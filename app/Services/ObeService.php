<?php

namespace App\Services;

use App\Models\Cpmk;
use App\Models\CpmkAsesmen;
use App\Models\QuizJawaban;
use App\Models\QuizSoal;
use App\Models\TugasKumpul;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M-049: ketercapaian CPL/CPMK dari asesmen kuis + tugas.
 * Skor dinormalisasi 0..1 (kuis: skor/bobot; tugas: nilai/100).
 * Hanya asesmen yang sudah dikerjakan yang dihitung.
 *
 * @return array{per_cpl: array<string, float>, per_cpmk: array<string, float>, keseluruhan: float}
 */
class ObeService
{
    public static function ketercapaian(int $mahasiswaId, ?int $prodiId = null): array
    {
        $perCpl = [];
        $perCpmk = [];

        try {
            if (! Schema::hasTable('cpmk') || ! Schema::hasTable('cpmk_asesmen')) {
                return ['per_cpl' => [], 'per_cpmk' => [], 'keseluruhan' => 0.0];
            }

            $query = Cpmk::with(['asesmen', 'cpl'])->orderBy('id');

            if ($prodiId !== null && Schema::hasTable('matakuliah')) {
                $mkIds = DB::table('matakuliah')->where('prodi_id', $prodiId)->pluck('id')->all();
                $query->whereIn('matakuliah_id', $mkIds);
            }

            foreach ($query->get() as $cpmk) {
                $terbobot = 0.0;
                $pembagi = 0.0;

                foreach ($cpmk->asesmen as $a) {
                    $skor = self::skorAsesmen($mahasiswaId, $a->sumber_tipe, (int) $a->sumber_id);

                    if ($skor === null) {
                        continue;
                    }

                    $w = max(0.0, (float) $a->bobot);
                    $terbobot += $w * $skor;
                    $pembagi += $w;
                }

                if ($pembagi > 0) {
                    $pct = round($terbobot / $pembagi * 100, 1);
                    $perCpmk[$cpmk->kode] = $pct;

                    $kodeCpl = (string) ($cpmk->cpl->kode ?? 'tanpa-cpl');
                    $perCpl[$kodeCpl] ??= ['nilai' => 0.0, 'bobot' => 0.0];
                    $wCpmk = max(0.0, (float) $cpmk->bobot);
                    $perCpl[$kodeCpl]['nilai'] += $wCpmk * $pct;
                    $perCpl[$kodeCpl]['bobot'] += $wCpmk;
                }
            }
        } catch (\Throwable) {
            return ['per_cpl' => [], 'per_cpmk' => [], 'keseluruhan' => 0.0];
        }

        $ringkas = [];
        foreach ($perCpl as $kode => $aggr) {
            $ringkas[$kode] = $aggr['bobot'] > 0 ? round($aggr['nilai'] / $aggr['bobot'], 1) : 0.0;
        }

        $semuaBobot = array_sum(array_column($perCpl, 'bobot'));

        return [
            'per_cpl' => $ringkas,
            'per_cpmk' => $perCpmk,
            'keseluruhan' => $semuaBobot > 0
                ? round(array_sum(array_map(fn ($a) => $a['nilai'], $perCpl)) / $semuaBobot, 1)
                : 0.0,
        ];
    }

    /** Skor 0..1 atau null bila belum dikerjakan/dinilai. */
    private static function skorAsesmen(int $mahasiswaId, string $tipe, int $sumberId): ?float
    {
        try {
            if ($tipe === CpmkAsesmen::SUMBER_QUIZ) {
                $soal = QuizSoal::find($sumberId);

                if (! $soal || (float) $soal->bobot <= 0) {
                    return null;
                }

                $skor = QuizJawaban::where('quiz_soal_id', $soal->id)
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->value('skor');

                return $skor === null ? null : max(0.0, min(1.0, (float) $skor / (float) $soal->bobot));
            }

            if ($tipe === CpmkAsesmen::SUMBER_TUGAS) {
                $nilai = TugasKumpul::where('tugas_id', $sumberId)
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->value('nilai');

                return $nilai === null ? null : max(0.0, min(1.0, (float) $nilai / 100));
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
