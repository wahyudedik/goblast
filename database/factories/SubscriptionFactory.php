<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now();
        $endsAt = $startsAt->copy()->addMonth();

        return [
            'status' => 'active',
            'message_quota_used' => 0,
            'message_quota_limit' => 1000,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];
    }

    /**
     * Create an active subscription.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    /**
     * Create an expired subscription.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Create a cancelled subscription.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create a subscription with unlimited quota.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_quota_limit' => null,
        ]);
    }

    /**
     * Create a subscription with partial quota usage.
     */
    public function partialUsage(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_quota_used' => 500,
            'message_quota_limit' => 1000,
        ]);
    }

    /**
     * Create a subscription with high quota usage.
     */
    public function highUsage(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_quota_used' => 900,
            'message_quota_limit' => 1000,
        ]);
    }
}
