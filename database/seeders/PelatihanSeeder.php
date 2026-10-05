<?php

namespace Database\Seeders;

use App\Models\Pelatihan;
use Illuminate\Database\Seeder;

class PelatihanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pelatihan::query()->delete();

        Pelatihan::insert([
            [
                'nama' => 'Pelatihan Dasar K3',
                'deskripsi' => 'Pelatihan keselamatan dan kesehatan kerja dasar untuk tenaga kesehatan.',
                'tanggal_mulai' => '2026-11-10',
                'tanggal_selesai' => '2026-11-12',
                'lokasi' => 'Gedung A Bapelkes',
                'kuota' => 30,
                'metode' => 'luring',
                'status' => 'dibuka',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Manajemen Puskesmas',
                'deskripsi' => 'Pelatihan manajemen dan administrasi puskesmas untuk kepala dan staf.',
                'tanggal_mulai' => '2026-11-15',
                'tanggal_selesai' => '2026-11-18',
                'lokasi' => 'Online via Zoom',
                'kuota' => 50,
                'metode' => 'daring',
                'status' => 'dibuka',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Gizi Masyarakat',
                'deskripsi' => 'Pelatihan gizi masyarakat dan pencegahan stunting.',
                'tanggal_mulai' => '2026-11-20',
                'tanggal_selesai' => '2026-11-22',
                'lokasi' => 'Gedung B Bapelkes',
                'kuota' => 2,
                'metode' => 'luring',
                'status' => 'dibuka',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}