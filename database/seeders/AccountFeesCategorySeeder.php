<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\client\AccountFeesCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AccountFeesCategorySeeder extends Seeder
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
                'name'          => 'withdrawal_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ], [
                'name'          => 'transaction_fee',
                'is_default'    => true,
                'creator_id'    => auth()->id()
            ]
        ];

        AccountFeesCategory::insert($categories);
    }
}
