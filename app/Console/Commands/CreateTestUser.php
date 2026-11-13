<?php

namespace App\Console\Commands;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUser extends Command
{
    protected $signature = 'test:create-user';

    protected $description = 'Create test user for Playwright testing';

    public function handle()
    {
        \DB::transaction(function () {
            $peserta = User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test Peserta',
                    'role' => 'peserta',
                    'password' => bcrypt('password'),
                ]
            );

            $pelatihan = Pelatihan::factory()->create([
                'status' => 'selesai',
                'tanggal_selesai' => now()->subDay(),
            ]);

            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);

            $this->info("✓ Created peserta: {$peserta->email}");
            $this->info("✓ Pelatihan ID: {$pelatihan->id}");
            $this->info("✓ Pendaftaran ID: {$pendaftaran->id}");
        });
    }
}
