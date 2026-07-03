<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin System',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'), 
            'email_verified_at' => now(),
        ]);
    }
}