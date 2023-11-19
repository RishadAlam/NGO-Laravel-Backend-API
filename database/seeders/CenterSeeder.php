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
            Center::create(
                [
                    'field_id'      => $i+1,
                    'name'          => fake()->unique()->name(),
                    'description'   => fake()->paragraph(),
                    'status'        => fake()->numberBetween(0,1)
                ]
            );
        }
    }
}
