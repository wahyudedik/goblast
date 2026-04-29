<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PaymentRecordedNotification;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BillingService::class);
    }

    // ── recordPayment() ─────────────────────────────────────────────

    public function test_record_payment_creates_invoice(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
            'notes' => 'Transfer BCA',
        ];

        $invoice = $this->service->recordPayment($tenant, $plan, $paymentData, $superadmin);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($tenant->id, $invoice->tenant_id);
        $this->assertEquals($plan->id, $invoice->plan_id);
        $this->assertEquals($superadmin->id, $invoice->recorded_by);
        $this->assertEquals('50000.00', $invoice->amount);
        $this->assertEquals(30, $invoice->duration_days);
        $this->assertEquals('2025-01-15', $invoice->paid_at->format('Y-m-d'));
        $this->assertEquals('Transfer BCA', $invoice->notes);
    }

    public function test_record_payment_creates_invoice_without_notes(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 150000,
            'duration_days' => 30,
            'paid_at' => '2025-02-01',
        ];

        $invoice = $this->service->recordPayment($tenant, $plan, $paymentData, $superadmin);

        $this->assertNull($invoice->notes);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'notes' => null,
        ]);
    }

    public function test_record_payment_sends_notification_to_tenant_admin(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $adminUser = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        $this->service->recordPayment($tenant, $plan, $paymentData, $superadmin);

        Notification::assertSentTo($adminUser, PaymentRecordedNotification::class);
    }

    public function test_record_payment_does_not_fail_when_no_admin_user(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        // No admin user for this tenant — should not throw
        $invoice = $this->service->recordPayment($tenant, $plan, $paymentData, $superadmin);

        $this->assertInstanceOf(Invoice::class, $invoice);
        Notification::assertNothingSent();
    }

    // ── activateSubscription() ──────────────────────────────────────

    public function test_activate_subscription_creates_invoice_and_subscription(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'trial']);
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        $subscription = $this->service->activateSubscription($tenant, $plan, $paymentData, $superadmin);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($tenant->id, $subscription->tenant_id);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals($plan->message_quota, $subscription->message_quota_limit);
        $this->assertEquals(0, $subscription->message_quota_used);

        // Invoice should be created and linked
        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::first();
        $this->assertEquals($subscription->id, $invoice->subscription_id);
    }

    public function test_activate_subscription_updates_tenant_status_to_active(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'expired']);
        $plan = Plan::factory()->pro()->create();
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 150000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        $this->service->activateSubscription($tenant, $plan, $paymentData, $superadmin);

        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_activate_subscription_expires_old_active_subscription(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $oldPlan = Plan::factory()->starter()->create();
        $newPlan = Plan::factory()->pro()->create();
        $superadmin = User::factory()->superadmin()->create();

        $oldSubscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $oldPlan->id,
        ]);

        $paymentData = [
            'amount' => 150000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        $newSubscription = $this->service->activateSubscription($tenant, $newPlan, $paymentData, $superadmin);

        $this->assertEquals('expired', $oldSubscription->fresh()->status);
        $this->assertEquals('active', $newSubscription->status);
    }

    // ── extendSubscription() ────────────────────────────────────────

    public function test_extend_subscription_creates_invoice_and_extends(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();
        $originalEndsAt = now()->addDays(10);

        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'ends_at' => $originalEndsAt,
        ]);

        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        $invoice = $this->service->extendSubscription($tenant, $plan, $paymentData, $superadmin);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($subscription->id, $invoice->subscription_id);
        $this->assertDatabaseCount('invoices', 1);

        // Subscription should be extended by 30 days
        $this->assertTrue(
            $subscription->fresh()->ends_at->isSameDay($originalEndsAt->copy()->addDays(30))
        );
    }

    public function test_extend_subscription_handles_no_active_subscription(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'expired']);
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        $paymentData = [
            'amount' => 50000,
            'duration_days' => 30,
            'paid_at' => '2025-01-15',
        ];

        $invoice = $this->service->extendSubscription($tenant, $plan, $paymentData, $superadmin);

        // Invoice should still be created
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertNull($invoice->subscription_id);
    }

    // ── getRevenue() ────────────────────────────────────────────────

    public function test_get_revenue_returns_total_for_period(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 50000,
            'paid_at' => '2025-01-10',
        ]);

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 150000,
            'paid_at' => '2025-01-20',
        ]);

        $result = $this->service->getRevenue(
            now()->parse('2025-01-01'),
            now()->parse('2025-01-31'),
        );

        $this->assertEquals('200000.00', $result['total']);
        $this->assertEquals(2, $result['count']);
    }

    public function test_get_revenue_filters_by_plan(): void
    {
        $tenant = Tenant::factory()->create();
        $starterPlan = Plan::factory()->starter()->create();
        $proPlan = Plan::factory()->pro()->create();
        $superadmin = User::factory()->superadmin()->create();

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $starterPlan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 50000,
            'paid_at' => '2025-01-10',
        ]);

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $proPlan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 150000,
            'paid_at' => '2025-01-15',
        ]);

        $result = $this->service->getRevenue(
            now()->parse('2025-01-01'),
            now()->parse('2025-01-31'),
            plan: $proPlan,
        );

        $this->assertEquals('150000.00', $result['total']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_get_revenue_filters_by_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        Invoice::factory()->create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 50000,
            'paid_at' => '2025-01-10',
        ]);

        Invoice::factory()->create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 100000,
            'paid_at' => '2025-01-15',
        ]);

        $result = $this->service->getRevenue(
            now()->parse('2025-01-01'),
            now()->parse('2025-01-31'),
            tenant: $tenantA,
        );

        $this->assertEquals('50000.00', $result['total']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_get_revenue_excludes_invoices_outside_period(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 50000,
            'paid_at' => '2025-01-10',
        ]);

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 100000,
            'paid_at' => '2025-02-15',
        ]);

        $result = $this->service->getRevenue(
            now()->parse('2025-01-01'),
            now()->parse('2025-01-31'),
        );

        $this->assertEquals('50000.00', $result['total']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_get_revenue_returns_zero_when_no_invoices(): void
    {
        $result = $this->service->getRevenue(
            now()->parse('2025-01-01'),
            now()->parse('2025-01-31'),
        );

        $this->assertEquals('0.00', $result['total']);
        $this->assertEquals(0, $result['count']);
    }

    public function test_get_revenue_filters_by_both_plan_and_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $starterPlan = Plan::factory()->starter()->create();
        $proPlan = Plan::factory()->pro()->create();
        $superadmin = User::factory()->superadmin()->create();

        // TenantA + Starter
        Invoice::factory()->create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $starterPlan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 50000,
            'paid_at' => '2025-01-10',
        ]);

        // TenantA + Pro
        Invoice::factory()->create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $proPlan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 150000,
            'paid_at' => '2025-01-12',
        ]);

        // TenantB + Starter
        Invoice::factory()->create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $starterPlan->id,
            'recorded_by' => $superadmin->id,
            'amount' => 50000,
            'paid_at' => '2025-01-15',
        ]);

        $result = $this->service->getRevenue(
            now()->parse('2025-01-01'),
            now()->parse('2025-01-31'),
            plan: $starterPlan,
            tenant: $tenantA,
        );

        $this->assertEquals('50000.00', $result['total']);
        $this->assertEquals(1, $result['count']);
    }
}
