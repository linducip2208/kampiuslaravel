<?php

namespace Database\Factories;

use App\Models\Fakultas;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Prodi>
 */
class ProdiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->words(2, true);

        return [
            'fakultas_id' => Fakultas::factory(),
            'nama' => $nama,
            'jenjang' => fake()->randomElement(['D3', 'S1', 'S2']),
            'kode' => strtoupper(fake()->unique()->lexify('????')),
            'slug' => Str::slug($nama).'-'.fake()->unique()->randomNumber(4),
        ];
    }
}
