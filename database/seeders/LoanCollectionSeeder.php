<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collections\LoanCollection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LoanCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoanCollection::factory(10000)->create();
    }
}
