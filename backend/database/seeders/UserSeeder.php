<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(['email' => 'admin@nexustrade.local'], [
            'first_name'        => 'Admin',
            'last_name'         => 'User',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
        Wallet::firstOrCreate(['user_id' => $admin->id], ['balance_cents' => 0]);
        NotificationPreference::firstOrCreate(['user_id' => $admin->id]);

        // 10 regular users
        for ($i = 1; $i <= 10; $i++) {
            $user = User::firstOrCreate(['email' => "user{$i}@nexustrade.local"], [
                'first_name'        => 'User',
                'last_name'         => "#{$i}",
                'password'          => Hash::make('password'),
                'role'              => 'user',
                'email_verified_at' => now(),
            ]);
            Wallet::firstOrCreate(['user_id' => $user->id], ['balance_cents' => random_int(10000, 1000000)]);
            NotificationPreference::firstOrCreate(['user_id' => $user->id]);
        }
    }
}
