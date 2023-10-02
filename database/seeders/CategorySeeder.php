<?php

namespace Database\Seeders;

use App\Models\category\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i=0; $i < 10; $i++) {
            Category::create(
                [
                    'name'          => fake()->unique()->name(),
                    'description'   => fake()->paragraph(),
                    'saving'        => fake()->numberBetween(0,1),
                    'loan'          => fake()->numberBetween(0,1),
                    'status'        => fake()->numberBetween(0,1)
                ]
            );
        }
    }
}
