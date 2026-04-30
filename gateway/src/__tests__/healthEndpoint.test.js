const express = require('express');

/**
 * Unit tests for the /health endpoint response shape.
 *
 * We build a minimal Express app that mirrors the health route logic from index.js,
 * injecting mock dependencies to test the three session restore states:
 * idle, in_progress, and completed.
 *
 * **Validates: Requirements 5.1, 5.2, 5.4, 5.5**
 */

/**
 * Helper: create a test Express app with the health endpoint wired to the given mocks.
 */
function createHealthApp({ sessionRestoreQueue, backoffManager, getDeviceCount, maxConnections }) {
    const app = express();

    app.get('/health', (req, res) => {
        const restoreProgress = sessionRestoreQueue.getProgress();
        const devicesInBackoff = backoffManager.getAllInBackoff();
        const maxConn = maxConnections || 100;

        res.json({
            status: 'ok',
            uptime: process.uptime(),
            timestamp: new Date().toISOString(),
            devices: getDeviceCount(),
            protection: {
                session_restore: {
                    status: restoreProgress.status,
                    progress_percentage: restoreProgress.total > 0
                        ? Math.round(((restoreProgress.restored + restoreProgress.failed) / restoreProgress.total) * 100)
                        : 0,
                    total: restoreProgress.total,
                    restored: restoreProgress.restored,
                    failed: restoreProgress.failed,
                    pending: restoreProgress.pending,
                },
                backoff: {
                    devices_in_backoff: devicesInBackoff.length,
                    devices: devicesInBackoff.map(d => ({
                        device_id: d.deviceId,
                        failures: d.failures,
                        next_retry_at: d.nextRetryAt,
                    })),
                },
                capacity: {
                    active_connections: getDeviceCount(),
                    max_connections: maxConn,
                },
            },
        });
    });

    return app;
}

/**
 * Make a GET request to the app and parse the JSON response.
 */
async function getHealth(app) {
    // Use a simple approach: start a server on a random port, make a request, close it
    return new Promise((resolve, reject) => {
        const server = app.listen(0, '127.0.0.1', () => {
            const { port } = server.address();
            const http = require('http');

            http.get(`http://127.0.0.1:${port}/health`, (res) => {
                let data = '';
                res.on('data', chunk => { data += chunk; });
                res.on('end', () => {
                    server.close();
                    try {
                        resolve({ status: res.statusCode, body: JSON.parse(data) });
                    } catch (e) {
                        reject(e);
                    }
                });
            }).on('error', (err) => {
                server.close();
                reject(err);
            });
        });
    });
}

describe('Health endpoint response shape', () => {
    it('returns protection fields in idle state (no sessions to restore)', async () => {
        const app = createHealthApp({
            sessionRestoreQueue: {
                getProgress: () => ({
                    status: 'idle',
                    total: 0,
                    restored: 0,
                    failed: 0,
                    pending: 0,
                    startedAt: null,
                    completedAt: null,
                }),
            },
            backoffManager: {
                getAllInBackoff: () => [],
            },
            getDeviceCount: () => 0,
            maxConnections: 50,
        });

        const { status, body } = await getHealth(app);

        expect(status).toBe(200);
        expect(body.status).toBe('ok');
        expect(typeof body.uptime).toBe('number');
        expect(typeof body.timestamp).toBe('string');
        expect(body.devices).toBe(0);

        // session_restore
        expect(body.protection.session_restore).toEqual({
            status: 'idle',
            progress_percentage: 0,
            total: 0,
            restored: 0,
            failed: 0,
            pending: 0,
        });

        // backoff
        expect(body.protection.backoff).toEqual({
            devices_in_backoff: 0,
            devices: [],
        });

        // capacity
        expect(body.protection.capacity).toEqual({
            active_connections: 0,
            max_connections: 50,
        });
    });

    it('returns protection fields in in_progress state with active restorations', async () => {
        const app = createHealthApp({
            sessionRestoreQueue: {
                getProgress: () => ({
                    status: 'in_progress',
                    total: 10,
                    restored: 3,
                    failed: 1,
                    pending: 6,
                    startedAt: '2025-01-15T10:00:00.000Z',
                    completedAt: null,
                }),
            },
            backoffManager: {
                getAllInBackoff: () => [
                    {
                        deviceId: 'device-1',
                        failures: 3,
                        nextRetryAt: '2025-01-15T10:05:00.000Z',
                        lastError: 'Connection timeout',
                        manualInterventionRequired: false,
                    },
                    {
                        deviceId: 'device-2',
                        failures: 7,
                        nextRetryAt: '2025-01-15T10:10:00.000Z',
                        lastError: 'Rate limited',
                        manualInterventionRequired: false,
                    },
                ],
            },
            getDeviceCount: () => 5,
            maxConnections: 100,
        });

        const { status, body } = await getHealth(app);

        expect(status).toBe(200);
        expect(body.status).toBe('ok');
        expect(body.devices).toBe(5);

        // session_restore — 3 restored + 1 failed out of 10 = 40%
        expect(body.protection.session_restore).toEqual({
            status: 'in_progress',
            progress_percentage: 40,
            total: 10,
            restored: 3,
            failed: 1,
            pending: 6,
        });

        // backoff — 2 devices in backoff
        expect(body.protection.backoff.devices_in_backoff).toBe(2);
        expect(body.protection.backoff.devices).toEqual([
            { device_id: 'device-1', failures: 3, next_retry_at: '2025-01-15T10:05:00.000Z' },
            { device_id: 'device-2', failures: 7, next_retry_at: '2025-01-15T10:10:00.000Z' },
        ]);

        // capacity
        expect(body.protection.capacity).toEqual({
            active_connections: 5,
            max_connections: 100,
        });
    });

    it('returns protection fields in completed state', async () => {
        const app = createHealthApp({
            sessionRestoreQueue: {
                getProgress: () => ({
                    status: 'completed',
                    total: 8,
                    restored: 6,
                    failed: 2,
                    pending: 0,
                    startedAt: '2025-01-15T10:00:00.000Z',
                    completedAt: '2025-01-15T10:02:30.000Z',
                }),
            },
            backoffManager: {
                getAllInBackoff: () => [],
            },
            getDeviceCount: () => 6,
            maxConnections: 200,
        });

        const { status, body } = await getHealth(app);

        expect(status).toBe(200);
        expect(body.status).toBe('ok');
        expect(body.devices).toBe(6);

        // session_restore — 6 restored + 2 failed out of 8 = 100%
        expect(body.protection.session_restore).toEqual({
            status: 'completed',
            progress_percentage: 100,
            total: 8,
            restored: 6,
            failed: 2,
            pending: 0,
        });

        // backoff — no devices in backoff after completion
        expect(body.protection.backoff).toEqual({
            devices_in_backoff: 0,
            devices: [],
        });

        // capacity
        expect(body.protection.capacity).toEqual({
            active_connections: 6,
            max_connections: 200,
        });
    });

    it('calculates progress_percentage correctly for partial completion', async () => {
        const app = createHealthApp({
            sessionRestoreQueue: {
                getProgress: () => ({
                    status: 'in_progress',
                    total: 3,
                    restored: 1,
                    failed: 0,
                    pending: 2,
                    startedAt: '2025-01-15T10:00:00.000Z',
                    completedAt: null,
                }),
            },
            backoffManager: {
                getAllInBackoff: () => [],
            },
            getDeviceCount: () => 1,
            maxConnections: 100,
        });

        const { body } = await getHealth(app);

        // 1 out of 3 = 33.33... rounds to 33
        expect(body.protection.session_restore.progress_percentage).toBe(33);
    });

    it('defaults max_connections to 100 when not specified', async () => {
        const app = createHealthApp({
            sessionRestoreQueue: {
                getProgress: () => ({
                    status: 'idle',
                    total: 0,
                    restored: 0,
                    failed: 0,
                    pending: 0,
                    startedAt: null,
                    completedAt: null,
                }),
            },
            backoffManager: {
                getAllInBackoff: () => [],
            },
            getDeviceCount: () => 0,
            maxConnections: undefined,
        });

        const { body } = await getHealth(app);

        expect(body.protection.capacity.max_connections).toBe(100);
    });
});
