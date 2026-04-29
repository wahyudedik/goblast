<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PaymentRecordedNotification;
use App\Services\Contracts\BillingServiceInterface;
use App\Services\Contracts\SubscriptionServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService implements BillingServiceInterface
{
    public function __construct(
        private SubscriptionServiceInterface $subscriptionService,
    ) {}

    /**
     * Record a manual payment and create an invoice.
     *
     * Creates an Invoice record from the payment data without
     * activating or extending any subscription.
     */
    public function recordPayment(Tenant $tenant, Plan $plan, array $paymentData, User $recordedBy): Invoice
    {
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $recordedBy->id,
            'amount' => $paymentData['amount'],
            'duration_days' => $paymentData['duration_days'],
            'paid_at' => $paymentData['paid_at'],
            'notes' => $paymentData['notes'] ?? null,
        ]);

        Log::info('Payment recorded', [
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => $paymentData['amount'],
            'recorded_by' => $recordedBy->id,
        ]);

        $this->sendBillingNotification($tenant, $invoice);

        return $invoice;
    }

    /**
     * Record a payment and activate a new subscription for the tenant.
     *
     * Creates an invoice, then delegates to SubscriptionService::activate()
     * to create the subscription, expire old ones, and reset quota.
     */
    public function activateSubscription(Tenant $tenant, Plan $plan, array $paymentData, User $recordedBy): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $paymentData, $recordedBy) {
            $invoice = $this->recordPayment($tenant, $plan, $paymentData, $recordedBy);

            $subscription = $this->subscriptionService->activate(
                $tenant,
                $plan,
                $paymentData['duration_days'],
                $invoice,
            );

            Log::info('Subscription activated via billing', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
            ]);

            return $subscription;
        });
    }

    /**
     * Record a payment and extend the tenant's current active subscription.
     *
     * Creates an invoice, links it to the active subscription,
     * then delegates to SubscriptionService::extend().
     */
    public function extendSubscription(Tenant $tenant, Plan $plan, array $paymentData, User $recordedBy): Invoice
    {
        return DB::transaction(function () use ($tenant, $plan, $paymentData, $recordedBy) {
            $invoice = $this->recordPayment($tenant, $plan, $paymentData, $recordedBy);

            // Link invoice to the current active subscription
            $activeSubscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->latest('ends_at')
                ->first();

            if ($activeSubscription) {
                $invoice->update(['subscription_id' => $activeSubscription->id]);
            }

            $this->subscriptionService->extend($tenant, $paymentData['duration_days']);

            Log::info('Subscription extended via billing', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'additional_days' => $paymentData['duration_days'],
            ]);

            return $invoice;
        });
    }

    /**
     * Calculate total revenue for a given period, optionally filtered by plan or tenant.
     *
     * @return array{total: string, count: int}
     */
    public function getRevenue(Carbon $from, Carbon $to, ?Plan $plan = null, ?Tenant $tenant = null): array
    {
        $query = Invoice::query()
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()]);

        if ($plan) {
            $query->where('plan_id', $plan->id);
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        $result = $query->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->first();

        return [
            'total' => number_format((float) $result->total, 2, '.', ''),
            'count' => (int) $result->count,
        ];
    }

    /**
     * Send a billing notification to the tenant's admin user.
     */
    protected function sendBillingNotification(Tenant $tenant, Invoice $invoice): void
    {
        $adminUser = $tenant->users()
            ->where('role', 'admin')
            ->first();

        if (! $adminUser) {
            return;
        }

        try {
            $adminUser->notify(new PaymentRecordedNotification($invoice));

            Log::info('Billing notification sent', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'user_id' => $adminUser->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send billing notification', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
