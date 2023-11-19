<?php

namespace Database\Seeders;

use App\Models\client\LoanRegistration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoanRegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoanRegistration::factory(500)->create();
    }
}
