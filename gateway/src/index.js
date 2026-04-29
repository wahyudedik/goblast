require('dotenv').config();
const express = require('express');
const { DeviceManager } = require('./deviceManager');
const { createLogger } = require('./logger');

const logger = createLogger('server');
const app = express();

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Request logging middleware
app.use((req, res, next) => {
    logger.info({ method: req.method, url: req.url }, 'Incoming request');
    next();
});

// Initialize device manager
const deviceManager = new DeviceManager();

// ============================================================
// Health Check
// ============================================================
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        uptime: process.uptime(),
        timestamp: new Date().toISOString(),
        devices: deviceManager.getDeviceCount(),
    });
});

// ============================================================
// QR Code - Request QR for device connection
// GET /api/qr-code/:device_id
// ============================================================
app.get('/api/qr-code/:device_id', async (req, res) => {
    const { device_id } = req.params;

    try {
        logger.info({ device_id }, 'QR code requested');
        const qrCode = await deviceManager.getQrCode(device_id);

        res.json({
            success: true,
            qr_code: qrCode,
            device_id,
        });
    } catch (error) {
        logger.error({ device_id, error: error.message }, 'Failed to get QR code');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// ============================================================
// Device Status
// GET /api/device-status/:device_id
// ============================================================
app.get('/api/device-status/:device_id', async (req, res) => {
    const { device_id } = req.params;

    try {
        const status = deviceManager.getDeviceStatus(device_id);
        res.json({
            success: true,
            device_id,
            status,
        });
    } catch (error) {
        logger.error({ device_id, error: error.message }, 'Failed to get device status');
        res.status(500).json({
            success: false,
            error: error.message,
            status: 'error',
        });
    }
});

// ============================================================
// Send Message
// POST /api/send-message
// Body: { device_id, to, message }
// ============================================================
app.post('/api/send-message', async (req, res) => {
    const { device_id, to, message } = req.body;

    if (!device_id || !to || !message) {
        return res.status(400).json({
            success: false,
            error: 'Missing required fields: device_id, to, message',
        });
    }

    try {
        logger.info({ device_id, to }, 'Sending message');
        const result = await deviceManager.sendMessage(device_id, to, message);

        res.json({
            success: true,
            message_id: result.key?.id,
            status: 'sent',
        });
    } catch (error) {
        logger.error({ device_id, to, error: error.message }, 'Failed to send message');
        res.status(500).json({
            success: false,
            error: error.message,
            status: 'failed',
        });
    }
});

// ============================================================
// Disconnect Device
// POST /api/disconnect/:device_id
// ============================================================
app.post('/api/disconnect/:device_id', async (req, res) => {
    const { device_id } = req.params;

    try {
        logger.info({ device_id }, 'Disconnecting device');
        await deviceManager.disconnectDevice(device_id);

        res.json({
            success: true,
            message: 'Device disconnected successfully',
        });
    } catch (error) {
        logger.error({ device_id, error: error.message }, 'Failed to disconnect device');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// ============================================================
// Restart Instance
// POST /api/restart-instance/:instance_id
// ============================================================
app.post('/api/restart-instance/:instance_id', async (req, res) => {
    const { instance_id } = req.params;

    try {
        logger.info({ instance_id }, 'Restarting instance');
        await deviceManager.restartDevice(instance_id);

        res.json({
            success: true,
            message: 'Instance restarted successfully',
        });
    } catch (error) {
        logger.error({ instance_id, error: error.message }, 'Failed to restart instance');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// ============================================================
// List all devices
// GET /api/devices
// ============================================================
app.get('/api/devices', (req, res) => {
    const devices = deviceManager.getAllDevices();
    res.json({ success: true, devices });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({ error: 'Not found' });
});

// Error handler
app.use((err, req, res, next) => {
    logger.error({ error: err.message }, 'Unhandled error');
    res.status(500).json({ error: 'Internal server error' });
});

// Start server
const PORT = process.env.PORT || 3000;
const HOST = process.env.HOST || '0.0.0.0';

app.listen(PORT, HOST, () => {
    logger.info(`GoBlast Baileys Gateway running on http://${HOST}:${PORT}`);
    logger.info(`Webhook URL: ${process.env.WEBHOOK_URL}`);
    logger.info(`Session path: ${process.env.SESSION_PATH || './sessions'}`);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
    logger.info('SIGTERM received, shutting down gracefully...');
    await deviceManager.disconnectAll();
    process.exit(0);
});

process.on('SIGINT', async () => {
    logger.info('SIGINT received, shutting down gracefully...');
    await deviceManager.disconnectAll();
    process.exit(0);
});
