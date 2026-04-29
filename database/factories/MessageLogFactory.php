<?php

namespace Database\Factories;

use App\Models\MessageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageLog>
 */
class MessageLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient' => '62'.fake()->numerify('##########'),
            'message' => fake()->sentence(),
            'status' => 'pending',
            'source' => fake()->randomElement(['broadcast', 'trigger', 'reminder', 'api', 'auto_reply']),
            'error_message' => null,
            'attempts' => 0,
            'sent_at' => null,
            'failed_at' => null,
            'job_id' => fake()->uuid(),
        ];
    }

    /**
     * Create a sent message log.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
            'attempts' => 1,
        ]);
    }

    /**
     * Create a failed message log.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failed_at' => now(),
            'attempts' => 3,
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Create a pending message log.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    /**
     * Create a retrying message log.
     */
    public function retrying(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'retrying',
            'attempts' => fake()->numberBetween(1, 2),
            'error_message' => 'Temporary error, retrying...',
        ]);
    }

    /**
     * Create a cancelled message log.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'error_message' => 'Cancelled - quota exhausted',
        ]);
    }

    /**
     * Create a message log from broadcast source.
     */
    public function fromBroadcast(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'broadcast',
        ]);
    }

    /**
     * Create a message log from trigger source.
     */
    public function fromTrigger(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'trigger',
        ]);
    }

    /**
     * Create a message log from reminder source.
     */
    public function fromReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'reminder',
        ]);
    }

    /**
     * Create a message log from API source.
     */
    public function fromApi(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'api',
        ]);
    }

    /**
     * Create a message log from auto-reply source.
     */
    public function fromAutoReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'auto_reply',
        ]);
    }
}
