<?php

namespace Database\Seeders;

use App\Models\accounts\IncomeCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            ],[
                'name'          => 'closing_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],[
                'name'          => 'withdrawal_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],[
                'name'          => 'money_transfer_transaction_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ],
        ];

        IncomeCategory::insert($categories);
        // IncomeCategory::factory(5)->create();
    }
}
