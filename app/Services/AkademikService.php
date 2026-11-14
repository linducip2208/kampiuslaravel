<?php

namespace App\Services;

use App\Models\KelasWaitlist;
use App\Models\Krs;
use App\Models\Nilai;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perhitungan akademik terpusat (satu sumber kebenaran).
 * IPK = Σ(bobot × SKS) / Σ(SKS). IP = sama per tahun akademik.
 *
 * Batas SKS by IPK: >=3.00 → 24, >=2.50 → 22, >=2.00 → 20, <2.00 → 18.
 * Validasi KRS terpusat: SKS-maks, prasyarat, bentrok jadwal, kuota, tunggakan.
 * (Cek tunggakan hanya baca tabel `tagihan`; tidak menulis modul keuangan.)
 */
class AkademikService
{
    /**
     * @return array{ipk: float, total_sks: int, per_tahun: array<int, array{ip: float, sks: int}>, nilai: Collection}
     */
    public static function ringkasan(int $mahasiswaId, bool $hanyaTerkunci = true): array
    {
        $query = Nilai::with('kelas.matakuliah', 'kelas.tahunAkademik')
            ->where('mahasiswa_id', $mahasiswaId);

        if ($hanyaTerkunci) {
            $query->where('is_locked', true);
        }

        $nilai = $query->get();

        $totalBobot = 0.0;
        $totalSks = 0;
        $perTahun = [];

        foreach ($nilai as $row) {
            $sks = (int) ($row->kelas->matakuliah->sks ?? 0);
            $tahunId = (int) ($row->kelas->tahun_akademik_id ?? 0);

            $totalBobot += (float) $row->bobot * $sks;
            $totalSks += $sks;

            $perTahun[$tahunId] ??= ['ip' => 0.0, 'sks' => 0, 'bobot' => 0.0];
            $perTahun[$tahunId]['bobot'] += (float) $row->bobot * $sks;
            $perTahun[$tahunId]['sks'] += $sks;
        }

        foreach ($perTahun as $tahunId => $aggr) {
            $perTahun[$tahunId] = [
                'ip' => $aggr['sks'] > 0 ? round($aggr['bobot'] / $aggr['sks'], 2) : 0.0,
                'sks' => $aggr['sks'],
            ];
        }

        return [
            'ipk' => $totalSks > 0 ? round($totalBobot / $totalSks, 2) : 0.0,
            'total_sks' => $totalSks,
            'per_tahun' => $perTahun,
            'nilai' => $nilai,
        ];
    }

    public static function batasSks(float $ipk): int
    {
        return match (true) {
            $ipk >= 3.0 => 24,
            $ipk >= 2.5 => 22,
            $ipk >= 2.0 => 20,
            default => 18,
        };
    }

    /**
     * Peta kelas_id => sks untuk daftar kelas.
     *
     * @param  list<int>  $kelasIds
     * @return Collection<int, int>
     */
    public static function sksPerKelas(array $kelasIds): Collection
    {
        $kelasIds = array_values(array_unique(array_map('intval', $kelasIds)));

        if ($kelasIds === [] || ! Schema::hasTable('kelas') || ! Schema::hasTable('matakuliah')) {
            return collect($kelasIds)->mapWithKeys(fn (int $id): array => [$id => 0]);
        }

        return DB::table('kelas')
            ->leftJoin('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
            ->whereIn('kelas.id', $kelasIds)
            ->pluck('matakuliah.sks', 'kelas.id')
            ->map(fn ($sks): int => (int) ($sks ?? 0));
    }

    /**
     * Cek tunggakan tagihan (read-only modul keuangan).
     */
    public static function adaTunggakan(int $mahasiswaId): bool
    {
        $tabel = Schema::hasTable('tagihan') ? 'tagihan' : (Schema::hasTable('tagihans') ? 'tagihans' : null);

        if (! $tabel) {
            return false;
        }

        try {
            return DB::table($tabel)
                ->where('mahasiswa_id', $mahasiswaId)
                ->where('status', '!=', 'lunas')
                ->whereColumn('terbayar', '<', 'nominal')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Jumlah kursi terisi kelas (hanya KRS aktif: draft/diajukan/disetujui).
     * KRS ditolak tidak memakan kuota sehingga waitlist bisa dipromosikan.
     *
     * @param  int|null  $abaikanKrsId  KRS yang dikecualikan (milik pengaju sendiri
     *                                  saat revisi / milik KRS yang sedang disetujui PA).
     */
    public static function kursiTerisi(int $kelasId, ?int $abaikanKrsId = null): int
    {
        try {
            $query = DB::table('krs_detail')
                ->join('krs', 'krs.id', '=', 'krs_detail.krs_id')
                ->where('krs_detail.kelas_id', $kelasId)
                ->whereIn('krs.status', [Krs::STATUS_DRAFT, Krs::STATUS_DIAJUKAN, Krs::STATUS_DISETUJUI]);

            if ($abaikanKrsId !== null) {
                $query->where('krs.id', '!=', $abaikanKrsId);
            }

            return (int) $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * M-025/P3: hitung kursi terisi untuk banyak kelas sekaligus (1 query).
     *
     * @param  list<int>  $kelasIds
     * @return array<int, int> peta kelas_id => terisi
     */
    public static function kursiTerisiBanyak(array $kelasIds, ?int $abaikanKrsId = null): array
    {
        $kelasIds = array_values(array_unique(array_map('intval', $kelasIds)));

        if ($kelasIds === []) {
            return [];
        }

        try {
            $query = DB::table('krs_detail')
                ->join('krs', 'krs.id', '=', 'krs_detail.krs_id')
                ->whereIn('krs_detail.kelas_id', $kelasIds)
                ->whereIn('krs.status', [Krs::STATUS_DRAFT, Krs::STATUS_DIAJUKAN, Krs::STATUS_DISETUJUI]);

            if ($abaikanKrsId !== null) {
                $query->where('krs.id', '!=', $abaikanKrsId);
            }

            return $query
                ->groupBy('krs_detail.kelas_id')
                ->selectRaw('krs_detail.kelas_id, count(*) as terisi')
                ->pluck('terisi', 'krs_detail.kelas_id')
                ->map(fn ($v): int => (int) $v)
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function kuotaKelas(int $kelasId): int
    {
        try {
            return (int) DB::table('kelas')->where('id', $kelasId)->value('kuota');
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * M-023: kandidat DO — mahasiswa aktif yang telah menempuh minimal
     * $minSemester semester (distinct tahun_akademik pada nilai terkunci)
     * dengan IPK di bawah $batasIpk. Aturan konservatif & terdokumentasi;
     * penerapan status tetap eksplisit via command --terapkan (audit trail).
     *
     * @return Collection<int, array{mahasiswa_id: int, nim: string, ipk: float, semester: int}>
     */
    public static function kandidatDo(int $minSemester = 4, float $batasIpk = 2.0): Collection
    {
        try {
            $rows = DB::table('nilai')
                ->join('kelas', 'kelas.id', '=', 'nilai.kelas_id')
                ->join('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
                ->join('mahasiswa', 'mahasiswa.id', '=', 'nilai.mahasiswa_id')
                ->where('nilai.is_locked', true)
                ->where('mahasiswa.status', 'aktif')
                ->groupBy('nilai.mahasiswa_id', 'mahasiswa.nim')
                ->selectRaw('nilai.mahasiswa_id, mahasiswa.nim as nim')
                ->selectRaw('sum(nilai.bobot * matakuliah.sks) / nullif(sum(matakuliah.sks), 0) as ipk')
                ->selectRaw('count(distinct kelas.tahun_akademik_id) as semester')
                ->get();
        } catch (\Throwable) {
            return collect();
        }

        return $rows
            ->map(fn ($r) => [
                'mahasiswa_id' => (int) $r->mahasiswa_id,
                'nim' => (string) $r->nim,
                'ipk' => round((float) $r->ipk, 2),
                'semester' => (int) $r->semester,
            ])
            ->filter(fn ($r) => $r['semester'] >= $minSemester && $r['ipk'] < $batasIpk)
            ->values();
    }

    public static function kelasPenuh(int $kelasId, ?int $abaikanKrsId = null): bool
    {
        $kuota = self::kuotaKelas($kelasId);

        if ($kuota <= 0) {
            return false;
        }

        return self::kursiTerisi($kelasId, $abaikanKrsId) >= $kuota;
    }

    /**
     * Validasi KRS terpusat. Dipakai saat pengajuan mahasiswa
     * maupun verifikasi ulang saat persetujuan PA (hardening).
     *
     * @param  list<int>  $kelasIds
     * @param  int|null  $abaikanKrsId  KRS yang dikecualikan dari hitung kuota
     * @return list<string> daftar pesan kesalahan (kosong = lolos)
     */
    public static function validasiKrs(int $mahasiswaId, array $kelasIds, float $ipk, ?int $abaikanKrsId = null): array
    {
        $kelasIds = array_values(array_unique(array_map('intval', $kelasIds)));
        $pesan = [];

        if ($kelasIds === []) {
            return ['Pilih minimal satu kelas.'];
        }

        // SKS-maks by IPK.
        $sksMap = self::sksPerKelas($kelasIds);
        $totalSks = (int) $sksMap->sum();
        $batas = self::batasSks($ipk);

        if ($totalSks > $batas) {
            $pesan[] = "Total SKS ({$totalSks}) melebihi batas maksimal {$batas} SKS untuk IPK {$ipk}.";
        }

        // Tunggakan (blokir, read-only keuangan).
        if (self::adaTunggakan($mahasiswaId)) {
            $pesan[] = 'Pengajuan KRS diblokir: masih ada tagihan menunggak. Lunasi tagihan terlebih dahulu.';
        }

        // Prasyarat: kolom legacy tunggal + relasi ganda mk_prasyarat (jenis=prasyarat).
        // M-025/P3: 1 query lulus untuk semua prasyarat (bukan exists() per loop).
        try {
            $kelas = DB::table('kelas')
                ->leftJoin('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
                ->whereIn('kelas.id', $kelasIds)
                ->select(['kelas.id as kelas_id', 'matakuliah.id as mk_id', 'matakuliah.kode', 'matakuliah.nama as mk_nama', 'matakuliah.prasyarat_id'])
                ->get();

            $tambahan = collect();
            $mkIds = $kelas->pluck('mk_id')->filter()->map(fn ($v): int => (int) $v)->all();
            if ($mkIds !== [] && Schema::hasTable('mk_prasyarat')) {
                $tambahan = DB::table('mk_prasyarat')
                    ->whereIn('matakuliah_id', $mkIds)
                    ->where('jenis', 'prasyarat')
                    ->get(['matakuliah_id', 'prasyarat_id']);
            }

            $wajibPerKelas = [];
            $semuaPra = [];

            foreach ($kelas as $k) {
                $wajib = [];
                if ($k->prasyarat_id) {
                    $wajib[(int) $k->prasyarat_id] = true;
                }
                foreach ($tambahan->where('matakuliah_id', $k->mk_id) as $rel) {
                    $wajib[(int) $rel->prasyarat_id] = true;
                }
                $wajibPerKelas[$k->kelas_id] = ['nama' => $k->mk_nama ?? $k->kode ?? 'matakuliah', 'pra' => array_keys($wajib)];

                foreach (array_keys($wajib) as $praId) {
                    $semuaPra[$praId] = true;
                }
            }

            $lulusMk = [];
            if ($semuaPra !== []) {
                $lulusMk = DB::table('nilai')
                    ->join('kelas', 'kelas.id', '=', 'nilai.kelas_id')
                    ->where('nilai.mahasiswa_id', $mahasiswaId)
                    ->where('nilai.bobot', '>', 0)
                    ->whereIn('kelas.matakuliah_id', array_keys($semuaPra))
                    ->distinct()
                    ->pluck('kelas.matakuliah_id')
                    ->map(fn ($v): int => (int) $v)
                    ->flip()
                    ->all();
            }

            foreach ($wajibPerKelas as $data) {
                foreach ($data['pra'] as $praId) {
                    if (! isset($lulusMk[$praId])) {
                        $pesan[] = "Prasyarat belum lulus untuk mengambil {$data['nama']}.";
                        break;
                    }
                }
            }
        } catch (\Throwable) {
            // Tabel pendukung belum ada — lewati cek.
        }

        // Bentrok jadwal.
        try {
            $jadwal = DB::table('kelas')->whereIn('id', $kelasIds)
                ->select(['id', 'nama', 'hari', 'jam_mulai', 'jam_selesai'])->get()
                ->filter(fn ($k) => $k->hari && $k->jam_mulai && $k->jam_selesai)
                ->values();

            foreach ($jadwal as $i => $a) {
                foreach ($jadwal as $j => $b) {
                    if ($j <= $i || $a->hari !== $b->hari) {
                        continue;
                    }

                    if ($a->jam_mulai < $b->jam_selesai && $b->jam_mulai < $a->jam_selesai) {
                        $pesan[] = "Jadwal bentrok: kelas {$a->nama} dengan {$b->nama} ({$a->hari} {$a->jam_mulai}-{$a->jam_selesai}).";
                    }
                }
            }
        } catch (\Throwable) {
            // Kolom jadwal belum ada — lewati cek.
        }

        // Kuota (hitung KRS aktif saja; KRS ditolak tidak memakan kuota).
        // M-025/P3: 2 query untuk semua kelas (bukan 2N).
        $kuotas = [];
        $terisiMap = self::kursiTerisiBanyak($kelasIds, $abaikanKrsId);

        try {
            $kuotas = DB::table('kelas')->whereIn('id', $kelasIds)->pluck('kuota', 'id')
                ->map(fn ($v): int => (int) ($v ?? 0))->all();
        } catch (\Throwable) {
            // Tabel kelas belum ada — lewati cek kuota.
        }

        foreach ($kelasIds as $kelasId) {
            $kuota = $kuotas[$kelasId] ?? 0;

            if ($kuota <= 0) {
                continue;
            }

            $terisi = $terisiMap[$kelasId] ?? 0;

            if ($terisi >= $kuota) {
                $pesan[] = "Kuota kelas #{$kelasId} penuh ({$terisi}/{$kuota}). Silakan masuk waiting list.";
            }
        }

        return $pesan;
    }

    /**
     * Posisi antrean waitlist (1-based) untuk mahasiswa pada kelas.
     */
    public static function posisiWaitlist(int $kelasId, int $mahasiswaId): ?int
    {
        if (! Schema::hasTable('kelas_waitlist')) {
            return null;
        }

        $antre = KelasWaitlist::query()
            ->where('kelas_id', $kelasId)
            ->where('status', KelasWaitlist::STATUS_MENUNGGU)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('mahasiswa_id')
            ->all();

        $pos = array_search($mahasiswaId, $antre, true);

        return $pos === false ? null : $pos + 1;
    }

    /**
     * Promosikan antrean terdepan bila kuota tersedia.
     *
     * @return int|null mahasiswa_id yang dipromosikan
     */
    public static function promosikanWaitlist(int $kelasId): ?int
    {
        if (! Schema::hasTable('kelas_waitlist')) {
            return null;
        }

        if (self::kelasPenuh($kelasId)) {
            return null;
        }

        $entri = KelasWaitlist::query()
            ->where('kelas_id', $kelasId)
            ->where('status', KelasWaitlist::STATUS_MENUNGGU)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if (! $entri) {
            return null;
        }

        $entri->update(['status' => KelasWaitlist::STATUS_DIPROMOSIKAN]);

        return (int) $entri->mahasiswa_id;
    }
}
