<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().' Template',
            'type' => fake()->randomElement(['notification', 'promo', 'reminder']),
            'content' => 'Halo {nama}, '.fake()->sentence(),
            'variables' => ['nama'],
        ];
    }

    /**
     * Create a notification template.
     */
    public function notification(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'notification',
            'name' => 'Notification Template',
            'content' => 'Halo {nama}, ini adalah notifikasi untuk Anda. Status: {status}',
            'variables' => ['nama', 'status'],
        ]);
    }

    /**
     * Create a promo template.
     */
    public function promo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'promo',
            'name' => 'Promo Template',
            'content' => 'Dapatkan diskon {diskon}% untuk produk {produk}. Kode: {kode}',
            'variables' => ['diskon', 'produk', 'kode'],
        ]);
    }

    /**
     * Create a reminder template.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reminder',
            'name' => 'Reminder Template',
            'content' => 'Pengingat: {event} akan terjadi pada {tanggal}. Jangan lupa!',
            'variables' => ['event', 'tanggal'],
        ]);
    }

    /**
     * Create a template with no variables.
     */
    public function noVariables(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->sentence(),
            'variables' => [],
        ]);
    }
}
