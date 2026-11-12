<?php

namespace Database\Factories;

use App\Models\PmbGelombang;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PmbGelombang>
 */
class PmbGelombangFactory extends Factory
{
    /** @var string */
    protected $model = PmbGelombang::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Gelombang '.$this->faker->numberBetween(1, 3).' '.$this->faker->year(),
            'tahun_akademik_id' => TahunAkademik::factory(),
            'tgl_buka' => now()->subWeek()->toDateString(),
            'tgl_tutup' => now()->addMonth()->toDateString(),
            'kuota' => 200,
            'biaya' => 250000,
            'aktif' => true,
        ];
    }
}
