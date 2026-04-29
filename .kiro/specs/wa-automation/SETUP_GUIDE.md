# WA Automation - Setup Guide

## Task 1.1: Project Configuration & Environment Setup

This document outlines the configuration completed for the WA Automation system.

### Environment Variables Configuration

The following environment variables have been added to `.env` and `.env.example`:

#### Baileys Gateway Configuration
```env
BAILEYS_GATEWAY_URL=http://localhost:3000
BAILEYS_WEBHOOK_SECRET=your-webhook-secret-key-change-in-production
```

**Description:**
- `BAILEYS_GATEWAY_URL`: Base URL of the external Baileys Gateway service (Node.js) that handles WhatsApp connections
- `BAILEYS_WEBHOOK_SECRET`: Secret key for validating incoming webhook requests from Baileys Gateway (HMAC-SHA256)

**Action Required:** Update `BAILEYS_WEBHOOK_SECRET` with a secure random key in production environments.

#### Queue Configuration
```env
DB_QUEUE_CONNECTION=mysql
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90
```

**Description:**
- `DB_QUEUE_CONNECTION`: Database connection for queue storage (MySQL)
- `DB_QUEUE_TABLE`: Table name for storing queue jobs (default: `jobs`)
- `DB_QUEUE`: Default queue name for job processing
- `DB_QUEUE_RETRY_AFTER`: Seconds to wait before retrying failed jobs (90 seconds)

**Status:** ✅ Queue driver is already configured to use `database` in `config/queue.php`

#### Logging Configuration
```env
LOG_CHANNEL=stack
LOG_STACK=single,wa_automation
LOG_LEVEL=debug
```

**Description:**
- `LOG_CHANNEL`: Primary logging channel (stack aggregates multiple channels)
- `LOG_STACK`: Channels to include in the stack (single + wa_automation)
- `LOG_LEVEL`: Logging level (debug, info, warning, error, critical)

#### System Configuration
```env
WA_AUTOMATION_TRIAL_DURATION_DAYS=14
WA_AUTOMATION_LOG_RETENTION_DAYS=90
WA_AUTOMATION_SYSTEM_LOG_RETENTION_DAYS=180
WA_AUTOMATION_DEFAULT_RATE_LIMIT_PER_HOUR=200
WA_AUTOMATION_DEFAULT_DELAY_MIN_SECONDS=5
WA_AUTOMATION_DEFAULT_DELAY_MAX_SECONDS=10
```

**Description:**
- `WA_AUTOMATION_TRIAL_DURATION_DAYS`: Default trial period for new tenants (14 days)
- `WA_AUTOMATION_LOG_RETENTION_DAYS`: Message logs retention period (90 days)
- `WA_AUTOMATION_SYSTEM_LOG_RETENTION_DAYS`: System logs retention period (180 days)
- `WA_AUTOMATION_DEFAULT_RATE_LIMIT_PER_HOUR`: Max messages per device per hour (200)
- `WA_AUTOMATION_DEFAULT_DELAY_MIN_SECONDS`: Minimum delay between messages (5 seconds)
- `WA_AUTOMATION_DEFAULT_DELAY_MAX_SECONDS`: Maximum delay between messages (10 seconds)

### Configuration Files

#### 1. `config/wa-automation.php` (NEW)

A comprehensive configuration file has been created to centralize all WA Automation settings:

**Sections:**
- **baileys**: Baileys Gateway connection settings (URL, webhook secret, timeout, retries)
- **queue**: Queue processing configuration (connection, table, retry settings)
- **rate_limiting**: Message rate limiting to prevent WhatsApp blocking
- **subscription**: Trial period and subscription management settings
- **log_retention**: Log cleanup retention periods
- **templates**: Message template settings (max length, variable pattern)
- **broadcast**: Bulk message broadcasting configuration
- **auto_reply**: Auto-reply functionality settings (cooldown, case sensitivity)
- **reminders**: Scheduled reminder configuration
- **alerts**: System alert and monitoring settings

**Access in Code:**
```php
use Illuminate\Support\Facades\Config;

$gatewayUrl = config('wa-automation.baileys.gateway_url');
$rateLimit = config('wa-automation.rate_limiting.default_per_hour');
```

#### 2. `config/logging.php` (UPDATED)

Added a new logging channel `wa_automation` for dedicated WA Automation logging:

```php
'wa_automation' => [
    'driver' => 'daily',
    'path' => storage_path('logs/wa-automation.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_DAILY_DAYS', 14),
    'replace_placeholders' => true,
],
```

**Features:**
- Daily log rotation
- Separate log file: `storage/logs/wa-automation.log`
- Configurable retention period
- Placeholder replacement for context data

**Access in Code:**
```php
use Illuminate\Support\Facades\Log;

Log::channel('wa_automation')->info('Message sent successfully', [
    'recipient' => '6281234567890',
    'job_id' => 'uuid',
]);
```

#### 3. `config/queue.php` (VERIFIED)

Queue configuration is already properly set up:
- **Driver**: `database` (configured via `QUEUE_CONNECTION`)
- **Connection**: MySQL database
- **Failed Jobs**: Stored in `failed_jobs` table with UUID driver
- **Retry Logic**: Configurable retry attempts and backoff

### Mail Server Configuration

The mail server is already configured in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=17533ddaf63ace
MAIL_PASSWORD=99c948416043c8
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Status:** ✅ Ready for sending subscription notifications, trial expiry alerts, and billing emails

**Action Required:** Update with production SMTP credentials before deploying to production.

### Laravel Boost & MCP Integration

The application already has Laravel Boost and MCP installed:
- **laravel/boost**: v2.4.5
- **laravel/mcp**: v0.7.0

**Available Tools:**
- `database-query`: Run read-only SQL queries
- `database-schema`: Inspect table structure
- `get-absolute-url`: Generate correct URLs
- `browser-logs`: Read browser logs and errors
- `search-docs`: Search version-specific documentation

### Database Configuration

The application uses MySQL database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=goblast
DB_USERNAME=root
DB_PASSWORD=
```

**Status:** ✅ Ready for migrations and model creation

### Verification Checklist

- ✅ Environment variables configured in `.env`
- ✅ Environment variables documented in `.env.example`
- ✅ WA Automation configuration file created (`config/wa-automation.php`)
- ✅ Logging channel added for WA Automation (`wa_automation`)
- ✅ Queue driver configured to use database
- ✅ Mail server configured for notifications
- ✅ Laravel Boost and MCP integration available
- ✅ Database connection ready for migrations

### Next Steps

1. **Task 1.2**: Create database migrations for all required tables
2. **Task 1.3**: Create Eloquent models with relationships
3. **Task 1.4**: Create factories and seeders for testing

### Configuration Access Examples

```php
// Get Baileys Gateway URL
$url = config('wa-automation.baileys.gateway_url');

// Get webhook secret
$secret = config('wa-automation.baileys.webhook_secret');

// Get rate limiting settings
$rateLimit = config('wa-automation.rate_limiting.default_per_hour');
$delayMin = config('wa-automation.rate_limiting.delay_min_seconds');
$delayMax = config('wa-automation.rate_limiting.delay_max_seconds');

// Get trial duration
$trialDays = config('wa-automation.subscription.trial_duration_days');

// Log to WA Automation channel
Log::channel('wa_automation')->info('Event occurred', ['data' => $value]);

// Access queue configuration
$queueConnection = config('wa-automation.queue.connection');
$queueTable = config('wa-automation.queue.table');
```

### Security Notes

1. **Webhook Secret**: Change `BAILEYS_WEBHOOK_SECRET` to a strong random value in production
2. **Mail Credentials**: Update SMTP credentials for production
3. **Database Password**: Set a strong password for database user in production
4. **Environment**: Set `APP_DEBUG=false` in production
5. **Log Level**: Consider setting `LOG_LEVEL=warning` or `LOG_LEVEL=error` in production

### Troubleshooting

**Issue**: Configuration not loading
```bash
php artisan config:cache
php artisan config:clear
```

**Issue**: Queue not processing
```bash
# Check queue status
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Monitor queue
php artisan queue:monitor
```

**Issue**: Logging not working
```bash
# Check log file permissions
ls -la storage/logs/

# Clear log cache
php artisan cache:clear
```

---

**Configuration Completed**: April 26, 2026
**Status**: Ready for database migrations (Task 1.2)
