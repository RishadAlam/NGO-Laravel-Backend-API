<?php

namespace Database\Factories\accounts;

use App\Models\accounts\Expense;
use App\Models\accounts\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\accounts\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cat_id = ExpenseCategory::inRandomOrder()->first()->id;
        return [
            'expense_category_id'   => $cat_id,
            'amount'                => fake()->numberBetween(100,1000),
            'description'           => fake()->text(),
            'creator_id'            => fake()->numberBetween(1,5)
        ];
    }
}
