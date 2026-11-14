<?php

namespace App\Services;

use App\Models\AiKuota;
use Illuminate\Support\Facades\Schema;

/**
 * M-058: kuota harian + estimasi biaya AI per user per jenis.
 * Tarif per 1 panggilan (Rp) dan batas harian dari config/ai.php.
 */
class AiKuotaService
{
    public static function batasHarian(): int
    {
        return (int) config('ai.batas_harian', 100);
    }

    public static function tarif(string $jenis): int
    {
        $tarif = config('ai.tarif', []);

        return (int) ($tarif[$jenis] ?? $tarif['default'] ?? 0);
    }

    public static function terpakaiHariIni(?int $userId, string $jenis): int
    {
        if (! Schema::hasTable('ai_kuota')) {
            return 0;
        }

        try {
            // whereDate (bukan where exact): SQLite menyimpan kolom date
            // sebagai teks 'Y-m-d H:i:s', MySQL sebagai DATE — whereDate
            // portabel di keduanya.
            return (int) AiKuota::where('user_id', $userId)
                ->whereDate('tanggal', today())
                ->where('jenis', $jenis)
                ->value('jumlah');
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function boleh(?int $userId, string $jenis): bool
    {
        return self::terpakaiHariIni($userId, $jenis) < self::batasHarian();
    }

    /** Catat 1 pemakaian + akumulasi estimasi biaya. Idempoten per baris unik. */
    public static function catat(?int $userId, string $jenis): void
    {
        if (! Schema::hasTable('ai_kuota')) {
            return;
        }

        try {
            $row = AiKuota::where('user_id', $userId)
                ->where('jenis', $jenis)
                ->whereDate('tanggal', today())
                ->first();

            if (! $row) {
                $row = new AiKuota([
                    'user_id' => $userId,
                    'tanggal' => today()->toDateString(),
                    'jenis' => $jenis,
                    'jumlah' => 0,
                    'estimasi_biaya' => 0,
                ]);
            }

            $row->jumlah = ((int) ($row->jumlah ?? 0)) + 1;
            $row->estimasi_biaya = ((int) ($row->estimasi_biaya ?? 0)) + self::tarif($jenis);
            $row->save();
        } catch (\Throwable) {
        }
    }
}
