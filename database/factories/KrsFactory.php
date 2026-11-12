<?php

namespace Database\Factories;

use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Krs>
 */
class KrsFactory extends Factory
{
    /** @var string */
    protected $model = Krs::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'tahun_akademik_id' => TahunAkademik::factory(),
            'status' => Krs::STATUS_DRAFT,
            'total_sks' => 0,
            'ipk_snapshot' => 3.00,
            'catatan' => null,
        ];
    }
}
