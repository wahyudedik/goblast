# Implementation Plan: Gateway Rate Limiting & Protection

## Overview

This plan implements multi-layered protection against WhatsApp account blocking and IP throttling. Work is split across the Laravel backend (device creation rate limiting, configuration, webhook handling) and the Node.js gateway (session restore queue, exponential backoff, health endpoint). Tasks are ordered so each builds on the previous, with gateway-side work following Laravel-side work to allow end-to-end wiring.

## Tasks

- [ ] 1. Add gateway protection configuration to Laravel
  - [ ] 1.1 Add `gateway_protection` key to `config/wa-automation.php`
    - Add `device_creation` sub-key with `max_attempts` and `window_seconds`
    - Add `session_restore` sub-key with `delay_between_sessions_ms` and `max_concurrent_restorations`
    - Add `backoff` sub-key with `initial_delay_ms`, `max_delay_ms`, `multiplier`, `jitter_factor`, `max_retries`
    - All values read from env with defaults matching the design document
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.6_

  - [ ] 1.2 Add corresponding environment variables to `.env.example`
    - Add `WA_GATEWAY_DEVICE_CREATION_MAX_ATTEMPTS`, `WA_GATEWAY_DEVICE_CREATION_WINDOW_SECONDS`
    - Add `WA_GATEWAY_SESSION_RESTORE_DELAY_MS`, `WA_GATEWAY_SESSION_RESTORE_MAX_CONCURRENT`
    - Add `WA_GATEWAY_BACKOFF_INITIAL_DELAY_MS`, `WA_GATEWAY_BACKOFF_MAX_DELAY_MS`, `WA_GATEWAY_BACKOFF_MULTIPLIER`, `WA_GATEWAY_BACKOFF_JITTER_FACTOR`, `WA_GATEWAY_BACKOFF_MAX_RETRIES`
    - _Requirements: 6.1_

- [ ] 2. Implement device creation rate limiting in Laravel
  - [ ] 2.1 Register `device-creation` rate limiter in `AppServiceProvider::boot()`
    - Use `RateLimiter::for('device-creation', ...)` keyed by `tenant:{tenant_id}`
    - Read `max_attempts` and `window_seconds` from `config('wa-automation.gateway_protection.device_creation')`
    - Fallback to `Limit::perMinute(1)->by($request->ip())` for unauthenticated requests
    - Custom response callback redirects to `devices.index` with `rate_limited` and `retry_after` flash data
    - _Requirements: 1.1, 1.2, 1.3, 1.5_

  - [ ] 2.2 Apply `throttle:device-creation` middleware to `DeviceController::store`
    - Add the middleware to the `store` method in the controller constructor or via route definition
    - Ensure the rate limiter intercepts before the existing validation and service call
    - _Requirements: 1.1, 1.2_

  - [ ] 2.3 Add `rateLimitStatus` AJAX endpoint to `DeviceController`
    - Return JSON with `is_limited`, `remaining_attempts`, and `retry_after` fields
    - Register route as `devices.rate-limit-status` in `routes/web.php`
    - _Requirements: 1.4, 4.4_

  - [ ]* 2.4 Write feature tests for device creation rate limiting
    - Test rate limit allows requests within threshold
    - Test rate limit blocks requests exceeding threshold (returns redirect with `rate_limited` flash)
    - Test rate limit is keyed by tenant (two users, same tenant, shared limit)
    - Test rate limit resets after window expires using `Carbon::setTestNow()`
    - Test direct route access when rate limited returns redirect with error
    - Test `rateLimitStatus` AJAX endpoint returns correct JSON
    - Use `php artisan make:test --phpunit DeviceCreationRateLimitTest`
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.6_

- [ ] 3. Checkpoint — Ensure all Laravel tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 4. Implement rate limit UI feedback with Alpine.js
  - [ ] 4.1 Update `resources/views/devices/index.blade.php` with rate limit countdown
    - Add Alpine.js component that reads `retry_after` from session flash data
    - Display toast notification with Indonesian message: "Terlalu banyak percobaan. Silakan tunggu X menit Y detik sebelum mencoba lagi."
    - Replace "Tambah Device" button with a disabled, grayed-out version showing a clock icon and countdown timer when rate limited
    - Store expiry timestamp in `sessionStorage` to persist countdown across page navigations
    - Auto-enable the button when countdown reaches zero without page refresh
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ] 4.2 Handle direct route bypass for rate-limited users
    - In `DeviceController::create`, check rate limit status and redirect back with error if limited
    - _Requirements: 4.6_

  - [ ]* 4.3 Write feature test for rate limit UI bypass protection
    - Test that accessing `devices.create` route when rate limited redirects with error
    - Use `php artisan make:test --phpunit DeviceCreationRateLimitBypassTest`
    - _Requirements: 4.6_

- [ ] 5. Implement BackoffManager in Node.js gateway
  - [ ] 5.1 Create `gateway/src/backoffManager.js`
    - Implement `BackoffManager` class with configurable `initialDelay`, `maxDelay`, `multiplier`, `jitterFactor`, `maxRetries`
    - Implement `recordFailure(deviceId)` returning `{ delay, attempt, shouldRetry }`
    - Implement `recordSuccess(deviceId)` that resets failure count to 0
    - Implement `getNextDelay(deviceId)` with formula: `min(initialDelay * multiplier^failures, maxDelay) * (1 ± random * jitterFactor)`
    - Implement `shouldRetry(deviceId)` returning false when max retries reached
    - Implement `getState(deviceId)` and `getAllInBackoff()`
    - Validate config parameters on construction, clamp to valid ranges, log warnings for out-of-range values
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.7, 6.4, 6.5_

  - [ ] 5.2 Implement backoff state persistence in `BackoffManager`
    - Implement `persistState()` to write device backoff state to `.backoff_state.json` in the sessions directory
    - Implement `loadState()` to restore state on construction
    - Handle corrupted state file gracefully (log warning, start fresh)
    - Handle write failures gracefully (log error, continue with in-memory state)
    - _Requirements: 3.6_

  - [ ]* 5.3 Write property tests for BackoffManager (install fast-check as dev dependency)
    - **Property 3: Exponential backoff delay within bounds**
    - **Validates: Requirements 3.1, 3.7**

  - [ ]* 5.4 Write property test: success resets backoff state
    - **Property 4: Success resets backoff state**
    - **Validates: Requirements 3.3**

  - [ ]* 5.5 Write property test: max retries triggers manual intervention
    - **Property 5: Max retries triggers manual intervention**
    - **Validates: Requirements 3.4**

  - [ ]* 5.6 Write property test: backoff state serialization round-trip
    - **Property 6: Backoff state serialization round-trip**
    - **Validates: Requirements 3.6**

  - [ ]* 5.7 Write property test: invalid configuration fallback to defaults
    - **Property 7: Invalid configuration fallback to defaults**
    - **Validates: Requirements 6.5**

- [ ] 6. Implement SessionRestoreQueue in Node.js gateway
  - [ ] 6.1 Create `gateway/src/sessionRestoreQueue.js`
    - Implement `SessionRestoreQueue` class accepting a `deviceManager` reference and options (`delayBetweenSessions`, `maxConcurrent`)
    - Implement `enqueueAll(sessionDirs)` to build the restore queue sorted by directory name
    - Implement `processQueue()` with configurable concurrency limit and delay between restorations
    - Implement `restoreSession(deviceId)` that calls `deviceManager._initDevice(deviceId, false)`
    - Implement `prioritizeDevice(deviceId)` to move a device to the front of the queue
    - Implement `getProgress()` returning `{ status, total, restored, failed, pending, startedAt, completedAt }`
    - Implement `isRestoring()` boolean check
    - Validate config parameters on construction, clamp to valid ranges
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.7, 6.3, 6.5_

  - [ ]* 6.2 Write property test: concurrent restoration limit
    - **Property 1: Concurrent restoration limit**
    - **Validates: Requirements 2.3**

  - [ ]* 6.3 Write property test: restoration fault tolerance
    - **Property 2: Restoration fault tolerance**
    - **Validates: Requirements 2.5**

- [ ] 7. Integrate new components into DeviceManager and gateway startup
  - [ ] 7.1 Update `gateway/src/deviceManager.js` to use `BackoffManager`
    - Import and instantiate `BackoffManager` in the constructor with config from environment variables
    - Replace the fixed `setTimeout(() => this._initDevice(deviceId, false), 3000)` reconnect with `BackoffManager.recordFailure()` and calculated delay
    - Call `BackoffManager.recordSuccess(deviceId)` on successful connection (`connection === 'open'`)
    - When `shouldRetry` returns false, send `device.manual_intervention` webhook via `WebhookSender`
    - _Requirements: 3.1, 3.3, 3.4, 3.5_

  - [ ] 7.2 Update `gateway/src/deviceManager.js` to use `SessionRestoreQueue`
    - Replace `_restoreExistingSessions()` with `SessionRestoreQueue.enqueueAll()` and `processQueue()`
    - Add `prioritizeDevice(deviceId)` call in `getQrCode()` when restoration is in progress
    - Send `session.restore_complete` webhook via `WebhookSender` when queue finishes
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.6, 2.7_

  - [ ] 7.3 Add new webhook event methods to `gateway/src/webhookSender.js`
    - Add `sendRestoreComplete(stats)` method for `session.restore_complete` event
    - Add `sendManualIntervention(deviceId, failureCount, lastError)` method for `device.manual_intervention` event
    - Both payloads follow existing format with `event`, `device_id`, `from`, `message`, `timestamp` fields
    - _Requirements: 2.6, 3.5_

  - [ ] 7.4 Add gateway environment variables to `gateway/.env` (or `.env.example`)
    - Add `SESSION_RESTORE_DELAY_MS`, `SESSION_RESTORE_MAX_CONCURRENT`
    - Add `BACKOFF_INITIAL_DELAY_MS`, `BACKOFF_MAX_DELAY_MS`, `BACKOFF_MULTIPLIER`, `BACKOFF_JITTER_FACTOR`, `BACKOFF_MAX_RETRIES`
    - Add `MAX_CONNECTIONS` for capacity reporting
    - _Requirements: 6.1, 6.3, 6.4_

- [ ] 8. Enhance gateway health endpoint
  - [ ] 8.1 Update `/health` endpoint in `gateway/src/index.js`
    - Add `protection.session_restore` object with status, progress percentage, total, restored, failed, pending from `SessionRestoreQueue.getProgress()`
    - Add `protection.backoff` object with `devices_in_backoff` count and device details from `BackoffManager.getAllInBackoff()`
    - Add `protection.capacity` object with `active_connections` and `max_connections`
    - _Requirements: 5.1, 5.2, 5.4, 5.5_

  - [ ]* 8.2 Write unit tests for health endpoint response shape
    - Test response includes protection fields in idle, in_progress, and completed states
    - _Requirements: 5.1, 5.2, 5.4, 5.5_

- [ ] 9. Handle new webhook events in Laravel
  - [ ] 9.1 Update `ProcessWebhookJob` to handle new event types
    - Add handler for `session.restore_complete` event — log the restore stats via `SystemLog`
    - Add handler for `device.manual_intervention` event — find the device, update status, create an alert via `AlertService`
    - Both events should be processed without requiring a matching device for `session.restore_complete` (uses `device_id: "system"`)
    - _Requirements: 2.6, 3.5_

  - [ ]* 9.2 Write feature tests for new webhook event handling
    - Test `session.restore_complete` webhook is accepted and logged
    - Test `device.manual_intervention` webhook is accepted, device status updated, alert created
    - Use `php artisan make:test --phpunit WebhookNewEventsTest`
    - _Requirements: 2.6, 3.5_

- [ ] 10. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Final wiring and validation
  - [ ] 11.1 Verify route registration completeness
    - Confirm `devices.rate-limit-status` route is registered in `routes/web.php` within the tenant middleware group
    - Confirm `throttle:device-creation` middleware is applied only to `DeviceController::store`
    - _Requirements: 1.1, 1.2, 4.4_

  - [ ] 11.2 Run `vendor/bin/pint --dirty --format agent` on all modified PHP files
    - _Requirements: Code style compliance_

  - [ ]* 11.3 Write integration test for full device creation flow with rate limiting
    - Test creating devices up to the limit, verify rate limit kicks in, verify countdown data in response
    - Use `php artisan make:test --phpunit DeviceCreationFlowIntegrationTest`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 4.1_

- [ ] 12. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties defined in the design document
- PHP tests use PHPUnit via `php artisan make:test --phpunit`; run with `php artisan test --compact`
- Gateway tests use fast-check for property-based testing (to be installed as dev dependency in task 5.3)
- Run `vendor/bin/pint --dirty --format agent` after any PHP file changes
