const fs = require('fs');
const path = require('path');
const { createLogger } = require('./logger');

const logger = createLogger('backoff-manager');

/**
 * Default configuration values for backoff parameters.
 */
const DEFAULTS = {
    initialDelay: 5000,
    maxDelay: 300000,
    multiplier: 2.0,
    jitterFactor: 0.3,
    maxRetries: 10,
};

/**
 * Valid ranges for each configuration parameter.
 */
const VALID_RANGES = {
    initialDelay: { min: 1000, max: 60000 },
    maxDelay: { min: 60000, max: 600000 },
    multiplier: { min: 1.5, max: 4.0 },
    jitterFactor: { min: 0.0, max: 0.5 },
    maxRetries: { min: 1, max: 50 },
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

    return value;
}

class BackoffManager {
    /**
     * @param {object} options
     * @param {number} [options.initialDelay] - Base delay in ms (1000–60000, default 5000)
     * @param {number} [options.maxDelay] - Maximum delay in ms (60000–600000, default 300000)
     * @param {number} [options.multiplier] - Exponential multiplier (1.5–4.0, default 2.0)
     * @param {number} [options.jitterFactor] - Jitter factor (0.0–0.5, default 0.3)
     * @param {number} [options.maxRetries] - Max retries before manual intervention (1–50, default 10)
     * @param {string} [options.statePath] - Path to persist backoff state
     * @param {boolean} [options.logWarnings] - Whether to log config warnings (default true)
     */
    constructor(options = {}) {
        const logWarnings = options.logWarnings !== false;

        this.initialDelay = clampToDefault('initialDelay', options.initialDelay, logWarnings);
        this.maxDelay = clampToDefault('maxDelay', options.maxDelay, logWarnings);
        this.multiplier = clampToDefault('multiplier', options.multiplier, logWarnings);
        this.jitterFactor = clampToDefault('jitterFactor', options.jitterFactor, logWarnings);
        this.maxRetries = clampToDefault('maxRetries', options.maxRetries, logWarnings);
        this.statePath = options.statePath || path.join(process.env.SESSION_PATH || './sessions', '.backoff_state.json');

        /** @type {Map<string, {failures: number, lastFailureAt: string|null, nextRetryAt: string|null, lastError: string|null, manualInterventionRequired: boolean}>} */
        this.devices = new Map();

        // Load persisted state on construction
        this.loadState();
    }

    /**
     * Record a connection failure for a device.
     * Increments the failure count and calculates the next retry delay.
     *
     * @param {string} deviceId
     * @param {string} [error] - Error message from the failure
     * @returns {{ delay: number, attempt: number, shouldRetry: boolean }}
     */
    recordFailure(deviceId, error = null) {
        let state = this.devices.get(deviceId);

        if (!state) {
            state = {
                failures: 0,
                lastFailureAt: null,
                nextRetryAt: null,
                lastError: null,
                manualInterventionRequired: false,
            };
            this.devices.set(deviceId, state);
        }

        state.failures += 1;
        state.lastFailureAt = new Date().toISOString();
        state.lastError = error;

        const canRetry = state.failures < this.maxRetries;
        state.manualInterventionRequired = !canRetry;

        let delay = 0;
        if (canRetry) {
            delay = this._calculateDelay(state.failures - 1);
            state.nextRetryAt = new Date(Date.now() + delay).toISOString();
        } else {
            state.nextRetryAt = null;
        }

        this.persistState();

        return {
            delay,
            attempt: state.failures,
            shouldRetry: canRetry,
        };
    }

    /**
     * Record a successful connection for a device.
     * Resets the failure count to 0.
     *
     * @param {string} deviceId
     */
    recordSuccess(deviceId) {
        const state = this.devices.get(deviceId);

        if (state) {
            state.failures = 0;
            state.lastFailureAt = null;
            state.nextRetryAt = null;
            state.lastError = null;
            state.manualInterventionRequired = false;
        }

        this.persistState();
    }

    /**
     * Calculate the next retry delay for a device based on its current failure count.
     * Formula: min(initialDelay * multiplier^failures, maxDelay) * (1 ± random * jitterFactor)
     *
     * @param {string} deviceId
     * @returns {number} Delay in milliseconds
     */
    getNextDelay(deviceId) {
        const state = this.devices.get(deviceId);
        const failures = state ? state.failures : 0;
        return this._calculateDelay(failures);
    }

    /**
     * Check if a device should retry connecting.
     * Returns false when max retries have been reached.
     *
     * @param {string} deviceId
     * @returns {boolean}
     */
    shouldRetry(deviceId) {
        const state = this.devices.get(deviceId);
        if (!state) {
            return true;
        }
        return state.failures < this.maxRetries;
    }

    /**
     * Get the backoff state for a specific device.
     *
     * @param {string} deviceId
     * @returns {object|null}
     */
    getState(deviceId) {
        const state = this.devices.get(deviceId);
        if (!state) {
            return null;
        }
        return { deviceId, ...state };
    }

    /**
     * Get all devices currently in backoff (failures > 0).
     *
     * @returns {Array<object>}
     */
    getAllInBackoff() {
        const result = [];
        for (const [deviceId, state] of this.devices) {
            if (state.failures > 0) {
                result.push({ deviceId, ...state });
            }
        }
        return result;
    }

    /**
     * Persist the current backoff state to disk.
     */
    persistState() {
        try {
            const stateObj = {};
            for (const [deviceId, state] of this.devices) {
                stateObj[deviceId] = { ...state };
            }

            const dir = path.dirname(this.statePath);
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }

            fs.writeFileSync(this.statePath, JSON.stringify(stateObj, null, 2), 'utf-8');
        } catch (error) {
            logger.error({ error: error.message, path: this.statePath }, 'Failed to persist backoff state');
        }
    }

    /**
     * Load backoff state from disk.
     * Handles corrupted files gracefully by starting fresh.
     */
    loadState() {
        try {
            if (!fs.existsSync(this.statePath)) {
                return;
            }

            const raw = fs.readFileSync(this.statePath, 'utf-8');
            const parsed = JSON.parse(raw);

            if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
                logger.warn({ path: this.statePath }, 'Corrupted backoff state file (not an object), starting fresh');
                return;
            }

            for (const [deviceId, state] of Object.entries(parsed)) {
                if (
                    typeof state === 'object' &&
                    state !== null &&
                    typeof state.failures === 'number'
                ) {
                    this.devices.set(deviceId, {
                        failures: state.failures,
                        lastFailureAt: state.lastFailureAt !== undefined ? state.lastFailureAt : null,
                        nextRetryAt: state.nextRetryAt !== undefined ? state.nextRetryAt : null,
                        lastError: state.lastError !== undefined ? state.lastError : null,
                        manualInterventionRequired: state.manualInterventionRequired === true,
                    });
                }
            }

            logger.info({ deviceCount: this.devices.size }, 'Loaded backoff state from disk');
        } catch (error) {
            logger.warn({ error: error.message, path: this.statePath }, 'Failed to load backoff state, starting fresh');
            this.devices.clear();
        }
    }

    /**
     * Calculate delay for a given failure index (0-based).
     * Formula: min(initialDelay * multiplier^failureIndex, maxDelay) * (1 + jitter)
     * where jitter is a random value in [-jitterFactor, +jitterFactor]
     *
     * @param {number} failureIndex - 0-based failure index
     * @returns {number} Delay in milliseconds
     * @private
     */
    _calculateDelay(failureIndex) {
        const baseDelay = Math.min(
            this.initialDelay * Math.pow(this.multiplier, failureIndex),
            this.maxDelay
        );

        const jitter = (Math.random() * 2 - 1) * this.jitterFactor;
        const delay = baseDelay * (1 + jitter);

        return Math.max(0, delay);
    }
}

module.exports = { BackoffManager, DEFAULTS, VALID_RANGES, clampToDefault };
