<?php

namespace Database\Factories\accounts;

use App\Models\accounts\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'          => fake()->name,
            'acc_no'        => fake()->numberBetween(11111111111, 99999999999),
            'acc_details'   => fake()->text(),
            'creator_id'    => auth()->id()
        ];
    }
}
