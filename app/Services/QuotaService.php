<?php

namespace App\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\Tenant;
use App\Services\Contracts\QuotaServiceInterface;
use Illuminate\Support\Facades\DB;

class QuotaService implements QuotaServiceInterface
{
    /**
     * Get the remaining quota for a tenant.
     *
     * @return int The number of messages remaining, or -1 if unlimited
     */
    public function getRemainingQuota(Tenant $tenant): int
    {
        $subscription = $tenant->subscriptions()
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            return 0;
        }

        // Unlimited quota
        if ($subscription->message_quota_limit === null) {
            return -1;
        }

        return max(0, $subscription->message_quota_limit - $subscription->message_quota_used);
    }

    /**
     * Decrement the quota for a tenant using atomic database transaction.
     *
     * Uses pessimistic locking (lockForUpdate) to ensure thread-safe quota tracking
     * and prevent race conditions in concurrent environments.
     *
     * @throws QuotaExceededException
     */
    public function decrement(Tenant $tenant, int $amount = 1): void
    {
        DB::transaction(function () use ($tenant, $amount) {
            $subscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw new QuotaExceededException(0, $amount, 'No active subscription found');
            }

            // Unlimited quota - no need to decrement
            if ($subscription->message_quota_limit === null) {
                return;
            }

            // Check if quota is available (within the lock)
            $remaining = $subscription->message_quota_limit - $subscription->message_quota_used;
            if ($remaining < $amount) {
                throw new QuotaExceededException($remaining, $amount, 'Insufficient quota');
            }

            // Atomically increment the used quota
            $subscription->increment('message_quota_used', $amount);
        }, attempts: 5);
    }

    /**
     * Reset the quota for a tenant to zero.
     *
     * Typically called when a subscription is renewed or extended.
     */
    public function reset(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $subscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return;
            }

            $subscription->update(['message_quota_used' => 0]);
        }, attempts: 5);
    }

    /**
     * Check if the tenant's quota is exhausted.
     *
     * @return bool True if quota is exhausted (0 remaining and not unlimited)
     */
    public function isExhausted(Tenant $tenant): bool
    {
        return $this->getRemainingQuota($tenant) === 0;
    }

    /**
     * Check if the tenant has unlimited quota.
     *
     * @return bool True if the tenant's plan has unlimited quota
     */
    public function isUnlimited(Tenant $tenant): bool
    {
        return $this->getRemainingQuota($tenant) === -1;
    }
}
