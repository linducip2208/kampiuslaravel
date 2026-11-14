<?php

namespace App\Services;

use App\Models\Coa;
use App\Models\JurnalItem;
use App\Models\JurnalUmum;
use App\Models\TutupBuku;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M-052: GL akuntansi penuh — CoA + jurnal umum berimbang + buku besar +
 * neraca saldo. Periode terkunci via tutup_buku (seperti jurnal penerimaan).
 */
class GlService
{
    /**
     * @param  list<array{coa_id: int, debet: int, kredit: int}>  $items
     */
    public static function posting(int $tahunId, string $tanggal, string $keterangan, array $items, ?string $referensi = null, ?int $by = null): JurnalUmum
    {
        if (TutupBuku::terkunci($tahunId)) {
            throw new \RuntimeException('Periode sudah tutup buku; jurnal ditolak.');
        }

        $bersih = [];
        foreach ($items as $row) {
            $d = max(0, (int) ($row['debet'] ?? 0));
            $k = max(0, (int) ($row['kredit'] ?? 0));

            if (($d > 0) === ($k > 0)) {
                throw new \InvalidArgumentException('Tiap baris wajib tepat satu sisi (debet xor kredit) positif.');
            }

            $bersih[] = ['coa_id' => (int) $row['coa_id'], 'debet' => $d, 'kredit' => $k];
        }

        if (count($bersih) < 2) {
            throw new \InvalidArgumentException('Jurnal butuh minimal 2 baris.');
        }

        $totD = array_sum(array_column($bersih, 'debet'));
        $totK = array_sum(array_column($bersih, 'kredit'));

        if ($totD <= 0 || $totD !== $totK) {
            throw new \InvalidArgumentException('Jurnal tidak seimbang (debet harus = kredit > 0).');
        }

        $coaIds = array_unique(array_column($bersih, 'coa_id'));
        $aktif = Coa::whereIn('id', $coaIds)->where('aktif', true)->count();

        if ($aktif !== count($coaIds)) {
            throw new \InvalidArgumentException('Akun tidak dikenal / nonaktif.');
        }

        return DB::transaction(function () use ($tahunId, $tanggal, $keterangan, $referensi, $by, $bersih): JurnalUmum {
            $jurnal = JurnalUmum::create([
                'tanggal' => $tanggal,
                'tahun_akademik_id' => $tahunId,
                'keterangan' => $keterangan,
                'referensi' => $referensi,
                'dibuat_oleh' => $by,
            ]);

            foreach ($bersih as $row) {
                JurnalItem::create($row + ['jurnal_id' => $jurnal->id]);
            }

            return $jurnal->fresh();
        });
    }

    /**
     * Buku besar 1 akun: baris + saldo berjalan sesuai sisi normal.
     *
     * @return array{rows: list<array{tanggal: string, keterangan: string, debet: int, kredit: int, saldo: int}>, saldo_akhir: int}
     */
    public static function bukuBesar(int $coaId, int $tahunId): array
    {
        $coa = Coa::findOrFail($coaId);
        $kreditNormal = $coa->normal === 'kredit';

        $rows = [];
        $saldo = 0;

        try {
            $items = JurnalItem::join('jurnal_umum', 'jurnal_umum.id', '=', 'jurnal_item.jurnal_id')
                ->where('jurnal_item.coa_id', $coaId)
                ->where('jurnal_umum.tahun_akademik_id', $tahunId)
                ->orderBy('jurnal_umum.tanggal')
                ->orderBy('jurnal_item.id')
                ->select(['jurnal_umum.tanggal', 'jurnal_umum.keterangan', 'jurnal_item.debet', 'jurnal_item.kredit'])
                ->get();
        } catch (\Throwable) {
            return ['rows' => [], 'saldo_akhir' => 0];
        }

        foreach ($items as $it) {
            $saldo += $kreditNormal ? ((int) $it->kredit - (int) $it->debet) : ((int) $it->debet - (int) $it->kredit);
            $rows[] = [
                'tanggal' => (string) $it->tanggal,
                'keterangan' => (string) $it->keterangan,
                'debet' => (int) $it->debet,
                'kredit' => (int) $it->kredit,
                'saldo' => $saldo,
            ];
        }

        return ['rows' => $rows, 'saldo_akhir' => $saldo];
    }

    /**
     * Neraca saldo: total debet/kredit per akun + cek seimbang.
     *
     * @return array{rows: list<array{kode: string, nama: string, debet: int, kredit: int}>, total_debet: int, total_kredit: int, seimbang: bool}
     */
    public static function neracaSaldo(int $tahunId): array
    {
        try {
            $rows = Coa::leftJoin('jurnal_item', 'jurnal_item.coa_id', '=', 'coa.id')
                ->leftJoin('jurnal_umum', function ($j) use ($tahunId): void {
                    $j->on('jurnal_umum.id', '=', 'jurnal_item.jurnal_id')
                        ->where('jurnal_umum.tahun_akademik_id', $tahunId);
                })
                ->where('coa.aktif', true)
                ->groupBy(['coa.id', 'coa.kode', 'coa.nama'])
                ->selectRaw('coa.kode, coa.nama, COALESCE(SUM(jurnal_item.debet), 0) AS debet, COALESCE(SUM(jurnal_item.kredit), 0) AS kredit')
                ->havingRaw('COALESCE(SUM(jurnal_item.debet), 0) + COALESCE(SUM(jurnal_item.kredit), 0) > 0')
                ->orderBy('coa.kode')
                ->get();
        } catch (\Throwable) {
            return ['rows' => [], 'total_debet' => 0, 'total_kredit' => 0, 'seimbang' => true];
        }

        $out = [];
        $totD = 0;
        $totK = 0;

        foreach ($rows as $r) {
            $out[] = ['kode' => (string) $r->kode, 'nama' => (string) $r->nama, 'debet' => (int) $r->debet, 'kredit' => (int) $r->kredit];
            $totD += (int) $r->debet;
            $totK += (int) $r->kredit;
        }

        return ['rows' => $out, 'total_debet' => $totD, 'total_kredit' => $totK, 'seimbang' => $totD === $totK];
    }

    public static function seederDasarCoa(): int
    {
        if (! Schema::hasTable('coa')) {
            return 0;
        }

        $dasar = [
            ['1000', 'Kas/Bank', 'aset', 'debet'],
            ['1100', 'Piutang Mahasiswa', 'aset', 'debet'],
            ['1500', 'Aset Tetap', 'aset', 'debet'],
            ['2000', 'Utang Usaha', 'kewajiban', 'kredit'],
            ['3000', 'Ekuitas', 'ekuitas', 'kredit'],
            ['4000', 'Pendapatan UKT', 'pendapatan', 'kredit'],
            ['5000', 'Beban Operasional', 'beban', 'debet'],
            ['5100', 'Beban Gaji', 'beban', 'debet'],
        ];

        $n = 0;
        foreach ($dasar as [$kode, $nama, $tipe, $normal]) {
            if (Coa::where('kode', $kode)->doesntExist()) {
                Coa::create(compact('kode', 'nama', 'tipe', 'normal'));
                $n++;
            }
        }

        return $n;
    }
}
