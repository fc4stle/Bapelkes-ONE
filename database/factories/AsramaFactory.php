<?php

namespace Database\Factories;

use App\Models\Asrama;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asrama>
 */
class AsramaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->word(),
            'kapasitas' => fake()->numberBetween(10, 50),
        ];
    }
}
