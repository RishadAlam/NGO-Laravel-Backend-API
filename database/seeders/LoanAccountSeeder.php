<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\client\LoanAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LoanAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoanAccount::factory(500)->create();
    }
}
