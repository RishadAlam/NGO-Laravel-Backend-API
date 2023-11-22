<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\client\SavingAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SavingAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SavingAccount::factory(500)->create();
    }
}
