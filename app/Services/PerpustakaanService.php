<?php

namespace App\Services;

use App\Models\PerpusEksemplar;
use App\Models\PerpusPinjam;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Sirkulasi perpustakaan: maks 3 pinjam aktif, tempo 14 hari,
 * denda Rp1.000/hari keterlambatan (aturan terdokumentasi, teruji).
 */
class PerpustakaanService
{
    public const MAKS_AKTIF = 3;

    public const HARI_PINJAM = 14;

    public const DENDA_PER_HARI = 1000;

    public static function aktifCount(int $userId): int
    {
        try {
            return (int) PerpusPinjam::where('user_id', $userId)
                ->where('status', PerpusPinjam::STATUS_PINJAM)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function hitungDenda(string $jatuhTempo, ?string $kembali = null): int
    {
        try {
            $jatuh = new \DateTimeImmutable($jatuhTempo);
            $kembaliDt = $kembali ? new \DateTimeImmutable($kembali) : new \DateTimeImmutable('today');
            $telat = (int) $jatuh->diff($kembaliDt)->format('%r%a');

            return $telat > 0 ? $telat * self::DENDA_PER_HARI : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @throws InvalidArgumentException|RuntimeException */
    public static function pinjam(int $eksemplarId, int $userId): PerpusPinjam
    {
        if (self::aktifCount($userId) >= self::MAKS_AKTIF) {
            throw new RuntimeException('Batas maksimal '.self::MAKS_AKTIF.' pinjaman aktif tercapai.');
        }

        return DB::transaction(function () use ($eksemplarId, $userId): PerpusPinjam {
            $eks = PerpusEksemplar::whereKey($eksemplarId)->lockForUpdate()->firstOrFail();

            if ($eks->status !== PerpusEksemplar::STATUS_TERSEDIA) {
                throw new RuntimeException('Eksemplar tidak tersedia (status: '.$eks->status.').');
            }

            $pinjam = PerpusPinjam::create([
                'eksemplar_id' => $eks->id,
                'user_id' => $userId,
                'tgl_pinjam' => now()->toDateString(),
                'jatuh_tempo' => now()->addDays(self::HARI_PINJAM)->toDateString(),
                'status' => PerpusPinjam::STATUS_PINJAM,
            ]);
            $eks->update(['status' => PerpusEksemplar::STATUS_DIPINJAM]);

            return $pinjam->fresh();
        });
    }

    public static function kembali(int $pinjamId, string $kondisi = 'baik'): PerpusPinjam
    {
        return DB::transaction(function () use ($pinjamId, $kondisi): PerpusPinjam {
            $pinjam = PerpusPinjam::whereKey($pinjamId)->lockForUpdate()->firstOrFail();

            if ($pinjam->status !== PerpusPinjam::STATUS_PINJAM) {
                throw new RuntimeException('Peminjaman sudah selesai.');
            }

            $hariIni = now()->toDateString();
            $denda = self::hitungDenda($pinjam->jatuh_tempo->toDateString(), $hariIni);

            if (in_array($kondisi, [PerpusEksemplar::STATUS_HILANG, PerpusEksemplar::STATUS_RUSAK], true)) {
                $pinjam->update(['status' => $kondisi, 'tgl_kembali' => $hariIni, 'denda_rp' => $denda]);
                PerpusEksemplar::whereKey($pinjam->eksemplar_id)->update(['status' => $kondisi]);

                return $pinjam->fresh();
            }

            $pinjam->update(['status' => PerpusPinjam::STATUS_KEMBALI, 'tgl_kembali' => $hariIni, 'denda_rp' => $denda]);
            PerpusEksemplar::whereKey($pinjam->eksemplar_id)->update(['status' => PerpusEksemplar::STATUS_TERSEDIA]);

            return $pinjam->fresh();
        });
    }
}
