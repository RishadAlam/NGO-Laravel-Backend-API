<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = User::where('email', 'sazzadullalamrishad@yahoo.com')->first();

        if (is_null($email)) {
            $user           = new User();
            $user->name     = "Rishad Alam";
            $user->email    = "sazzadullalamrishad@yahoo.com";
            $user->phone    = "01876637965";
            $user->password = Hash::make('Rishad@bitcode786');
            $user->status   = 1;
            $user->save();
        }

        User::factory(11)->create();
    }
}
