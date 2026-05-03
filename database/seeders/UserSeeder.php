<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::insert([
            [
                'name' => 'NameeNomen',
                'email' => 'Nomen@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('NameeNomen'),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Fatimah',
                'email' => 'fat@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('fatimah'),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Siti',
                'email' => 'sit@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('sitiiii'),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}