<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2, 20) * 5000;
        $discount = fake()->randomElement([0, 0, 0, 5000]);

        return [
            'code' => 'KKI-' . now()->format('ymd') . '-' . fake()->unique()->numberBetween(1, 9999),
            'user_id' => User::factory(),
            'order_type' => fake()->randomElement(['dine_in', 'take_away']),
            'customer_name' => fake()->optional()->name(),
            'payment_method' => fake()->randomElement(['cash', 'qris', 'debit']),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal - $discount,
            'paid_amount' => $subtotal,
            'change_amount' => $discount, // paid - total = discount
            'status' => 'paid',
            'note' => null,
        ];
    }
}
