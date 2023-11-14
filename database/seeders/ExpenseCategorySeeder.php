<?php

namespace Database\Seeders;

use App\Models\accounts\ExpenseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'          => 'electricity_bill',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'office_rent',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'stationary',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'daily_expense',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],
        ];

        ExpenseCategory::insert($categories);
        // ExpenseCategory::factory(5)->create();
    }
}
