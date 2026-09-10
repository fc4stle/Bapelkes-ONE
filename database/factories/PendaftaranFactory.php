<?php

namespace Database\Factories;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pendaftaran>
 */
class PendaftaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peserta_id' => User::factory(),
            'pelatihan_id' => Pelatihan::factory(),
            'status_verifikasi' => 'pending',
            'data_diri' => null,
            'dokumen' => null,
            'butuh_asrama' => false,
            'catatan' => null,
        ];
    }
}
