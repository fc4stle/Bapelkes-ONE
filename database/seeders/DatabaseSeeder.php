<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun uji: Peserta
        User::updateOrCreate(
            ['email' => 'peserta@test.local'],
            [
                'name' => 'Peserta Test',
                'password' => Hash::make('password'),
                'role' => 'peserta',
            ]
        );

        // Akun uji: Panitia
        User::updateOrCreate(
            ['email' => 'panitia@test.local'],
            [
                'name' => 'Panitia Test',
                'password' => Hash::make('password'),
                'role' => 'panitia',
            ]
        );

        $this->call([
            AsramaKamarSeeder::class,
            PelatihanSeeder::class,
        ]);
    }
}
