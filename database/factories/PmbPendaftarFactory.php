<?php

namespace Database\Factories;

use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PmbPendaftar>
 */
class PmbPendaftarFactory extends Factory
{
    /** @var string */
    protected $model = PmbPendaftar::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_daftar' => 'PMB-'.$this->faker->year().'-'.$this->faker->unique()->numerify('######'),
            'nama' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'hp' => '08'.$this->faker->numerify('##########'),
            'sekolah_asal' => 'SMA Negeri '.$this->faker->numberBetween(1, 10),
            'prodi1_id' => Prodi::factory(),
            'prodi2_id' => null,
            'gelombang_id' => PmbGelombang::factory(),
            'berkas_json' => ['ijazah' => 'berkas/ijazah.pdf'],
            'status' => PmbPendaftar::STATUS_DIAJUKAN,
            'catatan_verifikasi' => null,
            'user_id' => null,
        ];
    }
}
