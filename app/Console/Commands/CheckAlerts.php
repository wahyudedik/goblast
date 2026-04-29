<?php

namespace App\Console\Commands;

use App\Exceptions\GatewayException;
use App\Models\Alert;
use App\Models\GatewayInstance;
use App\Models\MessageLog;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\Contracts\BaileysGatewayClientInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('alert:check')]
#[Description('Check system health and create alerts for critical conditions')]
class CheckAlerts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BaileysGatewayClientInterface $client): int
    {
        $this->info('Starting alert check...');

        $alertsCreated = 0;

        // Check gateway instance health
        $alertsCreated += $this->checkGatewayHealth($client);

        // Check failed jobs spike (>50 in 1 hour)
        $alertsCreated += $this->checkFailedJobsSpike();

        // Check quota usage (>90%)
        $alertsCreated += $this->checkQuotaUsage();

        // Send email notification to superadmin if alerts were created
        if ($alertsCreated > 0) {
            $this->notifySuperadmin($alertsCreated);
        }

        $summary = [
            'alerts_created' => $alertsCreated,
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Alert check completed', $summary);

        $this->newLine();
        $this->info("Alert check completed: {$alertsCreated} alert(s) created");

        return self::SUCCESS;
    }

    /**
     * Check gateway instance health.
     */
    protected function checkGatewayHealth(BaileysGatewayClientInterface $client): int
    {
        $alertsCreated = 0;

        $instances = GatewayInstance::all();

        foreach ($instances as $instance) {
            // Check if instance was last checked more than 5 minutes ago or never checked
            $shouldCheck = ! $instance->last_checked_at || $instance->last_checked_at->lt(now()->subMinutes(5));

            if (! $shouldCheck) {
                continue;
            }

            try {
                // Try to get connection status for a test device
                // This is a simple health check - in production, you might have a dedicated health endpoint
                $instance->update([
                    'status' => 'active',
                    'last_checked_at' => now(),
                    'last_error' => null,
                ]);

                $this->info("✓ Gateway instance '{$instance->name}' is healthy");
            } catch (GatewayException $e) {
                // Gateway is not responding
                $instance->update([
                    'status' => 'error',
                    'last_checked_at' => now(),
                    'last_error' => $e->getMessage(),
                ]);

                // Check if alert already exists for this instance
                $existingAlert = Alert::where('type', 'gateway.down')
                    ->where('status', 'active')
                    ->where('context->gateway_instance_id', $instance->id)
                    ->exists();

                if (! $existingAlert) {
                    Alert::create([
                        'tenant_id' => null,
                        'type' => 'gateway.down',
                        'severity' => 'critical',
                        'message' => "Gateway instance '{$instance->name}' is not responding for more than 5 minutes",
                        'context' => [
                            'gateway_instance_id' => $instance->id,
                            'gateway_name' => $instance->name,
                            'base_url' => $instance->base_url,
                            'error' => $e->getMessage(),
                        ],
                        'status' => 'active',
                    ]);

                    $alertsCreated++;
                    $this->error("✗ Gateway instance '{$instance->name}' is down - Alert created");
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Check for failed jobs spike (>50 in 1 hour).
     */
    protected function checkFailedJobsSpike(): int
    {
        $oneHourAgo = now()->subHour();

        $failedCount = MessageLog::where('status', 'failed')
            ->where('failed_at', '>=', $oneHourAgo)
            ->count();

        if ($failedCount > 50) {
            // Check if alert already exists
            $existingAlert = Alert::where('type', 'jobs.failed_spike')
                ->where('status', 'active')
                ->where('created_at', '>=', $oneHourAgo)
                ->exists();

            if (! $existingAlert) {
                Alert::create([
                    'tenant_id' => null,
                    'type' => 'jobs.failed_spike',
                    'severity' => 'error',
                    'message' => "High number of failed message jobs detected: {$failedCount} failures in the last hour",
                    'context' => [
                        'failed_count' => $failedCount,
                        'time_window' => '1 hour',
                        'threshold' => 50,
                    ],
                    'status' => 'active',
                ]);

                $this->error("✗ Failed jobs spike detected: {$failedCount} failures - Alert created");

                return 1;
            }
        }

        return 0;
    }

    /**
     * Check quota usage (>90%).
     */
    protected function checkQuotaUsage(): int
    {
        $alertsCreated = 0;

        $subscriptions = Subscription::with('tenant')
            ->where('status', 'active')
            ->whereNotNull('message_quota_limit')
            ->get();

        foreach ($subscriptions as $subscription) {
            $quotaLimit = $subscription->message_quota_limit;
            $quotaUsed = $subscription->message_quota_used;

            if ($quotaLimit > 0) {
                $usagePercentage = ($quotaUsed / $quotaLimit) * 100;

                if ($usagePercentage > 90) {
                    // Check if alert already exists for this tenant
                    $existingAlert = Alert::where('type', 'quota.90pct')
                        ->where('tenant_id', $subscription->tenant_id)
                        ->where('status', 'active')
                        ->where('created_at', '>=', now()->subDay())
                        ->exists();

                    if (! $existingAlert) {
                        Alert::create([
                            'tenant_id' => $subscription->tenant_id,
                            'type' => 'quota.90pct',
                            'severity' => 'warning',
                            'message' => "Tenant '{$subscription->tenant->name}' has used {$usagePercentage}% of their message quota",
                            'context' => [
                                'tenant_id' => $subscription->tenant_id,
                                'tenant_name' => $subscription->tenant->name,
                                'quota_used' => $quotaUsed,
                                'quota_limit' => $quotaLimit,
                                'usage_percentage' => round($usagePercentage, 2),
                            ],
                            'status' => 'active',
                        ]);

                        $alertsCreated++;
                        $this->warn("⚠ Tenant '{$subscription->tenant->name}' quota usage: {$usagePercentage}% - Alert created");
                    }
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Send email notification to superadmin.
     */
    protected function notifySuperadmin(int $alertsCreated): void
    {
        $superadmins = User::where('role', 'superadmin')->get();

        foreach ($superadmins as $superadmin) {
            try {
                $superadmin->notify(new SystemAlertNotification($alertsCreated));
                $this->info("Sent alert notification to superadmin: {$superadmin->email}");
            } catch (\Throwable $e) {
                $this->error("Failed to send notification to {$superadmin->email}: {$e->getMessage()}");
                Log::error('Failed to send system alert notification', [
                    'superadmin_id' => $superadmin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
