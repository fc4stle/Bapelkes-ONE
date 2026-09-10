<?php

namespace Database\Factories;

use App\Models\Asrama;
use App\Models\Kamar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kamar>
 */
class KamarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asrama_id' => Asrama::factory(),
            'nomor_kamar' => fake()->unique()->bothify('K-###'),
            'kapasitas' => 1,
        ];
    }
}
