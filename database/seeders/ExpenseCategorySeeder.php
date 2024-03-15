<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\accounts\ExpenseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'          => 'loan_given',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'saving_withdrawal',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'loan_saving_withdrawal',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'account_closing_interest',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'electricity_bill',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'office_rent',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'printing_stationery',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'daily_expense',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'employee_salary',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'travel_cost',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'consumption',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'others_expense',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],
        ];

        ExpenseCategory::insert($categories);
        // ExpenseCategory::factory(5)->create();
    }
}
