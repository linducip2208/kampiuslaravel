<?php

namespace Database\Factories;

use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAkademik>
 */
class TahunAkademikFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2022, 2027);

        return [
            'tahun' => $year.'/'.($year + 1),
            'semester' => fake()->randomElement(['ganjil', 'genap']),
            'aktif' => false,
        ];
    }
}
