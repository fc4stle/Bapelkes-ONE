<?php

namespace Database\Factories;

use App\Models\Pelatihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelatihan>
 */
class PelatihanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->dateTimeBetween('now', '+30 days');
        $tanggalSelesai = fake()->dateTimeBetween($tanggalMulai->format('Y-m-d'), $tanggalMulai->format('Y-m-d').' +14 days');

        return [
            'nama' => fake()->sentence(3),
            'deskripsi' => fake()->paragraph(),
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'lokasi' => fake()->city(),
            'kuota' => fake()->numberBetween(10, 100),
            'status' => fake()->randomElement(['draft', 'dibuka', 'ditutup', 'selesai']),
        ];
    }
}
