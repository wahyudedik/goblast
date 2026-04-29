<?php

namespace App\Services\Contracts;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

interface BillingServiceInterface
{
    /**
     * Record a manual payment and create an invoice.
     *
     * @param  array{
     *     amount: numeric-string|float,
     *     duration_days: int,
     *     paid_at: string,
     *     notes?: string|null,
     * }  $paymentData
     */
    public function recordPayment(Tenant $tenant, Plan $plan, array $paymentData, User $recordedBy): Invoice;

    /**
     * Record a payment and activate a new subscription for the tenant.
     *
     * @param  array{
     *     amount: numeric-string|float,
     *     duration_days: int,
     *     paid_at: string,
     *     notes?: string|null,
     * }  $paymentData
     */
    public function activateSubscription(Tenant $tenant, Plan $plan, array $paymentData, User $recordedBy): Subscription;

    /**
     * Record a payment and extend the tenant's current active subscription.
     *
     * @param  array{
     *     amount: numeric-string|float,
     *     duration_days: int,
     *     paid_at: string,
     *     notes?: string|null,
     * }  $paymentData
     */
    public function extendSubscription(Tenant $tenant, Plan $plan, array $paymentData, User $recordedBy): Invoice;

    /**
     * Calculate total revenue for a given period, optionally filtered by plan or tenant.
     *
     * @return array{
     *     total: string,
     *     count: int,
     * }
     */
    public function getRevenue(Carbon $from, Carbon $to, ?Plan $plan = null, ?Tenant $tenant = null): array;
}
