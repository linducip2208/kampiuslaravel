<?php

namespace App\Services;

use App\Models\HutangDagang;
use App\Models\JurnalKeuangan;
use Illuminate\Support\Facades\DB;

/**
 * M-034: bayar utang usaha bertahap (mirror pelunasan piutang).
 * Kunci baris + idempoten batas sisa (lebih bayar dipangkas ke sisa).
 */
class HutangService
{
    /**
     * @return array{terbayar: int, status: string, dibayar: int}
     */
    public static function bayar(int $hutangId, int $nominal, ?int $by = null): array
    {
        return DB::transaction(function () use ($hutangId, $nominal, $by): array {
            $hutang = HutangDagang::whereKey($hutangId)->lockForUpdate()->firstOrFail();

            if ($nominal <= 0) {
                throw new \InvalidArgumentException('Nominal pembayaran harus positif.');
            }

            if ($hutang->status === HutangDagang::STATUS_LUNAS) {
                return ['terbayar' => (int) $hutang->terbayar, 'status' => $hutang->status, 'dibayar' => 0];
            }

            $dibayar = min($nominal, $hutang->sisa());
            $terbayar = (int) $hutang->terbayar + $dibayar;
            $status = $terbayar >= (int) $hutang->nominal
                ? HutangDagang::STATUS_LUNAS
                : ($terbayar > 0 ? HutangDagang::STATUS_SEBAGIAN : HutangDagang::STATUS_BELUM);

            $hutang->update(['terbayar' => $terbayar, 'status' => $status]);

            // Kas keluar dijurnalkan (satu baris per pembayaran nyata).
            if ($dibayar > 0) {
                JurnalKeuangan::create([
                    'tanggal' => today()->toDateString(),
                    'pembayaran_id' => null,
                    'tagihan_id' => null,
                    'jenis' => 'pengeluaran',
                    'akun_debet' => 'Utang Usaha',
                    'akun_kredit' => 'Kas/Bank',
                    'nominal' => $dibayar,
                    'keterangan' => 'Bayar hutang '.($hutang->nomor ?? "#{$hutang->id}"),
                    'created_by' => $by,
                ]);
            }

            return ['terbayar' => $terbayar, 'status' => $status, 'dibayar' => $dibayar];
        });
    }
}
