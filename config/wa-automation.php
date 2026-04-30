<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baileys Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the external Baileys Gateway service that handles
    | WhatsApp connections and message delivery.
    |
    */

    'baileys' => [
        'gateway_url' => env('BAILEYS_GATEWAY_URL', 'http://localhost:3000'),
        'webhook_secret' => env('BAILEYS_WEBHOOK_SECRET', 'your-webhook-secret-key'),
        'timeout' => 30, // seconds
        'max_retries' => 3,
        'retry_backoff' => [30, 60, 120], // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for message queue processing and delivery.
    |
    */

    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'database'),
        'table' => env('DB_QUEUE_TABLE', 'jobs'),
        'failed_table' => 'failed_jobs',
        'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings to prevent WhatsApp account blocking.
    |
    */

    'rate_limiting' => [
        'default_per_hour' => (int) env('WA_AUTOMATION_DEFAULT_RATE_LIMIT_PER_HOUR', 200),
        'delay_min_seconds' => (int) env('WA_AUTOMATION_DEFAULT_DELAY_MIN_SECONDS', 5),
        'delay_max_seconds' => (int) env('WA_AUTOMATION_DEFAULT_DELAY_MAX_SECONDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription & Trial Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for trial periods and subscription management.
    |
    */

    'subscription' => [
        'trial_duration_days' => (int) env('WA_AUTOMATION_TRIAL_DURATION_DAYS', 14),
        'trial_expiry_warning_days' => 3,
        'trial_expiry_reminder_days' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention Configuration
    |--------------------------------------------------------------------------
    |
    | How long to retain various types of logs before automatic cleanup.
    |
    */

    'log_retention' => [
        'message_logs_days' => (int) env('WA_AUTOMATION_LOG_RETENTION_DAYS', 90),
        'system_logs_days' => (int) env('WA_AUTOMATION_SYSTEM_LOG_RETENTION_DAYS', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Template Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for message templates and variable substitution.
    |
    */

    'templates' => [
        'max_content_length' => 4096,
        'variable_pattern' => '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
        'missing_variable_replacement' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcast Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for bulk message broadcasting.
    |
    */

    'broadcast' => [
        'max_csv_size_mb' => 5,
        'max_recipients_per_batch' => 1000,
        'delay_between_messages_min' => 5,
        'delay_between_messages_max' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Reply Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic reply functionality.
    |
    */

    'auto_reply' => [
        'cooldown_minutes' => 60,
        'case_sensitive' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for scheduled reminders.
    |
    */

    'reminders' => [
        'process_time' => '07:00', // WIB timezone
        'duplicate_prevention_hours' => 24,
        'supported_types' => [
            'spp_due',
            'invoice_unpaid',
            'booking_tomorrow',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for system alerts and monitoring.
    |
    */

    'alerts' => [
        'gateway_health_check_interval_minutes' => 5,
        'failed_jobs_spike_threshold' => 50,
        'failed_jobs_spike_window_minutes' => 60,
        'quota_warning_percentage' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-layered protection settings to prevent WhatsApp account blocking
    | and IP throttling. Covers device creation rate limiting, graceful
    | session restoration, and exponential backoff for connection retries.
    |
    */

    'gateway_protection' => [
        'device_creation' => [
            'max_attempts' => (int) env('WA_GATEWAY_DEVICE_CREATION_MAX_ATTEMPTS', 3),
            'window_seconds' => (int) env('WA_GATEWAY_DEVICE_CREATION_WINDOW_SECONDS', 300),
        ],
        'session_restore' => [
            'delay_between_sessions_ms' => (int) env('WA_GATEWAY_SESSION_RESTORE_DELAY_MS', 5000),
            'max_concurrent_restorations' => (int) env('WA_GATEWAY_SESSION_RESTORE_MAX_CONCURRENT', 3),
        ],
        'backoff' => [
            'initial_delay_ms' => (int) env('WA_GATEWAY_BACKOFF_INITIAL_DELAY_MS', 5000),
            'max_delay_ms' => (int) env('WA_GATEWAY_BACKOFF_MAX_DELAY_MS', 300000),
            'multiplier' => (float) env('WA_GATEWAY_BACKOFF_MULTIPLIER', 2),
            'jitter_factor' => (float) env('WA_GATEWAY_BACKOFF_JITTER_FACTOR', 0.3),
            'max_retries' => (int) env('WA_GATEWAY_BACKOFF_MAX_RETRIES', 10),
        ],
    ],

];
