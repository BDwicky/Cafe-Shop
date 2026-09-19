<?php

namespace Database\Factories;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promo>
 */
class PromoFactory extends Factory
{
    protected $model = Promo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PROMO'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'percentage',
            'discount_value' => fake()->randomElement([10, 15, 20, 25, 50]),
            'max_discount' => 20000,
            'min_order' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    public function fixed(int $amount = 10000): static
    {
        return $this->state(fn () => [
            'type' => 'fixed',
            'discount_value' => $amount,
            'max_discount' => null,
        ]);
    }

    public function percentage(int $percent = 20, ?int $maxDiscount = 15000): static
    {
        return $this->state(fn () => [
            'type' => 'percentage',
            'discount_value' => $percent,
            'max_discount' => $maxDiscount,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subDay(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn () => [
            'usage_limit' => 5,
            'used_count' => 5,
        ]);
    }
}
