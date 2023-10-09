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
            $user               = new User();
            $user->name         = "Rishad Alam";
            $user->email        = "sazzadullalamrishad@yahoo.com";
            $user->phone        = "01876637965";
            $user->image        = "Dummy";
            $user->image_uri    = "https://t4.ftcdn.net/jpg/03/64/21/11/360_F_364211147_1qgLVxv1Tcq0Ohz3FawUfrtONzz8nq3e.jpg";
            $user->password     = Hash::make('admin123');
            $user->status       = 1;
            $user->save();
        }

        User::factory(11)->create();
    }
}
