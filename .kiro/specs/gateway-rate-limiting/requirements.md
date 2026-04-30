# Requirements Document

## Introduction

This document specifies the requirements for the Gateway Rate Limiting & Protection feature for the WhatsApp automation SaaS platform. The feature addresses critical stability issues where:

1. Too many failed WhatsApp connections can cause server IP throttling/blocking
2. Gateway restarts trigger connection storms by restoring all sessions simultaneously
3. Users can spam the "Add Device" button without rate limiting
4. No user feedback is provided when rate limited

The system will implement multi-layered protection at both the Laravel backend and Node.js gateway levels to prevent WhatsApp account blocking while providing clear user feedback.

## Glossary

- **Gateway**: The Node.js service using @whiskeysockets/baileys library that manages WhatsApp connections
- **Device**: A WhatsApp account connection managed by the system
- **Tenant**: A customer organization in the multi-tenant SaaS platform
- **Session**: Stored authentication credentials for a WhatsApp device connection
- **Connection_Storm**: Multiple simultaneous connection attempts that can trigger WhatsApp rate limiting
- **Exponential_Backoff**: A retry strategy where wait time increases exponentially after each failure
- **Rate_Limiter**: A component that restricts the frequency of operations within a time window
- **Backoff_Manager**: A component that tracks retry attempts and calculates appropriate wait times
- **Session_Restore_Queue**: A queue that manages gradual restoration of device sessions on gateway startup

## Requirements

### Requirement 1: Device Creation Rate Limiting

**User Story:** As a tenant user, I want the system to prevent me from creating too many devices too quickly, so that my account doesn't get flagged by WhatsApp for suspicious activity.

#### Acceptance Criteria

1. WHEN a tenant attempts to create a device, THE Rate_Limiter SHALL check if the tenant has exceeded the maximum device creation attempts within the configured time window
2. WHEN a tenant exceeds the device creation rate limit, THE Rate_Limiter SHALL reject the request with HTTP 429 status and return the number of seconds until the next attempt is allowed
3. THE Rate_Limiter SHALL allow a maximum of 3 device creation attempts per tenant within a 5-minute sliding window (configurable)
4. WHEN a device creation is rate limited, THE System SHALL display a user-friendly notification showing the remaining wait time
5. THE Rate_Limiter SHALL track rate limits per tenant, not per user, to prevent circumvention via multiple user accounts
6. WHEN the rate limit window expires, THE Rate_Limiter SHALL automatically allow new device creation attempts

### Requirement 2: Graceful Session Restore on Gateway Startup

**User Story:** As a system administrator, I want the gateway to restore sessions gradually on startup, so that WhatsApp servers don't throttle or block our IP address.

#### Acceptance Criteria

1. WHEN the Gateway starts, THE Session_Restore_Queue SHALL enumerate all existing session directories without initiating connections
2. THE Session_Restore_Queue SHALL restore sessions sequentially with a configurable delay between each restoration (default: 5 seconds)
3. THE Session_Restore_Queue SHALL limit concurrent session restorations to a configurable maximum (default: 3 concurrent connections)
4. WHILE session restoration is in progress, THE Gateway SHALL respond to health check requests with restoration progress information
5. IF a session restoration fails, THEN THE Session_Restore_Queue SHALL log the failure and continue with the next session without blocking the queue
6. THE Gateway SHALL emit a webhook notification to Laravel when session restoration completes, including success/failure counts
7. WHEN a new QR code request arrives during session restoration, THE Gateway SHALL prioritize the new request over queued restorations

### Requirement 3: Exponential Backoff for Connection Retries

**User Story:** As a system administrator, I want failed connections to use exponential backoff for retries, so that temporary WhatsApp issues don't cause rapid retry loops that trigger IP blocking.

#### Acceptance Criteria

1. WHEN a device connection fails, THE Backoff_Manager SHALL calculate the next retry delay using exponential backoff with jitter
2. THE Backoff_Manager SHALL use configurable parameters: initial delay (default: 5 seconds), maximum delay (default: 5 minutes), multiplier (default: 2), and jitter factor (default: 0.3)
3. THE Backoff_Manager SHALL track failure counts per device and reset the count after a successful connection
4. IF a device reaches the maximum retry attempts (default: 10), THEN THE Backoff_Manager SHALL mark the device as requiring manual intervention and stop automatic retries
5. WHEN a device is marked for manual intervention, THE System SHALL send a webhook notification to Laravel with the failure reason
6. THE Backoff_Manager SHALL persist retry state across gateway restarts to prevent retry count resets
7. WHEN calculating retry delay, THE Backoff_Manager SHALL apply jitter by adding random variation of ±30% to prevent thundering herd problems

### Requirement 4: User Rate Limit Notifications

**User Story:** As a tenant user, I want to see clear messages when I'm rate limited, so that I understand why my action was blocked and when I can try again.

#### Acceptance Criteria

1. WHEN a device creation request is rate limited, THE System SHALL display a toast notification with the message "Terlalu banyak percobaan. Silakan tunggu X menit Y detik sebelum mencoba lagi." (Too many attempts. Please wait X minutes Y seconds before trying again.)
2. THE System SHALL display a countdown timer on the "Add Device" button showing remaining wait time when rate limited
3. WHILE rate limited, THE System SHALL disable the "Add Device" button and show a visual indicator (grayed out with clock icon)
4. WHEN the rate limit expires, THE System SHALL automatically re-enable the "Add Device" button without requiring page refresh
5. THE System SHALL store rate limit expiry time in the browser session to persist the countdown across page navigations
6. IF a rate limited user attempts to bypass the UI by directly accessing the create device route, THEN THE System SHALL redirect back with an appropriate error message

### Requirement 5: Gateway Health Endpoint Enhancement

**User Story:** As a system administrator, I want the gateway health endpoint to report rate limiting and session restoration status, so that I can monitor system protection mechanisms.

#### Acceptance Criteria

1. THE Gateway health endpoint SHALL include current session restoration status (idle, in_progress, completed) and progress percentage
2. THE Gateway health endpoint SHALL include the number of devices currently in backoff state and their next retry times
3. THE Gateway health endpoint SHALL include rate limiting statistics: total requests blocked in the last hour
4. WHEN session restoration is in progress, THE Gateway health endpoint SHALL return the count of sessions pending, restoring, restored, and failed
5. THE Gateway health endpoint SHALL include the gateway's current connection capacity (active connections vs maximum allowed)

### Requirement 6: Configuration Management

**User Story:** As a system administrator, I want to configure rate limiting and backoff parameters, so that I can tune the system based on observed WhatsApp behavior.

#### Acceptance Criteria

1. THE System SHALL read rate limiting configuration from environment variables with sensible defaults
2. THE System SHALL support the following configurable parameters for device creation rate limiting: max_attempts (default: 3), window_seconds (default: 300)
3. THE System SHALL support the following configurable parameters for session restoration: delay_between_sessions_ms (default: 5000), max_concurrent_restorations (default: 3)
4. THE System SHALL support the following configurable parameters for exponential backoff: initial_delay_ms (default: 5000), max_delay_ms (default: 300000), multiplier (default: 2), jitter_factor (default: 0.3), max_retries (default: 10)
5. WHEN configuration values are invalid or out of acceptable ranges, THE System SHALL log a warning and use default values
6. THE Laravel configuration SHALL be stored in the existing `config/wa-automation.php` file under a new `gateway_protection` key
