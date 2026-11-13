<?php

namespace Database\Factories;

use App\Models\PmbReferral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PmbReferral>
 */
class PmbReferralFactory extends Factory
{
    protected $model = PmbReferral::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper($this->faker->unique()->lexify('???')).$this->faker->unique()->numerify('###'),
            'sekolah' => 'SMA Negeri '.$this->faker->numberBetween(1, 99),
            'pic_nama' => $this->faker->name(),
            'pic_hp' => '08'.$this->faker->numerify('##########'),
            'aktif' => true,
        ];
    }
}
