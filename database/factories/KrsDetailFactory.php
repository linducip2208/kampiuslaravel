<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KrsDetail>
 */
class KrsDetailFactory extends Factory
{
    /** @var string */
    protected $model = KrsDetail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'krs_id' => Krs::factory(),
            'kelas_id' => Kelas::factory(),
            'sks' => $this->faker->numberBetween(1, 4),
            'status' => 'aktif',
        ];
    }
}
