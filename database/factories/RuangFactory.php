<?php

namespace Database\Factories;

use App\Models\Ruang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruang>
 */
class RuangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gedung' => 'Gedung '.fake()->randomLetter(),
            'nama' => 'R.'.fake()->unique()->bothify('###'),
            'kapasitas' => fake()->numberBetween(20, 120),
        ];
    }
}
