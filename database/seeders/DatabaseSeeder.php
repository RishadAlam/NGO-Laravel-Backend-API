<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Database\Seeders\CategorySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                UserSeeder::class,
                RolePermissionSeeder::class,
                AppConfigSeeder::class,
                AccountSeeder::class,
                IncomeCategorySeeder::class,
                ExpenseCategorySeeder::class,
                IncomeSeeder::class,
                ExpenseSeeder::class,
                AccountWithdrawalSeeder::class,
                FieldSeeder::class,
                CenterSeeder::class,
                CategorySeeder::class,
                ClientRegistrationSeeder::class,
                SavingAccountSeeder::class,
                LoanAccountSeeder::class
            ]
        );
    }
}
