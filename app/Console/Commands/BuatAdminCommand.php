<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:buat-admin')]
#[Description('Buat akun admin baru')]
class BuatAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Email:');
        $name = $this->ask('Nama:');
        $password = $this->secret('Password:');

        // Validasi
        if (User::where('email', $email)->exists()) {
            $this->error("Email $email sudah terdaftar.");

            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return 1;
        }

        // Konfirmasi
        if (! $this->confirm("Buat admin dengan email $email?")) {
            $this->info('Dibatalkan.');

            return 0;
        }

        // Buat admin
        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->info("Admin $email berhasil dibuat.");

        return 0;
    }
}
