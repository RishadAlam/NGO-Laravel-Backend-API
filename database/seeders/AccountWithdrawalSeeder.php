<?php

namespace Database\Seeders;

use App\Models\accounts\AccountWithdrawal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountWithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AccountWithdrawal::factory(5)->create();
    }
}
