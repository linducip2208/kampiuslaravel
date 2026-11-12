<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Matakuliah;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'matakuliah_id' => Matakuliah::factory(),
            'tahun_akademik_id' => TahunAkademik::factory(),
            'nama' => 'Kelas '.fake()->randomLetter(),
            'kuota' => 40,
            'dosen_id' => Dosen::factory(),
            'hari' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'ruang_id' => Ruang::factory(),
        ];
    }
}
