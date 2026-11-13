<?php

namespace Database\Factories;

use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahunAkademik>
 */
class TahunAkademikFactory extends Factory
{
    private static int $seq = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Urutan deterministik agar unik (hindari collision unique tahun+semester di test).
        self::$seq++;
        $year = 2020 + self::$seq;
        $semester = self::$seq % 2 === 0 ? 'genap' : 'ganjil';

        return [
            'tahun' => $year.'/'.($year + 1),
            'semester' => $semester,
            'aktif' => false,
        ];
    }
}
