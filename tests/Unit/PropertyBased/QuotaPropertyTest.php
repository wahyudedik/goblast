<?php

namespace Tests\Unit\PropertyBased;

use App\Exceptions\QuotaExceededException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Property-based tests for QuotaService correctness properties.
 *
 * These tests verify the following correctness properties:
 * 1. Quota never goes negative
 * 2. Quota decrements correctly for each message
 * 3. Quota resets on subscription renewal
 */
class QuotaPropertyTest extends TestCase
{
    use RefreshDatabase;

    private QuotaService $quotaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quotaService = new QuotaService;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 1: Quota never goes negative
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: getRemainingQuota() >= 0 for any valid tenant with limited quota.
     *
     * For any tenant with a limited quota subscription, the remaining quota
     * must always be non-negative (>= 0) or -1 for unlimited.
     */
    #[Test]
    #[DataProvider('quotaUsageScenarios')]
    public function remaining_quota_is_never_negative_for_limited_plans(
        int $quotaLimit,
        int $quotaUsed
    ): void {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => min($quotaUsed, $quotaLimit), // Ensure used doesn't exceed limit
        ]);

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        // Property: remaining quota is always >= 0 for limited plans
        $this->assertGreaterThanOrEqual(0, $remaining);
    }

    /**
     * Property: Decrement operation never results in negative quota.
     *
     * When attempting to decrement more than available, an exception is thrown
     * rather than allowing negative quota.
     */
    #[Test]
    #[DataProvider('decrementScenarios')]
    public function decrement_never_results_in_negative_quota(
        int $quotaLimit,
        int $quotaUsed,
        int $decrementAmount
    ): void {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => $quotaUsed,
        ]);

        $remaining = $quotaLimit - $quotaUsed;

        if ($decrementAmount > $remaining) {
            // Property: Exception thrown when decrement exceeds remaining
            $this->expectException(QuotaExceededException::class);
            $this->quotaService->decrement($tenant, $decrementAmount);
        } else {
            // Property: Successful decrement never results in negative
            $this->quotaService->decrement($tenant, $decrementAmount);
            $subscription->refresh();

            $newRemaining = $quotaLimit - $subscription->message_quota_used;
            $this->assertGreaterThanOrEqual(0, $newRemaining);
        }
    }

    /**
     * Property: After any sequence of decrements, quota remains non-negative.
     */
    #[Test]
    #[DataProvider('multipleDecrementSequences')]
    public function quota_remains_non_negative_after_multiple_decrements(
        int $quotaLimit,
        array $decrementSequence
    ): void {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => 0,
        ]);

        $totalDecremented = 0;

        foreach ($decrementSequence as $amount) {
            $remaining = $this->quotaService->getRemainingQuota($tenant);

            if ($amount <= $remaining) {
                $this->quotaService->decrement($tenant, $amount);
                $totalDecremented += $amount;

                // Property: After each successful decrement, quota is non-negative
                $newRemaining = $this->quotaService->getRemainingQuota($tenant);
                $this->assertGreaterThanOrEqual(0, $newRemaining);
            } else {
                // Property: Exception prevents negative quota
                try {
                    $this->quotaService->decrement($tenant, $amount);
                    $this->fail('Expected QuotaExceededException');
                } catch (QuotaExceededException $e) {
                    // Expected behavior - quota protection working
                    $this->assertTrue(true);
                }
            }
        }

        // Final verification
        $subscription->refresh();
        $finalRemaining = $quotaLimit - $subscription->message_quota_used;
        $this->assertGreaterThanOrEqual(0, $finalRemaining);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 2: Quota decrements correctly for each message
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: Decrement by N reduces remaining quota by exactly N.
     */
    #[Test]
    #[DataProvider('exactDecrementScenarios')]
    public function decrement_reduces_quota_by_exact_amount(
        int $quotaLimit,
        int $initialUsed,
        int $decrementAmount
    ): void {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => $initialUsed,
        ]);

        $remainingBefore = $this->quotaService->getRemainingQuota($tenant);

        // Only test valid decrements
        if ($decrementAmount <= $remainingBefore) {
            $this->quotaService->decrement($tenant, $decrementAmount);

            $remainingAfter = $this->quotaService->getRemainingQuota($tenant);

            // Property: remaining_after = remaining_before - decrement_amount
            $this->assertEquals(
                $remainingBefore - $decrementAmount,
                $remainingAfter,
                "Decrement by {$decrementAmount} should reduce remaining from {$remainingBefore} to ".($remainingBefore - $decrementAmount)
            );
        }
    }

    /**
     * Property: Sum of all decrements equals total quota used.
     */
    #[Test]
    #[DataProvider('cumulativeDecrementScenarios')]
    public function cumulative_decrements_equal_total_used(
        int $quotaLimit,
        array $decrements
    ): void {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => 0,
        ]);

        $expectedTotal = 0;

        foreach ($decrements as $amount) {
            $remaining = $this->quotaService->getRemainingQuota($tenant);
            if ($amount <= $remaining) {
                $this->quotaService->decrement($tenant, $amount);
                $expectedTotal += $amount;
            }
        }

        $subscription->refresh();

        // Property: message_quota_used = sum of all successful decrements
        $this->assertEquals($expectedTotal, $subscription->message_quota_used);
    }

    /**
     * Property: Decrement is idempotent in terms of quota accounting.
     *
     * Multiple calls with same amount should each decrement independently.
     */
    #[Test]
    public function multiple_decrements_are_additive(): void
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

        // Decrement 5 times by 10
        for ($i = 0; $i < 5; $i++) {
            $this->quotaService->decrement($tenant, 10);
        }

        $subscription->refresh();

        // Property: 5 decrements of 10 = 50 total used
        $this->assertEquals(50, $subscription->message_quota_used);
        $this->assertEquals(50, $this->quotaService->getRemainingQuota($tenant));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY 3: Quota resets on subscription renewal
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: Reset sets quota_used to 0 regardless of previous value.
     */
    #[Test]
    #[DataProvider('resetScenarios')]
    public function reset_sets_quota_used_to_zero(int $quotaLimit, int $quotaUsed): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => $quotaUsed,
        ]);

        $this->quotaService->reset($tenant);

        $subscription->refresh();

        // Property: After reset, quota_used = 0
        $this->assertEquals(0, $subscription->message_quota_used);
    }

    /**
     * Property: After reset, remaining quota equals quota limit.
     */
    #[Test]
    #[DataProvider('resetScenarios')]
    public function after_reset_remaining_equals_limit(int $quotaLimit, int $quotaUsed): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => $quotaLimit]);
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => $quotaLimit,
            'message_quota_used' => $quotaUsed,
        ]);

        $this->quotaService->reset($tenant);

        $remaining = $this->quotaService->getRemainingQuota($tenant);

        // Property: After reset, remaining = limit
        $this->assertEquals($quotaLimit, $remaining);
    }

    /**
     * Property: Reset followed by decrement works correctly.
     */
    #[Test]
    public function reset_then_decrement_works_correctly(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => 100]);
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => 100,
            'message_quota_used' => 95, // Almost exhausted
        ]);

        // Reset (simulating renewal)
        $this->quotaService->reset($tenant);

        // Should now be able to decrement full quota
        $this->quotaService->decrement($tenant, 50);

        $subscription->refresh();

        // Property: After reset and decrement, used = decrement amount
        $this->assertEquals(50, $subscription->message_quota_used);
        $this->assertEquals(50, $this->quotaService->getRemainingQuota($tenant));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PROPERTY: Unlimited quota special cases
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Property: Unlimited quota returns -1 and allows any decrement.
     */
    #[Test]
    #[DataProvider('unlimitedQuotaDecrements')]
    public function unlimited_quota_allows_any_decrement(int $decrementAmount): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['message_quota' => null]); // Unlimited
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'message_quota_limit' => null,
        ]);

        // Property: Unlimited quota returns -1
        $this->assertEquals(-1, $this->quotaService->getRemainingQuota($tenant));

        // Property: Any decrement succeeds without exception
        $this->quotaService->decrement($tenant, $decrementAmount);

        // Property: Still returns -1 after decrement
        $this->assertEquals(-1, $this->quotaService->getRemainingQuota($tenant));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DATA PROVIDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Generate random quota usage scenarios.
     */
    public static function quotaUsageScenarios(): array
    {
        $scenarios = [];

        // Edge cases
        $scenarios['zero_used'] = [100, 0];
        $scenarios['fully_used'] = [100, 100];
        $scenarios['half_used'] = [100, 50];

        // Random scenarios
        for ($i = 0; $i < 10; $i++) {
            $limit = rand(10, 10000);
            $used = rand(0, $limit);
            $scenarios["random_{$i}"] = [$limit, $used];
        }

        return $scenarios;
    }

    /**
     * Generate decrement scenarios including edge cases.
     */
    public static function decrementScenarios(): array
    {
        return [
            'decrement_1_from_full' => [100, 0, 1],
            'decrement_exact_remaining' => [100, 50, 50],
            'decrement_more_than_remaining' => [100, 90, 20],
            'decrement_from_exhausted' => [100, 100, 1],
            'large_decrement_valid' => [1000, 0, 500],
            'large_decrement_invalid' => [1000, 900, 200],
            'single_message_quota' => [1, 0, 1],
            'single_message_exhausted' => [1, 1, 1],
        ];
    }

    /**
     * Generate multiple decrement sequences.
     */
    public static function multipleDecrementSequences(): array
    {
        return [
            'small_decrements' => [100, [1, 1, 1, 1, 1]],
            'mixed_decrements' => [100, [10, 5, 20, 15, 30]],
            'exceeding_sequence' => [50, [10, 10, 10, 10, 20]], // Last one exceeds
            'exact_exhaustion' => [100, [25, 25, 25, 25]],
            'random_sequence' => [200, [rand(1, 20), rand(1, 20), rand(1, 20), rand(1, 20), rand(1, 20)]],
        ];
    }

    /**
     * Generate exact decrement scenarios.
     */
    public static function exactDecrementScenarios(): array
    {
        return [
            'decrement_1' => [100, 0, 1],
            'decrement_10' => [100, 0, 10],
            'decrement_from_partial' => [100, 30, 20],
            'decrement_to_zero' => [100, 90, 10],
            'large_quota_small_decrement' => [10000, 5000, 100],
        ];
    }

    /**
     * Generate cumulative decrement scenarios.
     */
    public static function cumulativeDecrementScenarios(): array
    {
        return [
            'five_ones' => [100, [1, 1, 1, 1, 1]],
            'tens' => [100, [10, 10, 10, 10, 10]],
            'mixed' => [100, [5, 10, 15, 20, 25]],
            'with_overflow' => [50, [10, 10, 10, 10, 20]], // Last exceeds
        ];
    }

    /**
     * Generate reset scenarios.
     */
    public static function resetScenarios(): array
    {
        return [
            'reset_from_zero' => [100, 0],
            'reset_from_partial' => [100, 50],
            'reset_from_full' => [100, 100],
            'reset_large_quota' => [10000, 9999],
            'reset_small_quota' => [10, 5],
        ];
    }

    /**
     * Generate unlimited quota decrement amounts.
     */
    public static function unlimitedQuotaDecrements(): array
    {
        return [
            'small' => [1],
            'medium' => [100],
            'large' => [10000],
            'very_large' => [1000000],
        ];
    }
}
