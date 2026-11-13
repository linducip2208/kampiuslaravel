<?php

namespace Database\Factories;

use App\Models\PeminjamanRuang;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeminjamanRuang>
 */
class PeminjamanRuangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ruang_id' => Ruang::factory(),
            'user_id' => User::factory(),
            'tanggal' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'jam_mulai' => '10:00',
            'jam_selesai' => '12:00',
            'keperluan' => fake()->sentence(4),
            'status' => 'diajukan',
        ];
    }
}
