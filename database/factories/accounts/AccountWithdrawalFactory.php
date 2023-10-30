<?php

namespace Database\Factories\accounts;

use App\Models\accounts\Account;
use App\Models\accounts\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\accounts\AccountWithdrawal>
 */
class AccountWithdrawalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $account    = Account::inRandomOrder()->first();
        $amount     = fake()->numberBetween(100,1000);
        $account->increment('total_withdrawal', $amount);

        return [
            'account_id'            => $account->id,
            'amount'                => $amount,
            'previous_balance'      => $account->balance,
            'description'           => fake()->text(),
            'creator_id'            => fake()->numberBetween(1,5)
        ];
    }
}
