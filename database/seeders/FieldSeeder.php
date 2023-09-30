<?php

namespace Database\Seeders;

use App\Models\field\Field;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i=0; $i < 10; $i++) {
            Field::create(
                [
                    'name'          => fake()->unique()->name(),
                    'description'   => fake()->paragraph(),
                    'status'        => fake()->numberBetween(0,1)
                ]
            );
        }
    }
}
