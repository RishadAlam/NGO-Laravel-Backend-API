<?php

namespace Database\Factories\Client;

use App\Models\center\Center;
use App\Models\field\Field;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\client\ClientRegistration>
 */
class ClientRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $field_id           = Field::inRandomOrder()->first()->id;
        $center_id          = Center::inRandomOrder()->where('field_id', $field_id)->first()->id;
        $user_id            = User::inRandomOrder()->first()->id;
        $present_address    = (object) [
            'street_address'    => fake()->streetAddress(),
            'city'              => fake()->city(),
            'word_no'           => fake()->numberBetween(1, 30),
            'post_office'       => fake()->streetName(),
            'post_code'         => fake()->buildingNumber(),
            'police_station'    => fake()->buildingNumber(),
            'state'             => fake()->state(),
            'division'          => fake()->state(),
        ];
        $permanent_address  = (object) [
            'street_address'    => fake()->streetAddress(),
            'city'              => fake()->city(),
            'word_no'           => fake()->numberBetween(1, 30),
            'post_office'       => fake()->streetName(),
            'post_code'         => fake()->buildingNumber(),
            'police_station'    => fake()->buildingNumber(),
            'state'             => fake()->state(),
            'division'          => fake()->state(),
        ];

        return [
            'field_id'          => $field_id,
            'center_id'         => $center_id,
            'acc_no'            => fake()->uniqid(),
            'name'              => fake()->name,
            'father_name'       => fake()->name('male'),
            'husband_name'      => fake()->name('male'),
            'mother_name'       => fake()->name('female'),
            'nid'               => fake()->numberBetween(1000000000000, 9999999999999),
            'dob'               => fake()->date,
            'occupation'        => fake()->text(10),
            'religion'          => fake()->randomElement(['islam', 'hindu', 'christian', 'Buddhist', 'others']),
            'gender'            => fake()->randomElement(['male', 'female', 'others']),
            'primary_phone'     => fake()->phoneNumber(),
            'secondary_phone'   => fake()->phoneNumber(),
            'image_uri'         => fake()->imageUrl(),
            'share'             => fake()->numberBetween(100, 1000),
            'present_address'   => json_encode($present_address),
            'permanent_address' => json_encode($permanent_address),
            'creator_id'        => $user_id,
        ];
    }
}
