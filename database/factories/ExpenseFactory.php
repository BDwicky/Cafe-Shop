<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_number' => Expense::generateExpenseNumber(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(array_keys(Expense::CATEGORIES)),
            'title' => fake()->sentence(3),
            'amount' => fake()->numberBetween(10000, 250000),
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'supplier' => fake()->company(),
            'notes' => fake()->sentence(),
        ];
    }
}
