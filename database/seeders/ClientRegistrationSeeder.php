<?php

namespace Database\Seeders;

use App\Models\client\ClientRegistration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientRegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClientRegistration::factory(1000)->create();
    }
}
