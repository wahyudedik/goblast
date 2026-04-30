const { createLogger } = require('./logger');

const logger = createLogger('session-restore-queue');

/**
 * Default configuration values for session restore parameters.
 */
const DEFAULTS = {
    delayBetweenSessions: 5000,
    maxConcurrent: 3,
};

/**
 * Valid ranges for each configuration parameter.
 */
const VALID_RANGES = {
    delayBetweenSessions: { min: 1000, max: 30000 },
    maxConcurrent: { min: 1, max: 10 },
};

/**
 * Clamp a value to a valid range. If the value is outside the range,
 * log a warning and return the default value.
 */
function clampToDefault(paramName, value, logWarnings = true) {
    const range = VALID_RANGES[paramName];
    const defaultVal = DEFAULTS[paramName];

    if (value === undefined || value === null || typeof value !== 'number' || isNaN(value)) {
        if (logWarnings && value !== undefined && value !== null) {
            logger.warn(
                { param: paramName, value, default: defaultVal },
                `Invalid config value for ${paramName}, using default`
            );
        }
        return defaultVal;
    }

    if (value < range.min || value > range.max) {
        if (logWarnings) {
            logger.warn(
                { param: paramName, value, min: range.min, max: range.max, default: defaultVal },
                `Config value for ${paramName} out of range [${range.min}, ${range.max}], using default`
            );
        }
        return defaultVal;
    }

    // For maxConcurrent, ensure integer
    if (paramName === 'maxConcurrent') {
        return Math.floor(value);
    }

    return value;
}

class SessionRestoreQueue {
    /**
     * @param {object} deviceManager - Reference to the DeviceManager instance
     * @param {object} options
     * @param {number} [options.delayBetweenSessions] - Delay in ms between session restorations (1000–30000, default 5000)
     * @param {number} [options.maxConcurrent] - Maximum concurrent restorations (1–10, default 3)
     * @param {boolean} [options.logWarnings] - Whether to log config warnings (default true)
     */
    constructor(deviceManager, options = {}) {
        this.deviceManager = deviceManager;
        const logWarnings = options.logWarnings !== false;

        this.delayBetweenSessions = clampToDefault('delayBetweenSessions', options.delayBetweenSessions, logWarnings);
        this.maxConcurrent = clampToDefault('maxConcurrent', options.maxConcurrent, logWarnings);

        this.status = 'idle'; // idle | in_progress | completed
        this.queue = [];
        this.activeCount = 0;
        this.stats = {
            total: 0,
            restored: 0,
            failed: 0,
            pending: 0,
        };
        this.startedAt = null;
        this.completedAt = null;
    }

    /**
     * Build the restore queue from session directory names, sorted alphabetically.
     *
     * @param {string[]} sessionDirs - Array of device IDs (session directory names)
     */
    enqueueAll(sessionDirs) {
        // Sort by directory name for deterministic ordering
        this.queue = [...sessionDirs].sort();
        this.stats.total = this.queue.length;
        this.stats.pending = this.queue.length;
        this.stats.restored = 0;
        this.stats.failed = 0;

        logger.info(
            { total: this.stats.total },
            'Session restore queue built'
        );
    }

    /**
     * Process the restore queue with configurable concurrency limit and delay between restorations.
     * Returns a promise that resolves when all sessions have been processed.
     *
     * @returns {Promise<void>}
     */
    async processQueue() {
        if (this.queue.length === 0) {
            this.status = 'completed';
            this.completedAt = new Date().toISOString();
            return;
        }

        this.status = 'in_progress';
        this.startedAt = new Date().toISOString();
        this.completedAt = null;

        logger.info(
            { total: this.stats.total, maxConcurrent: this.maxConcurrent, delay: this.delayBetweenSessions },
            'Starting session restoration'
        );

        // Process queue with concurrency control
        const workers = [];
        for (let i = 0; i < this.maxConcurrent; i++) {
            workers.push(this._worker());
        }

        await Promise.all(workers);

        this.status = 'completed';
        this.completedAt = new Date().toISOString();

        logger.info(
            {
                total: this.stats.total,
                restored: this.stats.restored,
                failed: this.stats.failed,
            },
            'Session restoration completed'
        );
    }

    /**
     * Worker that pulls items from the queue and restores them one at a time.
     * Each worker adds a delay between restorations.
     *
     * @returns {Promise<void>}
     * @private
     */
    async _worker() {
        while (true) {
            const deviceId = this.queue.shift();
            if (deviceId === undefined) {
                break;
            }

            this.activeCount++;
            try {
                await this.restoreSession(deviceId);
                this.stats.restored++;
            } catch (error) {
                this.stats.failed++;
                logger.error(
                    { deviceId, error: error.message },
                    'Failed to restore session'
                );
            } finally {
                this.activeCount--;
                this.stats.pending = this.queue.length;
            }

            // Delay between restorations (only if there are more items)
            if (this.queue.length > 0) {
                await this._delay(this.delayBetweenSessions);
            }
        }
    }

    /**
     * Restore a single session by calling deviceManager._initDevice.
     *
     * @param {string} deviceId
     * @returns {Promise<{success: boolean, error?: string}>}
     */
    async restoreSession(deviceId) {
        logger.info({ deviceId }, 'Restoring session');

        try {
            await this.deviceManager._initDevice(deviceId, false);
            return { success: true };
        } catch (error) {
            logger.error({ deviceId, error: error.message }, 'Session restore failed');
            throw error;
        }
    }

    /**
     * Move a device to the front of the pending queue.
     * Used when a new QR code request arrives during restoration.
     *
     * @param {string} deviceId
     */
    prioritizeDevice(deviceId) {
        const index = this.queue.indexOf(deviceId);
        if (index > 0) {
            this.queue.splice(index, 1);
            this.queue.unshift(deviceId);
            logger.info({ deviceId }, 'Device prioritized in restore queue');
        }
    }

    /**
     * Get the current restoration progress.
     *
     * @returns {{ status: string, total: number, restored: number, failed: number, pending: number, startedAt: string|null, completedAt: string|null }}
     */
    getProgress() {
        return {
            status: this.status,
            total: this.stats.total,
            restored: this.stats.restored,
            failed: this.stats.failed,
            pending: this.stats.pending,
            startedAt: this.startedAt,
            completedAt: this.completedAt,
        };
    }

    /**
     * Check if restoration is currently in progress.
     *
     * @returns {boolean}
     */
    isRestoring() {
        return this.status === 'in_progress';
    }

    /**
     * Promise-based delay helper.
     *
     * @param {number} ms
     * @returns {Promise<void>}
     * @private
     */
    _delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

module.exports = { SessionRestoreQueue, DEFAULTS, VALID_RANGES, clampToDefault };
