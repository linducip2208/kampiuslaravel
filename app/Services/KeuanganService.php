<?php

namespace App\Services;

use App\Models\DendaRule;
use App\Models\JurnalKeuangan;
use App\Models\Mahasiswa;
use App\Models\MahasiswaBeasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\TagihanCicilan;
use App\Models\UktTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
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
     * Cari template UKT paling spesifik: prodi+angkatan+jalur > ... > global.
     */
    public static function resolveTemplateNominal(?int $prodiId, ?string $angkatan, ?string $jalur, string $jenis = 'UKT'): ?UktTemplate
    {
        return UktTemplate::where('jenis', $jenis)
            ->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('prodi_id')->orWhere('prodi_id', $prodiId))
            ->where(fn ($q) => $q->whereNull('angkatan')->orWhere('angkatan', $angkatan))
            ->where(fn ($q) => $q->whereNull('jalur')->orWhere('jalur', $jalur))
            ->orderByRaw('(prodi_id IS NOT NULL) + (angkatan IS NOT NULL) + (jalur IS NOT NULL) DESC')
            ->orderByDesc('id')
            ->first();
    }

    public static function potonganBeasiswaOtomatis(int $mahasiswaId, ?int $tahunAkademikId, int $nominal): array
    {
        $beasiswa = MahasiswaBeasiswa::with('beasiswa')
            ->where('mahasiswa_id', $mahasiswaId)
            ->where('status', 'aktif')
            ->where(function ($q) use ($tahunAkademikId) {
                $q->whereNull('tahun_akademik_id')->orWhere('tahun_akademik_id', $tahunAkademikId);
            })
            ->get()
            ->map->beasiswa
            ->filter(fn ($b) => $b && $b->aktif)
            ->first();

        if (! $beasiswa) {
            return ['beasiswa_id' => null, 'diskon' => 0];
        }

        return ['beasiswa_id' => $beasiswa->id, 'diskon' => $beasiswa->hitungPotongan($nominal)];
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public static function generateMassal(
        int $tahunAkademikId,
        string $jenis,
        int $nominal,
        ?int $prodiId = null,
        ?string $angkatan = null,
        ?string $jalur = null,
        ?string $jatuhTempo = null,
        int $cicilan = 1,
    ): array {
        return DB::transaction(function () use ($tahunAkademikId, $jenis, $nominal, $prodiId, $angkatan, $jalur, $jatuhTempo, $cicilan) {
            $created = 0;
            $skipped = 0;

            $query = Mahasiswa::where('status', 'aktif');
            if ($prodiId) {
                $query->where('prodi_id', $prodiId);
            }
            if ($angkatan) {
                $query->where('angkatan', $angkatan);
            }
            if ($jalur) {
                $query->where('jalur', $jalur);
            }

            $query->chunkById(200, function ($mahasiswas) use ($tahunAkademikId, $jenis, $nominal, $jatuhTempo, $cicilan, &$created, &$skipped) {
                foreach ($mahasiswas as $mhs) {
                    $exists = Tagihan::where('mahasiswa_id', $mhs->id)
                        ->where('tahun_akademik_id', $tahunAkademikId)
                        ->where('jenis', $jenis)
                        ->exists();
                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    $dasar = $nominal;
                    if ($dasar <= 0) {
                        $template = self::resolveTemplateNominal($mhs->prodi_id, (string) $mhs->angkatan, $mhs->jalur, $jenis);
                        $dasar = $template?->nominal ?? 0;
                    }
                    if ($dasar <= 0) {
                        $skipped++;

                        continue;
                    }

                    $potongan = self::potonganBeasiswaOtomatis($mhs->id, $tahunAkademikId, $dasar);
                    $netto = max(0, $dasar - $potongan['diskon']);
                    $cicilanCount = max(1, $cicilan);

                    try {
                        $tagihan = Tagihan::create([
                            'mahasiswa_id' => $mhs->id,
                            'tahun_akademik_id' => $tahunAkademikId,
                            'jenis' => $jenis,
                            'nominal' => $netto,
                            'terbayar' => 0,
                            'status' => 'belum_bayar',
                            'beasiswa_id' => $potongan['beasiswa_id'],
                            'diskon' => $potongan['diskon'],
                            'jatuh_tempo' => $jatuhTempo,
                            'denda' => 0,
                        ]);
                    } catch (QueryException $e) {
                        // Race: baris duplikat dibuat paralel — lewati dengan aman.
                        $skipped++;

                        continue;
                    }

                    self::bagiCicilan($tagihan, $cicilanCount, $jatuhTempo);
                    $created++;
                }
            });

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    public static function bagiCicilan(Tagihan $tagihan, int $jumlah, ?string $jatuhTempo): void
    {
        $jumlah = max(1, $jumlah);
        $pokok = (int) $tagihan->nominal;
        $perCicilan = intdiv($pokok, $jumlah);
        $sisa = $pokok - ($perCicilan * $jumlah);

        $rows = [];
        $now = now();
        for ($i = 1; $i <= $jumlah; $i++) {
            $rows[] = [
                'tagihan_id' => $tagihan->id,
                'urutan' => $i,
                'nominal' => $perCicilan + ($i === $jumlah ? $sisa : 0),
                'terbayar' => 0,
                'jatuh_tempo' => $jatuhTempo,
                'status' => 'belum_bayar',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        TagihanCicilan::insert($rows);
    }

    /**
     * Terapkan denda otomatis (idempoten: nilai denda ditimpa, bukan diakumulasi).
     *
     * @return array{updated: int, total_denda: int}
     */
    public static function applyDendaOtomatis(?int $tahunAkademikId = null): array
    {
        $rules = DendaRule::where('aktif', true)
            ->when($tahunAkademikId, fn ($q) => $q->where(fn ($w) => $w->whereNull('tahun_akademik_id')->orWhere('tahun_akademik_id', $tahunAkademikId)))
            ->orderByDesc('terlambat_hari_min')
            ->get();

        if ($rules->isEmpty()) {
            return ['updated' => 0, 'total_denda' => 0];
        }

        $updated = 0;
        $totalDenda = 0;

        Tagihan::whereIn('status', ['belum_bayar', 'sebagian'])
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<', today())
            ->when($tahunAkademikId, fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
            ->chunkById(200, function ($tagihans) use ($rules, &$updated, &$totalDenda) {
                foreach ($tagihans as $tagihan) {
                    $hari = $tagihan->hariTerlambat();
                    $rule = $rules->first(
                        fn ($r) => $r->jenis === $tagihan->jenis && $hari >= (int) $r->terlambat_hari_min
                    ) ?? $rules->first(fn ($r) => $hari >= (int) $r->terlambat_hari_min);

                    if (! $rule) {
                        continue;
                    }

                    $denda = $rule->hitungDenda((int) $tagihan->nominal, $hari);
                    $tagihan->update(['denda' => $denda]);
                    $updated++;
                    $totalDenda += $denda;
                }
            });

        return ['updated' => $updated, 'total_denda' => $totalDenda];
    }

    /**
     * Ringkasan tunggakan untuk dashboard keuangan.
     *
     * @return array{total_tagihan: int, total_terbayar: int, total_denda: int, total_sisa: int, menunggak: int, per_prodi: array}
     */
    public static function tunggakanSummary(?int $tahunAkademikId = null): array
    {
        $query = Tagihan::query()->whereIn('status', ['belum_bayar', 'sebagian']);
        if ($tahunAkademikId) {
            $query->where('tahun_akademik_id', $tahunAkademikId);
        }

        $rows = $query->with('mahasiswa.prodi')->get();

        $perProdi = [];
        $totalSisa = 0;
        $totalDenda = 0;
        $totalNominal = 0;
        $totalTerbayar = 0;

        foreach ($rows as $tagihan) {
            $sisa = $tagihan->sisa();
            $totalSisa += $sisa;
            $totalDenda += (int) $tagihan->denda;
            $totalNominal += (int) $tagihan->nominal;
            $totalTerbayar += (int) $tagihan->terbayar;

            $nama = $tagihan->mahasiswa->prodi->nama ?? 'Tanpa Prodi';
            if (! isset($perProdi[$nama])) {
                $perProdi[$nama] = ['tagihan' => 0, 'sisa' => 0, 'mahasiswa' => 0];
            }
            $perProdi[$nama]['tagihan']++;
            $perProdi[$nama]['sisa'] += $sisa;
        }

        foreach ($perProdi as $nama => $data) {
            $perProdi[$nama]['mahasiswa'] = Tagihan::whereIn('status', ['belum_bayar', 'sebagian'])
                ->whereHas('mahasiswa.prodi', fn ($q) => $q->where('nama', $nama))
                ->when($tahunAkademikId, fn ($q) => $q->where('tahun_akademik_id', $tahunAkademikId))
                ->distinct('mahasiswa_id')
                ->count('mahasiswa_id');
        }

        return [
            'total_tagihan' => $totalNominal,
            'total_terbayar' => $totalTerbayar,
            'total_denda' => $totalDenda,
            'total_sisa' => $totalSisa,
            'menunggak' => $rows->count(),
            'per_prodi' => $perProdi,
        ];
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
                    'status' => $terbayar >= $tagihan->totalTagihan() ? 'lunas' : 'sebagian',
                ]);
                self::distribusikanKeCicilan($tagihan->fresh(), (int) $pembayaran->nominal);
                self::postPenerimaan($pembayaran->fresh(), $verifiedBy);
            } else {
                $pembayaran->update([
                    'status' => 'rejected',
                    'verified_by' => $verifiedBy,
                    'verified_at' => now(),
                ]);
            }
        });
    }

    protected static function distribusikanKeCicilan(Tagihan $tagihan, int $nominalBayar): void
    {
        $sisaBayar = $nominalBayar;

        /** @var TagihanCicilan $cicilan */
        foreach ($tagihan->cicilan()->lockForUpdate()->get() as $cicilan) {
            if ($sisaBayar <= 0) {
                break;
            }
            $kurang = $cicilan->sisa();
            if ($kurang <= 0) {
                continue;
            }
            $alokasi = min($kurang, $sisaBayar);
            $terbayar = (int) $cicilan->terbayar + $alokasi;
            $cicilan->update([
                'terbayar' => $terbayar,
                'status' => $terbayar >= (int) $cicilan->nominal ? 'lunas' : 'sebagian',
            ]);
            $sisaBayar -= $alokasi;
        }
    }

    /**
     * Jurnal penerimaan kas: Kas/Bank (D) — Piutang tagihan (K).
     * Idempoten per (pembayaran_id, penerimaan).
     */
    public static function postPenerimaan(Pembayaran $pembayaran, ?int $createdBy = null): JurnalKeuangan
    {
        $nominal = (int) $pembayaran->nominal;

        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal jurnal penerimaan harus positif.');
        }

        $jurnal = JurnalKeuangan::firstOrCreate(
            ['pembayaran_id' => $pembayaran->id, 'jenis' => 'penerimaan'],
            [
                'tanggal' => $pembayaran->verified_at?->toDateString() ?? today()->toDateString(),
                'tagihan_id' => $pembayaran->tagihan_id,
                'akun_debet' => 'Kas/Bank',
                'akun_kredit' => 'Piutang '.($pembayaran->tagihan?->jenis ?? 'lain'),
                'nominal' => $nominal,
                'keterangan' => 'Penerimaan '.$pembayaran->metode.' ref '.$pembayaran->reference,
                'created_by' => $createdBy,
            ]
        );

        // Single-row double-entry tetap wajib seimbang: debet ≠ kredit, nominal > 0.
        if ($jurnal->akun_debet === $jurnal->akun_kredit || (int) $jurnal->nominal <= 0) {
            throw new \RuntimeException('Jurnal penerimaan tidak seimbang.');
        }

        return $jurnal;
    }

    /**
     * Refund pembayaran verified: kurangi terbayar, catat jurnal reversal.
     * Return false bila pembayaran tidak eligible (belum verified / sudah refund).
     */
    public static function refundPembayaran(int $pembayaranId, int $by, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($pembayaranId, $by, $reason) {
            $pembayaran = Pembayaran::lockForUpdate()->findOrFail($pembayaranId);

            if ($pembayaran->status !== 'verified' || $pembayaran->refunded_at !== null) {
                return false;
            }

            $tagihan = Tagihan::lockForUpdate()->findOrFail($pembayaran->tagihan_id);

            $pembayaran->update([
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);

            $terbayar = max(0, (int) $tagihan->terbayar - (int) $pembayaran->nominal);
            $tagihan->update([
                'terbayar' => $terbayar,
                'status' => $terbayar <= 0 ? 'belum_bayar' : ($terbayar >= $tagihan->totalTagihan() ? 'lunas' : 'sebagian'),
            ]);

            JurnalKeuangan::firstOrCreate(
                ['pembayaran_id' => $pembayaran->id, 'jenis' => 'refund'],
                [
                    'tanggal' => today()->toDateString(),
                    'tagihan_id' => $pembayaran->tagihan_id,
                    'akun_debet' => 'Piutang '.($tagihan->jenis ?? 'lain'),
                    'akun_kredit' => 'Kas/Bank',
                    'nominal' => (int) $pembayaran->nominal,
                    'keterangan' => 'Refund '.($reason ?? '-').' ref '.$pembayaran->reference,
                    'created_by' => $by,
                ]
            );

            return true;
        });
    }

    /**
     * Rekonsiliasi mutasi bank (CSV: reference,nominal[,status]).
     * Cocok nominal persis → tandai reconciled_at.
     *
     * @return array{matched: int, selisih: array<int, array{reference: string, bank: int, sistem: int}>, tak_dikenal: array<int, string>}
     */
    public static function rekonsiliasiCsv(string $content): array
    {
        $matched = 0;
        $selisih = [];
        $takDikenal = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($content)) ?: [] as $i => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $delimiter = str_contains($line, ';') && ! str_contains($line, ',') ? ';' : ',';
            $cols = str_getcsv($line, $delimiter);

            // Lewati baris header.
            if ($i === 0 && isset($cols[0]) && ! preg_match('/^(SIM-|SIMAK-|simak-|TRX)/i', trim((string) $cols[0]))) {
                continue;
            }

            $reference = trim((string) ($cols[0] ?? ''));
            $nominal = (int) preg_replace('/\D+/', '', (string) ($cols[1] ?? ''));

            if ($reference === '') {
                continue;
            }

            $pembayaran = Pembayaran::where('reference', $reference)->first();

            if (! $pembayaran) {
                $takDikenal[] = $reference;

                continue;
            }

            if ((int) $pembayaran->nominal !== $nominal) {
                $selisih[] = ['reference' => $reference, 'bank' => $nominal, 'sistem' => (int) $pembayaran->nominal];

                continue;
            }

            if ($pembayaran->reconciled_at === null) {
                $pembayaran->update(['reconciled_at' => now()]);
            }

            $matched++;
        }

        return ['matched' => $matched, 'selisih' => $selisih, 'tak_dikenal' => $takDikenal];
    }

    /**
     * Tagihan untuk pengingat WA: H-7 (belum jatuh tempo) atau H+7 (menunggak).
     *
     * @return Collection<int, Tagihan>
     */
    public static function tagihanUntukPengingat(string $arah)
    {
        $target = $arah === 'h-7' ? today()->addDays(7) : today()->subDays(7);
        $kolom = $arah === 'h-7' ? 'notif_h7_at' : 'notif_hp7_at';

        return Tagihan::with(['mahasiswa.user'])
            ->whereIn('status', ['belum_bayar', 'sebagian'])
            ->whereDate('jatuh_tempo', $target)
            ->whereNull($kolom)
            ->get();
    }

    public static function tandaiPengingatTerkirim(Tagihan $tagihan, string $arah): void
    {
        $tagihan->update([$arah === 'h-7' ? 'notif_h7_at' : 'notif_hp7_at' => now()]);
    }

    /** Pesan penagihan WA untuk satu tagihan. */
    public static function pesanPengingat(Tagihan $tagihan, string $arah): string
    {
        $nama = $tagihan->mahasiswa->user->name ?? 'Mahasiswa';
        $sisa = (int) $tagihan->nominal + (int) $tagihan->denda - (int) $tagihan->terbayar;
        $jatuh = $tagihan->jatuh_tempo instanceof Carbon
            ? $tagihan->jatuh_tempo->translatedFormat('d M Y')
            : (string) $tagihan->jatuh_tempo;

        return $arah === 'h-7'
            ? "Yth. {$nama}, tagihan {$tagihan->jenis} Rp ".number_format($sisa, 0, ',', '.')." jatuh tempo {$jatuh} (7 hari lagi). Bayar via kampus / VA / QRIS. Terima kasih."
            : "Yth. {$nama}, tagihan {$tagihan->jenis} Rp ".number_format($sisa, 0, ',', '.')." telah lewat jatuh tempo {$jatuh} (7 hari). Segera lunasi agar KRS/sidang tidak diblokir.";
    }
}
