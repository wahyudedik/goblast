<?php

namespace App\Services\Contracts;

use App\Exceptions\QuotaExceededException;
use App\Models\Tenant;

interface QuotaServiceInterface
{
    /**
     * Get the remaining quota for a tenant.
     *
     * @return int The number of messages remaining, or -1 if unlimited
     */
    public function getRemainingQuota(Tenant $tenant): int;

    /**
     * Decrement the quota for a tenant.
     *
     * @param  int  $amount  The number of messages to decrement
     *
     * @throws QuotaExceededException
     */
    public function decrement(Tenant $tenant, int $amount = 1): void;

    /**
     * Reset the quota for a tenant to the plan limit.
     */
    public function reset(Tenant $tenant): void;

    /**
     * Check if the tenant's quota is exhausted.
     *
     * @return bool True if quota is exhausted (0 remaining and not unlimited)
     */
    public function isExhausted(Tenant $tenant): bool;

    /**
     * Check if the tenant has unlimited quota.
     *
     * @return bool True if the tenant's plan has unlimited quota
     */
    public function isUnlimited(Tenant $tenant): bool;
}
