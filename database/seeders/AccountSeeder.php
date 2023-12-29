<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\accounts\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create([
            'name'          => "cash",
            'is_default'    => true,
            'creator_id'    => auth()->id()
        ]);

        Account::factory(5)->create();
    }
}
