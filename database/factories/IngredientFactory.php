<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => 'ING-'.fake()->unique()->numerify('###'),
            'unit' => fake()->randomElement(['gr', 'ml', 'pcs']),
            'category' => fake()->randomElement(array_keys(Ingredient::CATEGORIES)),
            'current_stock' => 1000.00,
            'minimum_stock' => 100.00,
            'cost_per_unit' => 200,
            'notes' => fake()->sentence(),
        ];
    }
}
