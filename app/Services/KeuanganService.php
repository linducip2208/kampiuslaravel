<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class KeuanganService
{
    public static function hasTunggakan(int $mahasiswaId): bool
    {
        return Tagihan::where('mahasiswa_id', $mahasiswaId)
            ->whereIn('status', ['belum_bayar', 'sebagian'])
            ->exists();
    }

    public static function isBlockedForKrs(int $mahasiswaId): bool
    {
        return self::hasTunggakan($mahasiswaId);
    }

    public static function isBlockedForSidang(int $mahasiswaId): bool
    {
        return self::hasTunggakan($mahasiswaId);
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public static function generateMassal(int $tahunAkademikId, string $jenis, int $nominal, ?int $prodiId = null, ?string $angkatan = null): array
    {
        $created = 0;
        $skipped = 0;

        $query = Mahasiswa::where('status', 'aktif');
        if ($prodiId) {
            $query->where('prodi_id', $prodiId);
        }
        if ($angkatan) {
            $query->where('angkatan', $angkatan);
        }

        $query->chunkById(200, function ($mahasiswas) use ($tahunAkademikId, $jenis, $nominal, &$created, &$skipped) {
            foreach ($mahasiswas as $mhs) {
                $exists = Tagihan::where('mahasiswa_id', $mhs->id)
                    ->where('tahun_akademik_id', $tahunAkademikId)
                    ->where('jenis', $jenis)
                    ->exists();
                if ($exists) {
                    $skipped++;

                    continue;
                }
                Tagihan::create([
                    'mahasiswa_id' => $mhs->id,
                    'tahun_akademik_id' => $tahunAkademikId,
                    'jenis' => $jenis,
                    'nominal' => $nominal,
                    'terbayar' => 0,
                    'status' => 'belum_bayar',
                ]);
                $created++;
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }

    public static function verifyPembayaran(int $pembayaranId, int $verifiedBy, bool $approve): void
    {
        DB::transaction(function () use ($pembayaranId, $verifiedBy, $approve) {
            $pembayaran = Pembayaran::lockForUpdate()->findOrFail($pembayaranId);

            // Idempotency: pembayaran yang sudah diproses tidak boleh diproses ulang.
            if ($pembayaran->status !== 'pending') {
                return;
            }

            $tagihan = Tagihan::lockForUpdate()->findOrFail($pembayaran->tagihan_id);

            if ($approve) {
                $pembayaran->update([
                    'status' => 'verified',
                    'verified_by' => $verifiedBy,
                    'verified_at' => now(),
                ]);
                $terbayar = (int) $tagihan->terbayar + (int) $pembayaran->nominal;
                $tagihan->update([
                    'terbayar' => $terbayar,
                    'status' => $terbayar >= (int) $tagihan->nominal ? 'lunas' : 'sebagian',
                ]);
            } else {
                $pembayaran->update([
                    'status' => 'rejected',
                    'verified_by' => $verifiedBy,
                    'verified_at' => now(),
                ]);
            }
        });
    }
}
