<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nidn' => fake()->unique()->numerify('##########'),
            'prodi_id' => Prodi::factory(),
            'status' => 'aktif',
        ];
    }
}
