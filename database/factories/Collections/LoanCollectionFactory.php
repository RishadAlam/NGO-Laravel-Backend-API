<?php

namespace Database\Factories\Collections;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use App\Models\client\ClientRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collections\LoanCollection>
 */
class LoanCollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loan       = LoanAccount::inRandomOrder()->first();
        $user_id    = User::inRandomOrder()->first()->id;

        return [
            'field_id'                  => $loan->field_id,
            'center_id'                 => $loan->center_id,
            'category_id'               => $loan->category_id,
            'client_registration_id'    => $loan->client_registration_id,
            'loan_account_id'           => $loan->id,
            'creator_id'                => $user_id,
            'acc_no'                    => $loan->acc_no,
            'deposit'                   => fake()->numberBetween(10, 2000),
            'loan'                      => fake()->numberBetween(10, 2000),
            'interest'                  => fake()->numberBetween(10, 2000),
            'total'                     => fake()->numberBetween(10, 2000),
            'description'               => fake()->text(),
            'is_approved'               => fake()->numberBetween(0, 1),
        ];
    }
}
