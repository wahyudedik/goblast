<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringNotification;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionService::class);
    }

    // ── activate() ──────────────────────────────────────────────────

    public function test_activate_creates_subscription_with_correct_attributes(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial']);
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'duration_days' => 30,
        ]);

        $subscription = $this->service->activate($tenant, $plan, 30, $invoice);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($tenant->id, $subscription->tenant_id);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(0, $subscription->message_quota_used);
        $this->assertEquals($plan->message_quota, $subscription->message_quota_limit);
        $this->assertTrue($subscription->starts_at->isToday());
        $this->assertTrue($subscription->ends_at->isSameDay(now()->addDays(30)));
    }

    public function test_activate_updates_tenant_status_to_active(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'expired']);
        $plan = Plan::factory()->pro()->create();
        $superadmin = User::factory()->superadmin()->create();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'duration_days' => 30,
        ]);

        $this->service->activate($tenant, $plan, 30, $invoice);

        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_activate_links_invoice_to_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'duration_days' => 30,
        ]);

        $subscription = $this->service->activate($tenant, $plan, 30, $invoice);

        $this->assertEquals($subscription->id, $invoice->fresh()->subscription_id);
    }

    public function test_activate_expires_existing_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        $oldSubscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $newPlan = Plan::factory()->pro()->create();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $newPlan->id,
            'recorded_by' => $superadmin->id,
            'duration_days' => 30,
        ]);

        $newSubscription = $this->service->activate($tenant, $newPlan, 30, $invoice);

        $this->assertEquals('expired', $oldSubscription->fresh()->status);
        $this->assertEquals('active', $newSubscription->status);
    }

    public function test_activate_sets_unlimited_quota_for_business_plan(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->business()->create();
        $superadmin = User::factory()->superadmin()->create();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'duration_days' => 30,
        ]);

        $subscription = $this->service->activate($tenant, $plan, 30, $invoice);

        $this->assertNull($subscription->message_quota_limit);
    }

    public function test_activate_resets_quota_via_quota_service(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $superadmin = User::factory()->superadmin()->create();

        // Create existing subscription with used quota
        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'message_quota_used' => 50,
            'message_quota_limit' => 100,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'recorded_by' => $superadmin->id,
            'duration_days' => 30,
        ]);

        $subscription = $this->service->activate($tenant, $plan, 30, $invoice);

        // The new subscription starts with 0 used quota
        $this->assertEquals(0, $subscription->message_quota_used);
    }

    // ── extend() ────────────────────────────────────────────────────

    public function test_extend_adds_days_to_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $originalEndsAt = now()->addDays(10);

        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => $originalEndsAt,
        ]);

        $this->service->extend($tenant, 30);

        $subscription = $tenant->subscriptions()->where('status', 'active')->first();
        $this->assertTrue($subscription->ends_at->isSameDay($originalEndsAt->copy()->addDays(30)));
    }

    public function test_extend_does_nothing_when_no_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        // Should not throw, just log a warning
        $this->service->extend($tenant, 30);

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_extend_does_not_affect_expired_subscriptions(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();

        $expiredSubscription = Subscription::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $originalEndsAt = $expiredSubscription->ends_at->copy();

        $this->service->extend($tenant, 30);

        $this->assertTrue($expiredSubscription->fresh()->ends_at->isSameDay($originalEndsAt));
    }

    // ── isFeatureAllowed() ──────────────────────────────────────────

    public function test_is_feature_allowed_returns_true_for_plan_with_feature(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->pro()->create();

        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertTrue($this->service->isFeatureAllowed($tenant, 'reminder'));
    }

    public function test_is_feature_allowed_returns_false_for_plan_without_feature(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();

        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertFalse($this->service->isFeatureAllowed($tenant, 'reminder'));
        $this->assertFalse($this->service->isFeatureAllowed($tenant, 'api'));
        $this->assertFalse($this->service->isFeatureAllowed($tenant, 'multi_device'));
    }

    public function test_is_feature_allowed_returns_false_when_no_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertFalse($this->service->isFeatureAllowed($tenant, 'reminder'));
    }

    public function test_is_feature_allowed_returns_false_for_expired_subscription(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->business()->create();

        Subscription::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertFalse($this->service->isFeatureAllowed($tenant, 'api'));
    }

    public function test_is_feature_allowed_returns_false_for_invalid_feature_name(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->business()->create();

        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertFalse($this->service->isFeatureAllowed($tenant, 'nonexistent_feature'));
    }

    public function test_is_feature_allowed_business_plan_has_all_features(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->business()->create();

        Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertTrue($this->service->isFeatureAllowed($tenant, 'reminder'));
        $this->assertTrue($this->service->isFeatureAllowed($tenant, 'api'));
        $this->assertTrue($this->service->isFeatureAllowed($tenant, 'multi_device'));
    }

    // ── checkExpiry() ───────────────────────────────────────────────

    public function test_check_expiry_sends_notification_for_subscriptions_expiring_in_7_days(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->starter()->create();
        $adminUser = User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(7),
        ]);

        $this->service->checkExpiry();

        Notification::assertSentTo($adminUser, SubscriptionExpiringNotification::class);
    }

    public function test_check_expiry_expires_past_due_subscriptions(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create();
        User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
        ]);

        $this->service->checkExpiry();

        $this->assertEquals('expired', $subscription->fresh()->status);
        $this->assertEquals('expired', $tenant->fresh()->status);
    }

    public function test_check_expiry_sends_expired_notification(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create();
        $adminUser = User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
        ]);

        $this->service->checkExpiry();

        Notification::assertSentTo($adminUser, SubscriptionExpiredNotification::class);
    }

    public function test_check_expiry_does_not_affect_non_expired_subscriptions(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create();

        $subscription = Subscription::factory()->active()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'ends_at' => now()->addDays(15),
        ]);

        $this->service->checkExpiry();

        $this->assertEquals('active', $subscription->fresh()->status);
        $this->assertEquals('active', $tenant->fresh()->status);
    }

    public function test_check_expiry_skips_notification_when_no_admin_user(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::factory()->starter()->create();

        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
        ]);

        // No admin user created for this tenant
        $this->service->checkExpiry();

        // Subscription should still be expired even without notification
        Notification::assertNothingSent();
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => 'expired',
        ]);
    }
}
