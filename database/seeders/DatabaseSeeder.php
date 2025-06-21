<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                // IncomeSeeder::class,
                // ExpenseSeeder::class,
                // AccountWithdrawalSeeder::class,
                // FieldSeeder::class,
                // CenterSeeder::class,
                // ClientRegistrationSeeder::class,
                // SavingAccountSeeder::class,
                // LoanAccountSeeder::class,
                // SavingCollectionSeeder::class,
                // LoanCollectionSeeder::class,

                UserSeeder::class,
                RolePermissionSeeder::class,
                AppConfigSeeder::class,
                AccountSeeder::class,
                IncomeCategorySeeder::class,
                ExpenseCategorySeeder::class,
                CategorySeeder::class,
                AccountFeesCategorySeeder::class,
                AuditReportPageSeeder::class,
                AuditReportMetaSeeder::class
            ]
        );
    }
}
