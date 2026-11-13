<?php

namespace App\Services\Ai;

use App\Models\Nilai;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\Schema;

/** Deteksi anomali nilai (outlier) & keuangan (duplikat/nominal janggal). */
class AnomaliService
{
    /** @return list<array{kelas_id: int, mahasiswa_id: int, na: float, z: float}> */
    public function anomaliNilai(int $kelasId, float $ambangZ = 2.0): array
    {
        $out = [];

        try {
            if (! Schema::hasTable('nilai')) {
                return [];
            }
            $rows = Nilai::where('kelas_id', $kelasId)->get(['mahasiswa_id', 'na']);
            if ($rows->count() < 5) {
                return [];
            }
            $mean = $rows->avg('na');
            $var = $rows->reduce(fn ($c, $r) => $c + ((float) $r->na - $mean) ** 2, 0) / $rows->count();
            $sd = sqrt($var);
            if ($sd <= 0) {
                return [];
            }
            foreach ($rows as $r) {
                $z = round(((float) $r->na - $mean) / $sd, 2);
                if (abs($z) >= $ambangZ) {
                    $out[] = ['kelas_id' => $kelasId, 'mahasiswa_id' => (int) $r->mahasiswa_id, 'na' => (float) $r->na, 'z' => $z];
                }
            }
        } catch (\Throwable) {
        }

        return $out;
    }

    /** @return list<array{tipe: string, keterangan: string}> */
    public function anomaliKeuangan(): array
    {
        $out = [];

        try {
            if (! Schema::hasTable('pembayaran')) {
                return [];
            }
            $duplikat = Pembayaran::selectRaw('tagihan_id, nominal, COUNT(*) as total')
                ->groupBy('tagihan_id', 'nominal')->having('total', '>', 1)->take(20)->get();
            foreach ($duplikat as $d) {
                $out[] = ['tipe' => 'duplikat', 'keterangan' => "Tagihan {$d->tagihan_id} nominal {$d->nominal} tercatat {$d->total}x"];
            }
            $janggal = Pembayaran::where('nominal', '<=', 0)->take(20)->get(['id', 'nominal']);
            foreach ($janggal as $j) {
                $out[] = ['tipe' => 'nominal_janggal', 'keterangan' => "Pembayaran #{$j->id} nominal {$j->nominal}"];
            }
        } catch (\Throwable) {
        }

        return $out;
    }
}
