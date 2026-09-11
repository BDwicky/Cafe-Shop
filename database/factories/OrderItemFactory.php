<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->numberBetween(10, 40) * 1000;
        $qty = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'menu_id' => null,
            'menu_name' => fake()->randomElement(['Americano', 'Caffe Latte', 'Butter Croissant']),
            'price' => $price,
            'qty' => $qty,
            'line_total' => $price * $qty,
        ];
    }
}
