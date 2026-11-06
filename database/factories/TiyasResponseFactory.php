<?php

namespace Database\Factories;

use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TiyasResponse>
 */
class TiyasResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $config = config('tiyas');

        // Buat jawaban dummy (semua 3 = Sesuai/Sering)
        $jawaban = [];
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $jawaban[$kode] = 3;
                }
            }
        }

        // Identitas dummy
        $identitas = [
            'asal_instansi' => 'Dinas Kesehatan',
            'asal_kabupaten' => 'Kota Yogyakarta',
            'jenis_profesi' => 'Perawat',
            'masa_kerja' => '3–5 tahun',
            'waktu_sejak_selesai' => '3–6 bulan',
        ];

        return [
            'pendaftaran_id' => Pendaftaran::factory(),
            'identitas' => $identitas,
            'jawaban' => $jawaban,
            'submitted_at' => now(),
        ];
    }
}
