const fc = require('fast-check');
const fs = require('fs');
const path = require('path');
const os = require('os');
const { BackoffManager, DEFAULTS, VALID_RANGES } = require('../backoffManager');

// Helper to create a temp directory for state persistence tests
function createTempDir() {
    return fs.mkdtempSync(path.join(os.tmpdir(), 'backoff-test-'));
}

// Helper to clean up temp directories
function cleanupTempDir(dir) {
    try {
        fs.rmSync(dir, { recursive: true, force: true });
    } catch (e) {
        // ignore cleanup errors
    }
}

/**
 * Property 3: Exponential backoff delay within bounds
 *
 * For any failure count F (0 ≤ F < maxRetries), the calculated retry delay shall fall within
 * the range [baseDelay * (1 - jitterFactor), min(initialDelay * multiplier^F, maxDelay) * (1 + jitterFactor)]
 * where baseDelay = min(initialDelay * multiplier^F, maxDelay).
 *
 * **Validates: Requirements 3.1, 3.7**
 */
describe('Feature: gateway-rate-limiting, Property 3: Exponential backoff delay within bounds', () => {
    it('delay is always within [baseDelay * (1 - jitterFactor), baseDelay * (1 + jitterFactor)]', () => {
        fc.assert(
            fc.property(
                // Generate valid config values within allowed ranges
                fc.integer({ min: 1000, max: 60000 }),   // initialDelay
                fc.double({ min: 60000, max: 600000, noNaN: true }),  // maxDelay
                fc.double({ min: 1.5, max: 4.0, noNaN: true }),      // multiplier
                fc.double({ min: 0.0, max: 0.5, noNaN: true }),      // jitterFactor
                fc.integer({ min: 2, max: 50 }),          // maxRetries (min 2 so we can have F < maxRetries)
                fc.integer({ min: 0, max: 48 }),          // failureIndex (0-based)
                (initialDelay, maxDelay, multiplier, jitterFactor, maxRetries, failureIndex) => {
                    // F is the 0-based failure index; we record F+1 total failures
                    // Ensure F+1 < maxRetries so the last failure is still retryable
                    const F = failureIndex % (maxRetries - 1);

                    const tempDir = createTempDir();
                    try {
                        const manager = new BackoffManager({
                            initialDelay,
                            maxDelay,
                            multiplier,
                            jitterFactor,
                            maxRetries,
                            statePath: path.join(tempDir, '.backoff_state.json'),
                            logWarnings: false,
                        });

                        // Record F+1 failures. The (F+1)th failure uses exponent F for delay calculation.
                        const deviceId = 'test-device';
                        let lastResult;
                        for (let i = 0; i <= F; i++) {
                            lastResult = manager.recordFailure(deviceId, 'test error');
                        }

                        const delay = lastResult.delay;
                        const baseDelay = Math.min(initialDelay * Math.pow(multiplier, F), maxDelay);
                        const lowerBound = baseDelay * (1 - jitterFactor);
                        const upperBound = baseDelay * (1 + jitterFactor);

                        // Allow small floating point tolerance
                        const epsilon = 0.001;
                        return delay >= lowerBound - epsilon && delay <= upperBound + epsilon;
                    } finally {
                        cleanupTempDir(tempDir);
                    }
                }
            ),
            { numRuns: 200 }
        );
    });
});

/**
 * Property 4: Success resets backoff state
 *
 * For any device with F consecutive failures (F > 0), recording a successful connection
 * shall reset the failure count to 0 and shouldRetry shall return true for subsequent failures.
 *
 * **Validates: Requirements 3.3**
 */
describe('Feature: gateway-rate-limiting, Property 4: Success resets backoff state', () => {
    it('recording success resets failure count to 0 and shouldRetry returns true', () => {
        fc.assert(
            fc.property(
                fc.integer({ min: 1, max: 49 }),  // failureCount (1 to maxRetries-1)
                fc.integer({ min: 2, max: 50 }),  // maxRetries
                fc.string({ minLength: 1, maxLength: 20 }),  // deviceId
                (failureCount, maxRetries, deviceId) => {
                    // Ensure failureCount < maxRetries so device is still retryable
                    const F = (failureCount % (maxRetries - 1)) + 1;

                    const tempDir = createTempDir();
                    try {
                        const manager = new BackoffManager({
                            maxRetries,
                            statePath: path.join(tempDir, '.backoff_state.json'),
                            logWarnings: false,
                        });

                        // Record F failures
                        for (let i = 0; i < F; i++) {
                            manager.recordFailure(deviceId, 'test error');
                        }

                        // Verify failures were recorded
                        const stateBeforeSuccess = manager.getState(deviceId);
                        if (!stateBeforeSuccess || stateBeforeSuccess.failures !== F) {
                            return false;
                        }

                        // Record success
                        manager.recordSuccess(deviceId);

                        // Verify reset
                        const stateAfterSuccess = manager.getState(deviceId);
                        if (!stateAfterSuccess || stateAfterSuccess.failures !== 0) {
                            return false;
                        }

                        // shouldRetry should return true after reset
                        return manager.shouldRetry(deviceId) === true;
                    } finally {
                        cleanupTempDir(tempDir);
                    }
                }
            ),
            { numRuns: 200 }
        );
    });
});

/**
 * Property 5: Max retries triggers manual intervention
 *
 * For any configured maxRetries value and any device, after exactly maxRetries consecutive
 * failures, shouldRetry shall return false and manualInterventionRequired shall be true.
 *
 * **Validates: Requirements 3.4**
 */
describe('Feature: gateway-rate-limiting, Property 5: Max retries triggers manual intervention', () => {
    it('after exactly maxRetries failures, shouldRetry is false and manualInterventionRequired is true', () => {
        fc.assert(
            fc.property(
                fc.integer({ min: 1, max: 50 }),  // maxRetries
                fc.string({ minLength: 1, maxLength: 20 }),  // deviceId
                (maxRetries, deviceId) => {
                    const tempDir = createTempDir();
                    try {
                        const manager = new BackoffManager({
                            maxRetries,
                            statePath: path.join(tempDir, '.backoff_state.json'),
                            logWarnings: false,
                        });

                        // Record exactly maxRetries failures
                        let lastResult;
                        for (let i = 0; i < maxRetries; i++) {
                            lastResult = manager.recordFailure(deviceId, 'test error');
                        }

                        // After exactly maxRetries failures:
                        // shouldRetry should be false
                        const shouldRetry = manager.shouldRetry(deviceId);
                        // manualInterventionRequired should be true
                        const state = manager.getState(deviceId);
                        // The last recordFailure should have returned shouldRetry: false
                        const lastShouldRetry = lastResult.shouldRetry;

                        return (
                            shouldRetry === false &&
                            state.manualInterventionRequired === true &&
                            lastShouldRetry === false
                        );
                    } finally {
                        cleanupTempDir(tempDir);
                    }
                }
            ),
            { numRuns: 200 }
        );
    });
});

/**
 * Property 6: Backoff state serialization round-trip
 *
 * For any valid backoff state (containing arbitrary device IDs, failure counts, timestamps,
 * and error messages), persisting the state to disk and loading it back shall produce an
 * equivalent state object.
 *
 * **Validates: Requirements 3.6**
 */
describe('Feature: gateway-rate-limiting, Property 6: Backoff state serialization round-trip', () => {
    it('persisting state and loading it back produces equivalent state', () => {
        // Arbitrary for generating valid device state entries
        const isoDateArb = fc.integer({
            min: new Date('2020-01-01').getTime(),
            max: new Date('2030-01-01').getTime(),
        }).map(ts => new Date(ts).toISOString());

        const deviceStateArb = fc.record({
            failures: fc.integer({ min: 0, max: 50 }),
            lastFailureAt: fc.option(isoDateArb, { nil: null }),
            nextRetryAt: fc.option(isoDateArb, { nil: null }),
            lastError: fc.option(
                fc.string({ minLength: 0, maxLength: 100 }),
                { nil: null }
            ),
            manualInterventionRequired: fc.boolean(),
        });

        // Generate a map of device IDs to states (1-10 devices)
        // Exclude prototype-polluting keys like __proto__ which JSON.parse handles specially
        const safeDeviceIdArb = fc.stringMatching(/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,35}$/)
            .filter(id => id !== '__proto__' && id !== 'constructor' && id !== 'prototype');

        const stateMapArb = fc.array(
            fc.tuple(
                safeDeviceIdArb,
                deviceStateArb
            ),
            { minLength: 1, maxLength: 10 }
        );

        fc.assert(
            fc.property(stateMapArb, (entries) => {
                const tempDir = createTempDir();
                try {
                    const statePath = path.join(tempDir, '.backoff_state.json');

                    // Create a manager and populate it with the generated state
                    const manager1 = new BackoffManager({
                        statePath,
                        logWarnings: false,
                    });

                    // Manually set the device states
                    for (const [deviceId, state] of entries) {
                        manager1.devices.set(deviceId, { ...state });
                    }

                    // Persist to disk
                    manager1.persistState();

                    // Create a new manager that loads from disk
                    const manager2 = new BackoffManager({
                        statePath,
                        logWarnings: false,
                    });

                    // Verify all entries are equivalent
                    // Use a deduplicated map since entries may have duplicate keys
                    const expectedMap = new Map();
                    for (const [deviceId, state] of entries) {
                        expectedMap.set(deviceId, state);
                    }

                    if (manager2.devices.size !== expectedMap.size) {
                        return false;
                    }

                    for (const [deviceId, expectedState] of expectedMap) {
                        const loadedState = manager2.devices.get(deviceId);
                        if (!loadedState) return false;
                        if (loadedState.failures !== expectedState.failures) return false;
                        if (loadedState.lastFailureAt !== expectedState.lastFailureAt) return false;
                        if (loadedState.nextRetryAt !== expectedState.nextRetryAt) return false;
                        if (loadedState.lastError !== expectedState.lastError) return false;
                        if (loadedState.manualInterventionRequired !== expectedState.manualInterventionRequired) return false;
                    }

                    return true;
                } finally {
                    cleanupTempDir(tempDir);
                }
            }),
            { numRuns: 200 }
        );
    });
});

/**
 * Property 7: Invalid configuration fallback to defaults
 *
 * For any configuration parameter and any value outside its valid range, the system shall
 * use the defined default value and the resulting effective configuration shall match the default.
 *
 * **Validates: Requirements 6.5**
 */
describe('Feature: gateway-rate-limiting, Property 7: Invalid configuration fallback to defaults', () => {
    it('out-of-range values fall back to defaults', () => {
        // Generate values that are definitely outside valid ranges
        const outOfRangeArb = fc.record({
            initialDelay: fc.oneof(
                fc.integer({ min: -100000, max: 999 }),
                fc.integer({ min: 60001, max: 1000000 })
            ),
            maxDelay: fc.oneof(
                fc.integer({ min: -100000, max: 59999 }),
                fc.integer({ min: 600001, max: 10000000 })
            ),
            multiplier: fc.oneof(
                fc.double({ min: -10, max: 1.49, noNaN: true }),
                fc.double({ min: 4.01, max: 100, noNaN: true })
            ),
            jitterFactor: fc.oneof(
                fc.double({ min: -10, max: -0.01, noNaN: true }),
                fc.double({ min: 0.51, max: 10, noNaN: true })
            ),
            maxRetries: fc.oneof(
                fc.integer({ min: -100, max: 0 }),
                fc.integer({ min: 51, max: 1000 })
            ),
        });

        fc.assert(
            fc.property(outOfRangeArb, (invalidConfig) => {
                const tempDir = createTempDir();
                try {
                    const manager = new BackoffManager({
                        ...invalidConfig,
                        statePath: path.join(tempDir, '.backoff_state.json'),
                        logWarnings: false,
                    });

                    return (
                        manager.initialDelay === DEFAULTS.initialDelay &&
                        manager.maxDelay === DEFAULTS.maxDelay &&
                        manager.multiplier === DEFAULTS.multiplier &&
                        manager.jitterFactor === DEFAULTS.jitterFactor &&
                        manager.maxRetries === DEFAULTS.maxRetries
                    );
                } finally {
                    cleanupTempDir(tempDir);
                }
            }),
            { numRuns: 200 }
        );
    });

    it('NaN values fall back to defaults', () => {
        const tempDir = createTempDir();
        try {
            const manager = new BackoffManager({
                initialDelay: NaN,
                maxDelay: NaN,
                multiplier: NaN,
                jitterFactor: NaN,
                maxRetries: NaN,
                statePath: path.join(tempDir, '.backoff_state.json'),
                logWarnings: false,
            });

            expect(manager.initialDelay).toBe(DEFAULTS.initialDelay);
            expect(manager.maxDelay).toBe(DEFAULTS.maxDelay);
            expect(manager.multiplier).toBe(DEFAULTS.multiplier);
            expect(manager.jitterFactor).toBe(DEFAULTS.jitterFactor);
            expect(manager.maxRetries).toBe(DEFAULTS.maxRetries);
        } finally {
            cleanupTempDir(tempDir);
        }
    });

    it('non-numeric values fall back to defaults', () => {
        fc.assert(
            fc.property(
                fc.string({ minLength: 1, maxLength: 20 }),  // random string value
                (strValue) => {
                    const tempDir = createTempDir();
                    try {
                        const manager = new BackoffManager({
                            initialDelay: strValue,
                            maxDelay: strValue,
                            multiplier: strValue,
                            jitterFactor: strValue,
                            maxRetries: strValue,
                            statePath: path.join(tempDir, '.backoff_state.json'),
                            logWarnings: false,
                        });

                        return (
                            manager.initialDelay === DEFAULTS.initialDelay &&
                            manager.maxDelay === DEFAULTS.maxDelay &&
                            manager.multiplier === DEFAULTS.multiplier &&
                            manager.jitterFactor === DEFAULTS.jitterFactor &&
                            manager.maxRetries === DEFAULTS.maxRetries
                        );
                    } finally {
                        cleanupTempDir(tempDir);
                    }
                }
            ),
            { numRuns: 100 }
        );
    });
});
