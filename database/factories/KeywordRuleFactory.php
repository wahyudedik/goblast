<?php

namespace Database\Factories;

use App\Models\KeywordRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeywordRule>
 */
class KeywordRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'keyword' => fake()->word(),
            'reply' => fake()->sentence(),
            'priority' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    /**
     * Create a high priority keyword rule.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 10,
        ]);
    }

    /**
     * Create a medium priority keyword rule.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 5,
        ]);
    }

    /**
     * Create a low priority keyword rule.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 1,
        ]);
    }

    /**
     * Create an active keyword rule.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive keyword rule.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a keyword rule for common queries.
     */
    public function commonQuery(): static
    {
        $keywords = ['harga', 'price', 'biaya', 'cost', 'berapa', 'how much'];
        $replies = [
            'Harga kami sangat kompetitif. Silakan hubungi tim sales kami untuk penawaran terbaik.',
            'Untuk informasi harga, silakan klik link berikut: [link]',
            'Biaya layanan kami tergantung pada paket yang Anda pilih. Hubungi kami untuk detail lebih lanjut.',
        ];

        return $this->state(fn (array $attributes) => [
            'keyword' => fake()->randomElement($keywords),
            'reply' => fake()->randomElement($replies),
            'priority' => 5,
        ]);
    }
}
