<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Americano', 'Latte', 'Matcha', 'Croissant', 'Fries', 'Espresso']).' '.fake()->unique()->numberBetween(1, 999);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(10, 50) * 1000,
            'image' => null,
            'is_available' => true,
            'sort_order' => 0,
        ];
    }
}
