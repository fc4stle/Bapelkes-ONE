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

    /**
     * Generate respons dengan skor profil tertentu untuk testing clustering.
     * Opsi: 'optimal', 'developing', 'constrained', 'at_risk'.
     */
    public function withProfil(string $profil): self
    {
        return $this->state(function () use ($profil) {
            $config = config('tiyas');
            $jawaban = [];

            // Generate skor sintetis per profil
            $skorProfil = match ($profil) {
                'optimal' => ['TC' => 4, 'TE' => 4, 'ITT' => 4, 'YB' => 4],  // Semua tinggi
                'developing' => ['TC' => 3, 'TE' => 3, 'ITT' => 3, 'YB' => 2],  // Sedang-tinggi, YB sedikit rendah
                'constrained' => ['TC' => 2, 'TE' => 2, 'ITT' => 4, 'YB' => 2],  // ITT tinggi, TC/TE rendah
                'at_risk' => ['TC' => 1, 'TE' => 1, 'ITT' => 1, 'YB' => 1],  // Semua rendah
                default => ['TC' => 3, 'TE' => 3, 'ITT' => 3, 'YB' => 3],
            };

            // Isi jawaban berdasarkan target skor profil
            foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
                $targetSkor = $skorProfil[$kodeKonstruk] ?? 3;
                foreach ($konstruk['dimensi'] as $dimensi) {
                    foreach ($dimensi as $kode => $text) {
                        // Isi dengan skor target atau skor sekitar target
                        $jawaban[$kode] = $targetSkor;
                    }
                }
            }

            return [
                'jawaban' => $jawaban,
            ];
        });
    }
}
