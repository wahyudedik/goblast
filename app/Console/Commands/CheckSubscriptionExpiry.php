<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('subscription:check-expiry')]
#[Description('Check for expiring and expired subscriptions')]
class CheckSubscriptionExpiry extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting subscription expiry check...');

        // Query subscriptions that will expire in 7 days
        $expiringIn7Days = Subscription::with(['tenant', 'plan'])
            ->where('status', 'active')
            ->whereDate('ends_at', '=', now()->addDays(7)->toDateString())
            ->get();

        $this->info("Found {$expiringIn7Days->count()} subscription(s) expiring in 7 days");

        foreach ($expiringIn7Days as $subscription) {
            // Send email notification to tenant
            $tenant = $subscription->tenant;
            $primaryUser = $tenant->users()->where('role', 'admin')->first();

            if ($primaryUser) {
                try {
                    $primaryUser->notify(new SubscriptionExpiringNotification($subscription, 7));
                    $this->info("Sent expiry notification to {$tenant->name} (expires: {$subscription->ends_at->toDateString()})");
                } catch (\Throwable $e) {
                    $this->error("Failed to send notification to {$tenant->name}: {$e->getMessage()}");
                    Log::error('Failed to send subscription expiring notification', [
                        'tenant_id' => $tenant->id,
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Query subscriptions that have already expired
        $expired = Subscription::with(['tenant', 'plan'])
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        $this->info("Found {$expired->count()} expired subscription(s)");

        foreach ($expired as $subscription) {
            // Update subscription status to expired
            $subscription->update(['status' => 'expired']);

            // Update tenant status to expired
            $tenant = $subscription->tenant;
            $tenant->update(['status' => 'expired']);

            $this->warn("Expired subscription for {$tenant->name} (ended: {$subscription->ends_at->toDateString()})");

            // Send email notification for expired subscription
            $primaryUser = $tenant->users()->where('role', 'admin')->first();

            if ($primaryUser) {
                try {
                    $primaryUser->notify(new SubscriptionExpiredNotification($subscription));
                    $this->info("Sent expired notification to {$tenant->name}");
                } catch (\Throwable $e) {
                    $this->error("Failed to send expired notification to {$tenant->name}: {$e->getMessage()}");
                    Log::error('Failed to send subscription expired notification', [
                        'tenant_id' => $tenant->id,
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $summary = [
            'expiring_in_7_days' => $expiringIn7Days->count(),
            'expired' => $expired->count(),
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::info('Subscription expiry check completed', $summary);

        $this->newLine();
        $this->info('Subscription expiry check completed:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Expiring in 7 days', $summary['expiring_in_7_days']],
                ['Expired', $summary['expired']],
            ]
        );

        return self::SUCCESS;
    }
}
