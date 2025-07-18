<?php

namespace Database\Factories\Client;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use App\Models\client\ClientRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\client\LoanAccount>
 */
class LoanAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $field_id       = Field::inRandomOrder()->first()->id;
        $center_id      = Center::inRandomOrder()->where('field_id', $field_id)->first()->id;
        $category_id    = Category::inRandomOrder()->first()->id;
        $register       = ClientRegistration::inRandomOrder()->where('field_id', $field_id)->orWhere('center_id', $center_id)->first();
        $user_id        = User::inRandomOrder()->first()->id;

        return [
            'field_id'                          => $register->field_id,
            'center_id'                         => $register->center_id,
            'category_id'                       => $category_id,
            'client_registration_id'            => $register->id,
            'acc_no'                            => $register->acc_no,
            'start_date'                        => fake()->date(),
            'duration_date'                     => fake()->date(),
            'loan_given'                        => fake()->numberBetween(5, 10),
            'payable_installment'               => fake()->numberBetween(5, 10),
            'payable_deposit'                   => fake()->numberBetween(0, 1000),
            'payable_interest'                  => fake()->numberBetween(0, 100),
            'total_payable_interest'            => fake()->numberBetween(0, 100),
            'total_payable_loan_with_interest'  => fake()->numberBetween(1000, 10000),
            'loan_installment'                  => fake()->numberBetween(1000, 10000),
            'interest_installment'              => fake()->numberBetween(1000, 10000),
            'status'                            => 1,
            'is_approved'                       => fake()->numberBetween(0, 1),
            'is_loan_approved'                  => fake()->numberBetween(0, 1),
            'creator_id'                        => $user_id,
        ];
    }
}
