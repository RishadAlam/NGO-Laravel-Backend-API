<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\accounts\IncomeCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IncomeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'          => 'registration_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'saving_form_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'loan_form_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'closing_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'withdrawal_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'money_transfer_transaction_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],
        ];

        IncomeCategory::insert($categories);
        IncomeCategory::factory(5)->create();
    }
}
