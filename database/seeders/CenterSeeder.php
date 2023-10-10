<?php

namespace Database\Seeders;

use App\Models\center\Center;
use App\Models\field\Field;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i=0; $i < 10; $i++) {
            $field_id = Field::inRandomOrder()->first()->id;
            Center::create(
                [
                    'field_id'      => $field_id,
                    'name'          => fake()->unique()->name(),
                    'description'   => fake()->paragraph(),
                    'status'        => fake()->numberBetween(0,1)
                ]
            );
        }
    }
}
