let makeWASocket, DisconnectReason, useMultiFileAuthState, fetchLatestBaileysVersion, delay;

// Baileys v6+ is ESM-only. We use a dynamic import wrapped in a ready promise
// so the rest of the file (which is CommonJS) can await it before using Baileys.
const baileysReady = import('baileys').then((mod) => {
    makeWASocket = mod.default || mod.makeWASocket;
    DisconnectReason = mod.DisconnectReason;
    useMultiFileAuthState = mod.useMultiFileAuthState;
    fetchLatestBaileysVersion = mod.fetchLatestBaileysVersion;
    delay = mod.delay;
});
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');
const { createLogger } = require('./logger');
const { WebhookSender } = require('./webhookSender');
const { BackoffManager } = require('./backoffManager');
const { SessionRestoreQueue } = require('./sessionRestoreQueue');

const logger = createLogger('device-manager');

class DeviceManager {
    constructor() {
        this.devices = new Map(); // device_id -> { socket, status, qrCode, qrResolvers }
        this.sessionPath = process.env.SESSION_PATH || './sessions';
        this.webhookSender = new WebhookSender();

        // Initialize BackoffManager with config from environment variables
        this.backoffManager = new BackoffManager({
            initialDelay: process.env.BACKOFF_INITIAL_DELAY_MS ? parseInt(process.env.BACKOFF_INITIAL_DELAY_MS) : undefined,
            maxDelay: process.env.BACKOFF_MAX_DELAY_MS ? parseInt(process.env.BACKOFF_MAX_DELAY_MS) : undefined,
            multiplier: process.env.BACKOFF_MULTIPLIER ? parseFloat(process.env.BACKOFF_MULTIPLIER) : undefined,
            jitterFactor: process.env.BACKOFF_JITTER_FACTOR !== undefined ? parseFloat(process.env.BACKOFF_JITTER_FACTOR) : undefined,
            maxRetries: process.env.BACKOFF_MAX_RETRIES ? parseInt(process.env.BACKOFF_MAX_RETRIES) : undefined,
        });

        // Initialize SessionRestoreQueue
        this.sessionRestoreQueue = new SessionRestoreQueue(this, {
            delayBetweenSessions: process.env.SESSION_RESTORE_DELAY_MS ? parseInt(process.env.SESSION_RESTORE_DELAY_MS) : undefined,
            maxConcurrent: process.env.SESSION_RESTORE_MAX_CONCURRENT ? parseInt(process.env.SESSION_RESTORE_MAX_CONCURRENT) : undefined,
        });

        // Ensure session directory exists
        if (!fs.existsSync(this.sessionPath)) {
            fs.mkdirSync(this.sessionPath, { recursive: true });
        }

        // Restore existing sessions on startup using SessionRestoreQueue
        // Wait for Baileys to load before restoring
        baileysReady.then(() => this._restoreExistingSessions()).catch((error) => {
            logger.error({ error: error.message }, 'Failed to initialize Baileys');
        });
    }

    /**
     * Restore sessions from disk on startup using SessionRestoreQueue
     */
    async _restoreExistingSessions() {
        try {
            const sessionDirs = fs.readdirSync(this.sessionPath).filter((dir) => {
                const fullPath = path.join(this.sessionPath, dir);
                return fs.statSync(fullPath).isDirectory() && !dir.startsWith('.');
            });

            logger.info({ count: sessionDirs.length }, 'Enqueuing sessions for gradual restoration');

            this.sessionRestoreQueue.enqueueAll(sessionDirs);
            await this.sessionRestoreQueue.processQueue();

            // Send restore complete webhook
            const progress = this.sessionRestoreQueue.getProgress();
            await this.webhookSender.sendRestoreComplete({
                total: progress.total,
                restored: progress.restored,
                failed: progress.failed,
            });
        } catch (error) {
            logger.error({ error: error.message }, 'Failed to restore sessions');
        }
    }

    /**
     * Initialize a device connection
     */
    async _initDevice(deviceId, waitForQr = true) {
        await baileysReady;

        const sessionDir = path.join(this.sessionPath, deviceId);

        if (!fs.existsSync(sessionDir)) {
            fs.mkdirSync(sessionDir, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(sessionDir);
        const { version } = await fetchLatestBaileysVersion();

        logger.info({ deviceId, version }, 'Initializing device');

        // Create device entry
        if (!this.devices.has(deviceId)) {
            this.devices.set(deviceId, {
                socket: null,
                status: 'pending',
                qrCode: null,
                qrResolvers: [],
                phoneNumber: null,
            });
        }

        const deviceEntry = this.devices.get(deviceId);

        const sock = makeWASocket({
            version,
            auth: state,
            logger: createLogger('baileys').child({ deviceId }),
            browser: ['GoBlast', 'Chrome', '1.0.0'],
            generateHighQualityLinkPreview: false,
            syncFullHistory: false,
        });

        deviceEntry.socket = sock;

        // Handle connection updates
        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                logger.info({ deviceId }, 'QR code received');
                try {
                    // Convert QR to base64 data URL
                    const qrDataUrl = await QRCode.toDataURL(qr, {
                        width: 300,
                        margin: 2,
                        color: { dark: '#000000', light: '#FFFFFF' },
                    });

                    deviceEntry.qrCode = qrDataUrl;
                    deviceEntry.status = 'pending';

                    // Resolve any waiting QR requests
                    const resolvers = [...deviceEntry.qrResolvers];
                    deviceEntry.qrResolvers = [];
                    resolvers.forEach((resolve) => resolve(qrDataUrl));
                } catch (error) {
                    logger.error({ deviceId, error: error.message }, 'Failed to generate QR data URL');
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                logger.info({ deviceId, statusCode, shouldReconnect }, 'Connection closed');

                deviceEntry.status = 'disconnected';
                deviceEntry.qrCode = null;

                // Notify Laravel
                await this.webhookSender.sendStatusUpdate(deviceId, 'disconnected', {
                    reason: lastDisconnect?.error?.message,
                });

                if (shouldReconnect) {
                    const errorMessage = lastDisconnect?.error?.message || 'Unknown error';
                    const { delay: retryDelay, attempt, shouldRetry } = this.backoffManager.recordFailure(deviceId, errorMessage);

                    if (shouldRetry) {
                        logger.info({ deviceId, attempt, retryDelay }, 'Scheduling reconnect with backoff');
                        setTimeout(() => this._initDevice(deviceId, false), retryDelay);
                    } else {
                        // Max retries reached - send manual intervention webhook
                        const state = this.backoffManager.getState(deviceId);
                        logger.warn({ deviceId, attempt }, 'Max retries reached, manual intervention required');
                        await this.webhookSender.sendManualIntervention(
                            deviceId,
                            state.failures,
                            state.lastError
                        );
                    }
                } else {
                    // Logged out - clean up session
                    logger.info({ deviceId }, 'Device logged out, cleaning session');
                    this._cleanSession(deviceId);
                }
            } else if (connection === 'open') {
                logger.info({ deviceId }, 'Connection opened successfully');

                deviceEntry.status = 'connected';
                deviceEntry.qrCode = null;
                deviceEntry.phoneNumber = sock.user?.id?.split(':')[0];

                // Reset backoff state on successful connection
                this.backoffManager.recordSuccess(deviceId);

                // Notify Laravel
                await this.webhookSender.sendStatusUpdate(deviceId, 'connected', {
                    phone_number: deviceEntry.phoneNumber,
                    name: sock.user?.name,
                });
            } else if (connection === 'connecting') {
                deviceEntry.status = 'pending';
            }
        });

        // Save credentials when updated
        sock.ev.on('creds.update', saveCreds);

        // Handle incoming messages
        sock.ev.on('messages.upsert', async ({ messages, type }) => {
            if (type !== 'notify') return;

            for (const message of messages) {
                if (message.key.fromMe) continue;

                const from = message.key.remoteJid?.replace('@s.whatsapp.net', '').replace('@g.us', '');
                const text =
                    message.message?.conversation ||
                    message.message?.extendedTextMessage?.text ||
                    message.message?.imageMessage?.caption ||
                    '';

                if (!from || !text) continue;

                logger.info({ deviceId, from }, 'Incoming message');

                // Send to Laravel webhook
                await this.webhookSender.sendIncomingMessage(deviceId, from, text, message);
            }
        });

        return sock;
    }

    /**
     * Get QR code for a device (creates device if not exists)
     */
    async getQrCode(deviceId) {
        // If device already connected, throw error
        if (this.devices.has(deviceId)) {
            const device = this.devices.get(deviceId);
            if (device.status === 'connected') {
                throw new Error('Device is already connected');
            }
            // If QR already available, return it
            if (device.qrCode) {
                return device.qrCode;
            }
        }

        // Prioritize this device if session restoration is in progress
        if (this.sessionRestoreQueue.isRestoring()) {
            this.sessionRestoreQueue.prioritizeDevice(deviceId);
        }

        // Initialize device if not exists
        if (!this.devices.has(deviceId)) {
            await this._initDevice(deviceId, true);
        }

        const device = this.devices.get(deviceId);

        // Wait for QR code with timeout
        return new Promise((resolve, reject) => {
            // If QR already available
            if (device.qrCode) {
                return resolve(device.qrCode);
            }

            // Add resolver to queue
            device.qrResolvers.push(resolve);

            // Timeout after 30 seconds
            setTimeout(() => {
                const idx = device.qrResolvers.indexOf(resolve);
                if (idx > -1) {
                    device.qrResolvers.splice(idx, 1);
                    reject(new Error('QR code timeout - please try again'));
                }
            }, 30000);
        });
    }

    /**
     * Get device connection status
     */
    getDeviceStatus(deviceId) {
        if (!this.devices.has(deviceId)) {
            return 'disconnected';
        }
        return this.devices.get(deviceId).status;
    }

    /**
     * Send a WhatsApp message
     */
    async sendMessage(deviceId, to, message) {
        await baileysReady;

        if (!this.devices.has(deviceId)) {
            throw new Error(`Device ${deviceId} not found`);
        }

        const device = this.devices.get(deviceId);

        if (device.status !== 'connected') {
            throw new Error(`Device ${deviceId} is not connected (status: ${device.status})`);
        }

        // Format phone number to WhatsApp JID
        const jid = this._formatJid(to);

        // Add random delay to avoid rate limiting
        const minDelay = parseInt(process.env.MESSAGE_DELAY_MIN) || 3000;
        const maxDelay = parseInt(process.env.MESSAGE_DELAY_MAX) || 7000;
        const delayMs = Math.floor(Math.random() * (maxDelay - minDelay + 1)) + minDelay;

        await delay(delayMs);

        const result = await device.socket.sendMessage(jid, { text: message });

        logger.info({ deviceId, to: jid, messageId: result?.key?.id }, 'Message sent');

        return result;
    }

    /**
     * Disconnect a device
     */
    async disconnectDevice(deviceId) {
        if (!this.devices.has(deviceId)) {
            return; // Already disconnected
        }

        const device = this.devices.get(deviceId);

        try {
            if (device.socket) {
                await device.socket.logout();
            }
        } catch (error) {
            logger.warn({ deviceId, error: error.message }, 'Error during logout');
        }

        this._cleanSession(deviceId);
        this.devices.delete(deviceId);

        logger.info({ deviceId }, 'Device disconnected and session cleaned');
    }

    /**
     * Restart a device connection
     */
    async restartDevice(deviceId) {
        if (this.devices.has(deviceId)) {
            const device = this.devices.get(deviceId);
            try {
                device.socket?.end?.(undefined);
            } catch (e) {
                // ignore
            }
            this.devices.delete(deviceId);
        }

        await this._initDevice(deviceId, false);
        logger.info({ deviceId }, 'Device restarted');
    }

    /**
     * Disconnect all devices (for graceful shutdown)
     */
    async disconnectAll() {
        logger.info('Disconnecting all devices...');
        const promises = [];
        for (const [deviceId, device] of this.devices) {
            if (device.socket) {
                try {
                    const result = device.socket.end?.(undefined);
                    if (result && typeof result.catch === 'function') {
                        promises.push(
                            result.catch((e) => {
                                logger.warn({ deviceId, error: e.message }, 'Error closing socket');
                            })
                        );
                    }
                } catch (e) {
                    logger.warn({ deviceId, error: e.message }, 'Error closing socket');
                }
            }
        }
        await Promise.allSettled(promises);
        logger.info('All devices disconnected');
    }

    /**
     * Get all devices info
     */
    getAllDevices() {
        const result = [];
        for (const [deviceId, device] of this.devices) {
            result.push({
                device_id: deviceId,
                status: device.status,
                phone_number: device.phoneNumber,
            });
        }
        return result;
    }

    /**
     * Get total device count
     */
    getDeviceCount() {
        return this.devices.size;
    }

    /**
     * Format phone number to WhatsApp JID
     */
    _formatJid(phone) {
        // Remove any non-numeric characters
        const cleaned = phone.replace(/\D/g, '');
        return `${cleaned}@s.whatsapp.net`;
    }

    /**
     * Clean session files for a device
     */
    _cleanSession(deviceId) {
        const sessionDir = path.join(this.sessionPath, deviceId);
        if (fs.existsSync(sessionDir)) {
            fs.rmSync(sessionDir, { recursive: true, force: true });
            logger.info({ deviceId }, 'Session cleaned');
        }
    }
}

module.exports = { DeviceManager };
