<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\client\ClientRegistration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClientRegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClientRegistration::factory(100)->create();
    }
}
