<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nilai extends Model
{
    protected $table = 'nilai';

    protected $fillable = [
        'mahasiswa_id', 'kelas_id', 'tugas', 'uts', 'uas',
        'kehadiran', 'na', 'huruf', 'bobot', 'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'tugas' => 'decimal:2',
            'uts' => 'decimal:2',
            'uas' => 'decimal:2',
            'kehadiran' => 'decimal:2',
            'na' => 'decimal:2',
            'bobot' => 'decimal:1',
            'is_locked' => 'boolean',
        ];
    }

    public static function hitungNa(float $tugas, float $uts, float $uas, float $kehadiran): float
    {
        return round((0.3 * $tugas) + (0.3 * $uts) + (0.3 * $uas) + (0.1 * $kehadiran), 2);
    }

    public static function konversiHuruf(float $na): string
    {
        if ($na >= 85) {
            return 'A';
        }
        if ($na >= 70) {
            return 'B';
        }
        if ($na >= 55) {
            return 'C';
        }
        if ($na >= 40) {
            return 'D';
        }

        return 'E';
    }

    public static function bobotHuruf(string $huruf): float
    {
        return match ($huruf) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            default => 0.0,
        };
    }

    public function recalculate(): void
    {
        $this->na = self::hitungNa((float) $this->tugas, (float) $this->uts, (float) $this->uas, (float) $this->kehadiran);
        $this->huruf = self::konversiHuruf((float) $this->na);
        $this->bobot = self::bobotHuruf($this->huruf);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
