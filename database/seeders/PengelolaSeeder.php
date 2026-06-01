<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengelola;
use Illuminate\Support\Facades\Hash;

class PengelolaSeeder extends Seeder
{
    public function run(): void
    {
        Pengelola::create([
            'nama_pengelola' => 'Ali',
            'username' => 'admin',
            'password' => Hash::make('admin123') 
        ]);
    }
}
