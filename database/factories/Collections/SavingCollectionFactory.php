<?php

namespace Database\Factories\Collections;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use App\Models\client\SavingAccount;
use App\Models\client\ClientRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collections\SavingCollection>
 */
class SavingCollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $saving     = SavingAccount::inRandomOrder()->first();
        $user_id    = User::inRandomOrder()->first()->id;

        return [
            'field_id'                  => $saving->field_id,
            'center_id'                 => $saving->center_id,
            'category_id'               => $saving->category_id,
            'client_registration_id'    => $saving->client_registration_id,
            'saving_account_id'         => $saving->id,
            'creator_id'                => $user_id,
            'acc_no'                    => $saving->acc_no,
            'deposit'                   => fake()->numberBetween(10, 2000),
            'description'               => fake()->text(),
            'is_approved'               => fake()->numberBetween(0, 1),
        ];
    }
}
