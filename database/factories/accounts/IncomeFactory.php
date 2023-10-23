<?php

namespace Database\Factories\accounts;

use App\Models\accounts\Income;
use App\Models\accounts\IncomeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\accounts\Income>
 */
class IncomeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Income::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cat_id = IncomeCategory::inRandomOrder()->first()->id;
        return [
            'income_category_id'    => $cat_id,
            'amount'                => fake()->numberBetween(100,1000),
            'description'           => fake()->text(),
            'creator_id'            => fake()->numberBetween(1,5)
        ];
    }
}
