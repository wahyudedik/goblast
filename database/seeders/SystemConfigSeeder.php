<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'default_rate_limit_per_hour',
                'value' => '200',
                'type' => 'integer',
                'description' => 'Default rate limit for message sending per device per hour',
            ],
            [
                'key' => 'default_delay_min_seconds',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Minimum delay between messages in seconds',
            ],
            [
                'key' => 'default_delay_max_seconds',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum delay between messages in seconds',
            ],
            [
                'key' => 'trial_duration_days',
                'value' => '14',
                'type' => 'integer',
                'description' => 'Duration of trial period in days',
            ],
            [
                'key' => 'log_retention_days',
                'value' => '90',
                'type' => 'integer',
                'description' => 'Number of days to retain message logs',
            ],
            [
                'key' => 'system_log_retention_days',
                'value' => '180',
                'type' => 'integer',
                'description' => 'Number of days to retain system logs',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable or disable maintenance mode',
            ],
            [
                'key' => 'max_csv_file_size_mb',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Maximum CSV file size in MB for broadcasts',
            ],
            [
                'key' => 'device_health_check_interval_seconds',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Interval for device health checks in seconds',
            ],
            [
                'key' => 'gateway_timeout_seconds',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Timeout for Baileys Gateway requests in seconds',
            ],
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
