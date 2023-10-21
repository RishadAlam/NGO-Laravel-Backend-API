<?php

namespace Database\Factories\accounts;

use App\Models\accounts\IncomeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\accounts\IncomeCategory>
 */
class IncomeCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IncomeCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'          => fake()->name,
            'description'   => fake()->text(),
            'creator_id'    => auth()->id()
        ];
    }
}
