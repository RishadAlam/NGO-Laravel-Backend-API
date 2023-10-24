<?php

namespace Database\Factories\accounts;

use App\Models\accounts\Account;
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
        $account    = Account::inRandomOrder()->first();
        $cat_id     = ExpenseCategory::inRandomOrder()->first()->id;
        $amount     = fake()->numberBetween(100,1000);
        $account->update(['total_withdrawal' => $amount]);
        return [
            'account_id'            => $account->id,
            'expense_category_id'   => $cat_id,
            'amount'                => $amount,
            'previous_balance'      => $account->balance,
            'description'           => fake()->text(),
            'creator_id'            => fake()->numberBetween(1,5)
        ];
    }
}
