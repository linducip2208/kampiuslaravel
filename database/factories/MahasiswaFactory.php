<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
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
            'nim' => fake()->unique()->numerify('##########'),
            'prodi_id' => Prodi::factory(),
            'angkatan' => fake()->numberBetween(2021, 2026),
            'dosen_pa_id' => null,
            'status' => 'aktif',
        ];
    }
}
