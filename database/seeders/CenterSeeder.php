<?php

namespace Database\Seeders;

use App\Models\center\Center;
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
                    'name'          => fake()->unique()->name(),
                    'description'   => fake()->paragraph(),
                    'status'        => fake()->numberBetween(0,1)
                ]
            );
        }
    }
}
