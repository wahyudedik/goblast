<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringNotification;
use App\Services\Contracts\QuotaServiceInterface;
use App\Services\Contracts\SubscriptionServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private QuotaServiceInterface $quotaService,
    ) {}

    /**
     * Activate a new subscription for a tenant from an invoice.
     *
     * Expires any existing active subscription, creates a new one with
     * quota snapshot from the plan, links the invoice, resets quota,
     * and updates the tenant status to 'active'.
     */
    public function activate(Tenant $tenant, Plan $plan, int $durationDays, Invoice $invoice): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $durationDays, $invoice) {
            // Expire any existing active subscriptions for this tenant
            $tenant->subscriptions()
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            // Create new subscription with quota snapshot from plan
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'message_quota_used' => 0,
                'message_quota_limit' => $plan->message_quota,
                'starts_at' => now(),
                'ends_at' => now()->addDays($durationDays),
            ]);

            // Link the invoice to the new subscription
            $invoice->update(['subscription_id' => $subscription->id]);

            // Update tenant status to active
            $tenant->update(['status' => 'active']);

            // Reset quota via QuotaService (ensures clean state)
            $this->quotaService->reset($tenant);

            Log::info('Subscription activated', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'duration_days' => $durationDays,
                'ends_at' => $subscription->ends_at->toDateTimeString(),
            ]);

            return $subscription;
        });
    }

    /**
     * Extend the current active subscription by additional days.
     *
     * If no active subscription exists, the operation is skipped with a warning log.
     */
    public function extend(Tenant $tenant, int $additionalDays): void
    {
        DB::transaction(function () use ($tenant, $additionalDays) {
            $subscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                Log::warning('Cannot extend subscription: no active subscription found', [
                    'tenant_id' => $tenant->id,
                ]);

                return;
            }

            $subscription->update([
                'ends_at' => $subscription->ends_at->addDays($additionalDays),
            ]);

            Log::info('Subscription extended', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'additional_days' => $additionalDays,
                'new_ends_at' => $subscription->fresh()->ends_at->toDateTimeString(),
            ]);
        });
    }

    /**
     * Check if a feature is allowed by the tenant's current plan.
     *
     * Maps feature names to plan boolean attributes:
     * - 'reminder' → has_reminder
     * - 'api' → has_api
     * - 'multi_device' → has_multi_device
     *
     * Returns false if no active subscription exists or the feature name is invalid.
     */
    public function isFeatureAllowed(Tenant $tenant, string $feature): bool
    {
        $featureMap = [
            'reminder' => 'has_reminder',
            'api' => 'has_api',
            'multi_device' => 'has_multi_device',
        ];

        if (! isset($featureMap[$feature])) {
            return false;
        }

        $subscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('plan')
            ->first();

        if (! $subscription) {
            return false;
        }

        return (bool) $subscription->plan->{$featureMap[$feature]};
    }

    /**
     * Check all subscriptions for expiry.
     *
     * 1. Sends expiring notifications for subscriptions ending in 7 days
     * 2. Expires active subscriptions past their end date
     * 3. Updates tenant status to 'expired'
     * 4. Sends expired notifications
     */
    public function checkExpiry(): void
    {
        $this->notifyExpiringSubscriptions();
        $this->expireSubscriptions();
    }

    /**
     * Send notifications for subscriptions expiring in 7 days.
     */
    protected function notifyExpiringSubscriptions(): void
    {
        $expiringSubscriptions = Subscription::with(['tenant', 'plan'])
            ->where('status', 'active')
            ->whereDate('ends_at', '=', now()->addDays(7)->toDateString())
            ->get();

        foreach ($expiringSubscriptions as $subscription) {
            $primaryUser = $subscription->tenant->users()
                ->where('role', 'admin')
                ->first();

            if (! $primaryUser) {
                continue;
            }

            try {
                $primaryUser->notify(new SubscriptionExpiringNotification($subscription, 7));

                Log::info('Subscription expiring notification sent', [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'expires_at' => $subscription->ends_at->toDateString(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send subscription expiring notification', [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Expire active subscriptions past their end date and update tenant status.
     */
    protected function expireSubscriptions(): void
    {
        $expiredSubscriptions = Subscription::with(['tenant', 'plan'])
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);
            $subscription->tenant->update(['status' => 'expired']);

            Log::info('Subscription expired', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'ended_at' => $subscription->ends_at->toDateString(),
            ]);

            $primaryUser = $subscription->tenant->users()
                ->where('role', 'admin')
                ->first();

            if (! $primaryUser) {
                continue;
            }

            try {
                $primaryUser->notify(new SubscriptionExpiredNotification($subscription));

                Log::info('Subscription expired notification sent', [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send subscription expired notification', [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
