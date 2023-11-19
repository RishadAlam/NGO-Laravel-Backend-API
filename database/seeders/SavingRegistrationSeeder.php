<?php

namespace Database\Seeders;

use App\Models\client\SavingRegistration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SavingRegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SavingRegistration::factory(500)->create();
    }
}
