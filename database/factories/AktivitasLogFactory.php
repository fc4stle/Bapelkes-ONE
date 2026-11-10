<?php

namespace Database\Factories;

use App\Models\AktivitasLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AktivitasLog>
 */
class AktivitasLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aksiList = [
            'nonaktifkan_user',
            'aktifkan_user',
            'ubah_role_user',
            'verifikasi_pendaftaran',
            'tolak_pendaftaran',
            'tambah_pelatihan',
            'ubah_pelatihan',
            'hapus_pelatihan',
            'kirim_pengumuman',
            'pindah_kamar',
        ];

        return [
            'user_id' => User::factory(),
            'aksi' => $this->faker->randomElement($aksiList),
            'deskripsi' => $this->faker->sentence(5),
            'subjek_tipe' => null,
            'subjek_id' => null,
            'created_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
