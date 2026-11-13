<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public const KEHADIRAN_MINIMAL = 75.0;

    public const BOBOT_DEFAULT = ['tugas' => 30.0, 'uts' => 30.0, 'uas' => 30.0, 'kehadiran' => 10.0];

    /**
     * @param  array{tugas?:numeric,uts?:numeric,uas?:numeric,kehadiran?:numeric}|list<numeric>|null  $bobot
     */
    public static function hitungNa(float $tugas, float $uts, float $uas, float $kehadiran, ?array $bobot = null): float
    {
        $w = self::normalisasiBobot($bobot);

        return round(
            (($w['tugas'] * $tugas) + ($w['uts'] * $uts) + ($w['uas'] * $uas) + ($w['kehadiran'] * $kehadiran)) / 100,
            2
        );
    }

    /**
     * @param  array{tugas?:numeric,uts?:numeric,uas?:numeric,kehadiran?:numeric}|list<numeric>|null  $bobot
     * @return array{tugas:float,uts:float,uas:float,kehadiran:float}
     */
    public static function normalisasiBobot(?array $bobot = null): array
    {
        if ($bobot === null) {
            return self::BOBOT_DEFAULT;
        }

        if (array_is_list($bobot)) {
            $bobot = [
                'tugas' => (float) ($bobot[0] ?? 0),
                'uts' => (float) ($bobot[1] ?? 0),
                'uas' => (float) ($bobot[2] ?? 0),
                'kehadiran' => (float) ($bobot[3] ?? 0),
            ];
        } else {
            $bobot = [
                'tugas' => (float) ($bobot['tugas'] ?? 0),
                'uts' => (float) ($bobot['uts'] ?? 0),
                'uas' => (float) ($bobot['uas'] ?? 0),
                'kehadiran' => (float) ($bobot['kehadiran'] ?? 0),
            ];
        }

        $total = $bobot['tugas'] + $bobot['uts'] + $bobot['uas'] + $bobot['kehadiran'];

        if (abs($total - 100.0) > 0.001) {
            throw new \InvalidArgumentException('Total bobot nilai harus 100.');
        }

        return $bobot;
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

    public function recalculate(?array $bobot = null): void
    {
        $this->na = self::hitungNa((float) $this->tugas, (float) $this->uts, (float) $this->uas, (float) $this->kehadiran, $bobot);
        $this->huruf = self::konversiHuruf((float) $this->na);
        $this->bobot = self::bobotHuruf($this->huruf);
    }

    public static function isKehadiranKurang(float $kehadiran, float $batas = self::KEHADIRAN_MINIMAL): bool
    {
        return $kehadiran < $batas;
    }

    public function butuhPeringatanKehadiran(): bool
    {
        return self::isKehadiranKurang((float) $this->kehadiran);
    }

    /**
     * Remedial hanya untuk huruf D/E. Nilai remedial menggantikan UAS bila
     * lebih tinggi, NA dihitung ulang dan dibatasi maksimal 55 (huruf C).
     *
     * @param  array{tugas?:numeric,uts?:numeric,uas?:numeric,kehadiran?:numeric}|list<numeric>|null  $bobot
     */
    public function terapkanRemedial(float $nilaiRemedial, ?array $bobot = null): bool
    {
        if (! in_array($this->huruf, ['D', 'E'], true)) {
            return false;
        }

        $this->uas = max((float) $this->uas, $nilaiRemedial);
        $na = self::hitungNa((float) $this->tugas, (float) $this->uts, (float) $this->uas, (float) $this->kehadiran, $bobot);
        $this->na = min($na, 55.0);
        $this->huruf = self::konversiHuruf((float) $this->na);
        $this->bobot = self::bobotHuruf($this->huruf);

        return true;
    }

    /**
     * Rekap EDOM per dosen: rata-rata skor + jumlah responden.
     *
     * @return Collection<int, object{dosen_id:int,avg_skor:float,total_responden:int,total_kelas:int}>
     */
    public static function rekapEdomPerDosen(?int $tahunAkademikId = null): Collection
    {
        $query = DB::table('edom')
            ->join('kelas', 'kelas.id', '=', 'edom.kelas_id')
            ->whereNotNull('kelas.dosen_id')
            ->selectRaw('kelas.dosen_id as dosen_id, AVG(edom.skor) as avg_skor, COUNT(*) as total_responden, COUNT(DISTINCT edom.kelas_id) as total_kelas')
            ->groupBy('kelas.dosen_id');

        if ($tahunAkademikId !== null) {
            $query->where('edom.tahun_akademik_id', $tahunAkademikId);
        }

        return $query->get()->map(fn ($row) => (object) [
            'dosen_id' => (int) $row->dosen_id,
            'avg_skor' => round((float) $row->avg_skor, 2),
            'total_responden' => (int) $row->total_responden,
            'total_kelas' => (int) $row->total_kelas,
        ]);
    }

    /**
     * Rekap EDOM untuk satu dosen beserta rincian per kelas.
     */
    public static function rekapEdomDosen(int $dosenId, ?int $tahunAkademikId = null): array
    {
        $kelasIds = Kelas::where('dosen_id', $dosenId)->pluck('id')->all();

        $base = Edom::whereIn('kelas_id', $kelasIds)
            ->when($tahunAkademikId !== null, fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId));

        $ringkasan = (clone $base)
            ->selectRaw('COUNT(*) as total_responden, AVG(skor) as avg_skor')
            ->first();

        $perKelas = (clone $base)
            ->selectRaw('kelas_id, COUNT(*) as total_responden, AVG(skor) as avg_skor')
            ->groupBy('kelas_id')
            ->get()
            ->map(fn ($row) => [
                'kelas_id' => (int) $row->kelas_id,
                'avg_skor' => round((float) $row->avg_skor, 2),
                'total_responden' => (int) $row->total_responden,
            ])->all();

        return [
            'dosen_id' => $dosenId,
            'avg_skor' => round((float) ($ringkasan->avg_skor ?? 0), 2),
            'total_responden' => (int) ($ringkasan->total_responden ?? 0),
            'total_kelas' => count($perKelas),
            'per_kelas' => $perKelas,
        ];
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
