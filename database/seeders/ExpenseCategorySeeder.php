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
                'description'   => fake()->text(20),
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'office_rent',
                'description'   => fake()->text(20),
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'stationary',
                'description'   => fake()->text(20),
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'daily_expense',
                'description'   => fake()->text(20),
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],
        ];

        ExpenseCategory::insert($categories);
        ExpenseCategory::factory(5)->create();
    }
}
