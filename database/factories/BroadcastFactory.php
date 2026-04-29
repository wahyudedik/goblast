<?php

namespace Database\Factories;

use App\Models\Broadcast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Broadcast>
 */
class BroadcastFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(10, 100);

        return [
            'name' => fake()->word().' Broadcast',
            'status' => 'draft',
            'total_recipients' => $total,
            'sent_count' => 0,
            'failed_count' => 0,
            'pending_count' => $total,
            'source_type' => 'csv',
            'csv_path' => null,
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Create a draft broadcast.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create a queued broadcast.
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'queued',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create a running broadcast.
     */
    public function running(): static
    {
        $total = fake()->numberBetween(10, 100);
        $sent = fake()->numberBetween(1, $total - 1);
        $failed = fake()->numberBetween(0, $total - $sent);

        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'total_recipients' => $total,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'pending_count' => $total - $sent - $failed,
            'started_at' => now()->subHours(1),
            'completed_at' => null,
        ]);
    }

    /**
     * Create a completed broadcast.
     */
    public function completed(): static
    {
        $total = fake()->numberBetween(10, 100);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'total_recipients' => $total,
            'sent_count' => $total,
            'failed_count' => 0,
            'pending_count' => 0,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
    }

    /**
     * Create a cancelled broadcast.
     */
    public function cancelled(): static
    {
        $total = fake()->numberBetween(10, 100);
        $sent = fake()->numberBetween(0, $total);

        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'total_recipients' => $total,
            'sent_count' => $sent,
            'failed_count' => 0,
            'pending_count' => $total - $sent,
            'started_at' => now()->subHours(1),
            'completed_at' => now(),
        ]);
    }

    /**
     * Create a failed broadcast.
     */
    public function failed(): static
    {
        $total = fake()->numberBetween(10, 100);

        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'total_recipients' => $total,
            'sent_count' => 0,
            'failed_count' => $total,
            'pending_count' => 0,
            'started_at' => now()->subHours(1),
            'completed_at' => now(),
        ]);
    }

    /**
     * Create a broadcast from CSV source.
     */
    public function fromCsv(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'csv',
            'csv_path' => 'broadcasts/'.fake()->uuid().'.csv',
        ]);
    }

    /**
     * Create a broadcast from database source.
     */
    public function fromDatabase(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'database',
            'csv_path' => null,
        ]);
    }
}
