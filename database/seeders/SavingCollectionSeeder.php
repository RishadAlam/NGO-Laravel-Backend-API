<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SavingCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SavingCollection::factory(10000)->create();
    }
}
