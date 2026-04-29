<?php

namespace Tests\Unit\Services;

use App\Exceptions\QuotaExceededException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotaService $quotaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = new QuotaService;
    }

    #[Test]
    public function it_gets_remaining_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 30,
        ]);

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        $this->assertEquals(70, $remaining);
    }

    #[Test]
    public function it_returns_zero_when_no_active_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        $this->assertEquals(0, $remaining);
    }

    #[Test]
    public function it_returns_negative_one_for_unlimited_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => null]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => null,
        ]);

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        $this->assertEquals(-1, $remaining);
    }

    #[Test]
    public function it_decrements_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 0,
        ]);

        $this->quotaService->decrement($tenant, 5);

        $subscription->refresh();
        $this->assertEquals(5, $subscription->message_quota_used);
    }

    #[Test]
    public function it_throws_exception_when_decrementing_exceeds_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 98,
        ]);

        $this->expectException(QuotaExceededException::class);

        $this->quotaService->decrement($tenant, 5);
    }

    #[Test]
    public function it_allows_decrement_with_unlimited_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => null]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => null,
        ]);

        // Should not throw exception
        $this->quotaService->decrement($tenant, 1000);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_resets_quota(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 50,
        ]);

        $this->quotaService->reset($tenant);

        $subscription->refresh();
        $this->assertEquals(0, $subscription->message_quota_used);
    }

    #[Test]
    public function it_checks_if_quota_is_exhausted(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 100,
        ]);

        $isExhausted = $this->quotaService->isExhausted($tenant);

        $this->assertTrue($isExhausted);
    }

    #[Test]
    public function it_checks_if_quota_is_not_exhausted(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 50,
        ]);

        $isExhausted = $this->quotaService->isExhausted($tenant);

        $this->assertFalse($isExhausted);
    }

    #[Test]
    public function it_checks_if_quota_is_unlimited(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => null]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => null,
        ]);

        $isUnlimited = $this->quotaService->isUnlimited($tenant);

        $this->assertTrue($isUnlimited);
    }

    #[Test]
    public function it_checks_if_quota_is_not_unlimited(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
        ]);

        $isUnlimited = $this->quotaService->isUnlimited($tenant);

        $this->assertFalse($isUnlimited);
    }

    #[Test]
    public function it_throws_exception_when_no_active_subscription_on_decrement(): void
    {
        $tenant = Tenant::factory()->create();

        $this->expectException(QuotaExceededException::class);

        $this->quotaService->decrement($tenant);
    }

    #[Test]
    public function it_handles_exact_quota_match(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 95,
        ]);

        // Should succeed when decrementing exactly the remaining quota
        $this->quotaService->decrement($tenant, 5);

        $subscription->refresh();
        $this->assertEquals(100, $subscription->message_quota_used);
    }

    #[Test]
    public function it_returns_zero_when_quota_exhausted(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 100,
        ]);

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        $this->assertEquals(0, $remaining);
    }

    #[Test]
    public function it_ignores_inactive_subscriptions(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
            'message_quota_limit' => 100,
            'message_quota_used' => 50,
        ]);

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        $this->assertEquals(0, $remaining);
    }

    #[Test]
    public function it_handles_multiple_decrements(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 0,
        ]);

        $this->quotaService->decrement($tenant, 10);
        $this->quotaService->decrement($tenant, 20);
        $this->quotaService->decrement($tenant, 15);

        $subscription->refresh();
        $this->assertEquals(45, $subscription->message_quota_used);
    }

    #[Test]
    public function it_throws_exception_with_correct_remaining_amount(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 95,
        ]);

        try {
            $this->quotaService->decrement($tenant, 10);
            $this->fail('Expected QuotaExceededException');
        } catch (QuotaExceededException $e) {
            $this->assertEquals(5, $e->remaining);
            $this->assertEquals(10, $e->required);
        }
    }
}
