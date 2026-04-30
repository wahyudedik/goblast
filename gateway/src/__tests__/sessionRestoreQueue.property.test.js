const fc = require('fast-check');
const { SessionRestoreQueue, DEFAULTS, VALID_RANGES } = require('../sessionRestoreQueue');

/**
 * Creates a mock DeviceManager that tracks concurrent active restorations.
 * Each call to _initDevice simulates async work with a small delay.
 *
 * @param {object} options
 * @param {Set<string>} [options.failingDevices] - Set of device IDs that should fail
 * @param {number} [options.restoreDelayMs] - Simulated restore delay in ms (default 10)
 * @returns {{ deviceManager: object, tracker: { maxActive: number, activeCount: number, restoredDevices: string[], failedDevices: string[] } }}
 */
function createMockDeviceManager(options = {}) {
    const failingDevices = options.failingDevices || new Set();
    const restoreDelayMs = options.restoreDelayMs || 10;

    const tracker = {
        maxActive: 0,
        activeCount: 0,
        restoredDevices: [],
        failedDevices: [],
    };

    const deviceManager = {
        _initDevice: async (deviceId, _waitForQr) => {
            tracker.activeCount++;
            if (tracker.activeCount > tracker.maxActive) {
                tracker.maxActive = tracker.activeCount;
            }

            // Simulate async work
            await new Promise(resolve => setTimeout(resolve, restoreDelayMs));

            tracker.activeCount--;

            if (failingDevices.has(deviceId)) {
                tracker.failedDevices.push(deviceId);
                throw new Error(`Simulated failure for ${deviceId}`);
            }

            tracker.restoredDevices.push(deviceId);
        },
    };

    return { deviceManager, tracker };
}

/**
 * Property 1: Concurrent restoration limit
 *
 * For any number of sessions N in the restore queue and any configured maxConcurrent value M,
 * at no point during queue processing shall the number of actively restoring sessions exceed M.
 *
 * **Validates: Requirements 2.3**
 */
describe('Feature: gateway-rate-limiting, Property 1: Concurrent restoration limit', () => {
    it('active restoration count never exceeds maxConcurrent', async () => {
        await fc.assert(
            fc.asyncProperty(
                fc.integer({ min: 1, max: 50 }),  // queue size N
                fc.integer({ min: 1, max: 10 }),  // maxConcurrent M
                async (queueSize, maxConcurrent) => {
                    const sessionDirs = Array.from(
                        { length: queueSize },
                        (_, i) => `device-${String(i).padStart(4, '0')}`
                    );

                    const { deviceManager, tracker } = createMockDeviceManager({
                        restoreDelayMs: 2,
                    });

                    const queue = new SessionRestoreQueue(deviceManager, {
                        delayBetweenSessions: 1000, // Use minimum valid delay
                        maxConcurrent,
                        logWarnings: false,
                    });

                    queue.enqueueAll(sessionDirs);

                    // Override delay to near-zero to speed up tests
                    queue._delay = () => Promise.resolve();

                    await queue.processQueue();

                    // The max active count should never exceed maxConcurrent
                    return tracker.maxActive <= maxConcurrent;
                }
            ),
            { numRuns: 100 }
        );
    }, 60000);
});

/**
 * Property 2: Restoration fault tolerance
 *
 * For any session restore queue containing sessions where some subset fails, all non-failing
 * sessions in the queue shall still be processed to completion, and the final restored count
 * plus failed count shall equal the total queue size.
 *
 * **Validates: Requirements 2.5**
 */
describe('Feature: gateway-rate-limiting, Property 2: Restoration fault tolerance', () => {
    it('restored + failed = total and all non-failing sessions are processed', async () => {
        await fc.assert(
            fc.asyncProperty(
                fc.integer({ min: 1, max: 30 }),  // queue size
                fc.integer({ min: 1, max: 10 }),  // maxConcurrent
                fc.array(fc.integer({ min: 0, max: 29 }), { minLength: 0, maxLength: 15 }),  // failure positions
                async (queueSize, maxConcurrent, failurePositions) => {
                    const sessionDirs = Array.from(
                        { length: queueSize },
                        (_, i) => `device-${String(i).padStart(4, '0')}`
                    );

                    // Determine which devices should fail (clamp positions to valid range)
                    const failingDevices = new Set();
                    for (const pos of failurePositions) {
                        const idx = pos % queueSize;
                        failingDevices.add(sessionDirs[idx]);
                    }

                    const expectedSuccessDevices = sessionDirs.filter(d => !failingDevices.has(d));

                    const { deviceManager, tracker } = createMockDeviceManager({
                        failingDevices,
                        restoreDelayMs: 2,
                    });

                    const queue = new SessionRestoreQueue(deviceManager, {
                        delayBetweenSessions: 1000,
                        maxConcurrent,
                        logWarnings: false,
                    });

                    queue.enqueueAll(sessionDirs);

                    // Override delay to near-zero to speed up tests
                    queue._delay = () => Promise.resolve();

                    await queue.processQueue();

                    const progress = queue.getProgress();

                    // restored + failed must equal total
                    if (progress.restored + progress.failed !== progress.total) {
                        return false;
                    }

                    // All non-failing sessions must have been restored
                    const restoredSet = new Set(tracker.restoredDevices);
                    for (const deviceId of expectedSuccessDevices) {
                        if (!restoredSet.has(deviceId)) {
                            return false;
                        }
                    }

                    // Failed count must match the number of unique failing devices
                    if (progress.failed !== failingDevices.size) {
                        return false;
                    }

                    // Status should be completed
                    if (progress.status !== 'completed') {
                        return false;
                    }

                    return true;
                }
            ),
            { numRuns: 100 }
        );
    }, 60000);
});
