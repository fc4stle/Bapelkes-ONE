<?php

namespace Database\Factories;

use App\Models\Pelatihan;
use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengumuman>
 */
class PengumumanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pelatihan_id' => Pelatihan::factory(),
            'user_id' => User::factory(),
            'judul' => $this->faker->sentence(6, true),
            'isi' => $this->faker->paragraph(5),
            'jumlah_penerima' => $this->faker->numberBetween(1, 50),
        ];
    }
}
