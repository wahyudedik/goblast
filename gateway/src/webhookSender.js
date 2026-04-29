const axios = require('axios');
const crypto = require('crypto');
const { createLogger } = require('./logger');

const logger = createLogger('webhook');

class WebhookSender {
    constructor() {
        this.webhookUrl = process.env.WEBHOOK_URL;
        this.webhookSecret = process.env.WEBHOOK_SECRET;

        if (!this.webhookUrl) {
            logger.warn('WEBHOOK_URL not configured - webhooks will not be sent');
        }
    }

    /**
     * Send device status update to Laravel
     */
    async sendStatusUpdate(deviceId, status, extra = {}) {
        const payload = {
            event: 'device.status',
            device_id: deviceId,
            from: deviceId,
            message: status,
            status,
            ...extra,
            timestamp: new Date().toISOString(),
        };

        return this._send(payload);
    }

    /**
     * Send incoming message to Laravel
     */
    async sendIncomingMessage(deviceId, from, message, rawMessage = null) {
        const payload = {
            event: 'message.received',
            device_id: deviceId,
            from,
            message,
            message_id: rawMessage?.key?.id,
            timestamp: new Date().toISOString(),
        };

        return this._send(payload);
    }

    /**
     * Send payload to Laravel webhook endpoint
     */
    async _send(payload) {
        if (!this.webhookUrl) {
            return;
        }

        try {
            const body = JSON.stringify(payload);
            const signature = this._generateSignature(body);

            const response = await axios.post(this.webhookUrl, payload, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Baileys-Signature': signature,
                    'User-Agent': 'GoBlast-Gateway/1.0',
                },
                timeout: 10000,
            });

            logger.debug({ event: payload.event, status: response.status }, 'Webhook sent');
        } catch (error) {
            logger.error(
                {
                    event: payload.event,
                    error: error.message,
                    url: this.webhookUrl,
                },
                'Failed to send webhook'
            );
        }
    }

    /**
     * Generate HMAC SHA-256 signature
     */
    _generateSignature(payload) {
        return crypto.createHmac('sha256', this.webhookSecret || '').update(payload).digest('hex');
    }
}

module.exports = { WebhookSender };
