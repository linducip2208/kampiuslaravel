<?php

namespace App\Services;

use App\Models\Surat;
use Illuminate\Support\Facades\DB;

class SuratNomorService
{
    public static function generate(string $jenis): string
    {
        $year = (string) date('Y');

        return DB::transaction(function () use ($jenis, $year) {
            $count = Surat::where('jenis', $jenis)
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->count();
            $seq = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            return strtoupper($jenis).'/UNIV/'.$year.'/'.$seq;
        });
    }

    public static function assignIfEmpty(Surat $surat): string
    {
        if ($surat->nomor_surat) {
            return $surat->nomor_surat;
        }
        $nomor = self::generate($surat->jenis);
        $surat->update(['nomor_surat' => $nomor]);

        return $nomor;
    }
}
