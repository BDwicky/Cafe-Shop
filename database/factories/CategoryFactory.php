<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Kopi', 'Non-Kopi', 'Snack', 'Pastry', 'Dessert', 'Mineral']) . ' ' . fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'sort_order' => 0,
        ];
    }
}
