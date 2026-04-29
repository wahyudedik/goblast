<?php

namespace App\Services\Contracts;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;

interface SubscriptionServiceInterface
{
    /**
     * Activate a new subscription for a tenant from an invoice.
     *
     * Creates a subscription with the specified plan and duration,
     * snapshots quota from the plan, resets quota via QuotaService,
     * and updates the tenant status to 'active'.
     */
    public function activate(Tenant $tenant, Plan $plan, int $durationDays, Invoice $invoice): Subscription;

    /**
     * Extend the current active subscription by additional days.
     */
    public function extend(Tenant $tenant, int $additionalDays): void;

    /**
     * Check if a feature is allowed by the tenant's current plan.
     *
     * @param  string  $feature  Feature name: 'reminder', 'api', or 'multi_device'
     */
    public function isFeatureAllowed(Tenant $tenant, string $feature): bool;

    /**
     * Check all subscriptions for expiry, update expired ones,
     * update tenant status, and send notifications.
     */
    public function checkExpiry(): void;
}
