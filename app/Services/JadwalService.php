<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\JurnalDosen;
use App\Models\Kelas;
use App\Models\PeminjamanRuang;
use App\Models\Pertemuan;
use App\Models\Ruang;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penjadwalan anti-bentrok + pinjam ruang + presensi dosen/BAP.
 * Satu sumber kebenaran untuk konflik ruang/dosen.
 */
class JadwalService
{
    /** @var list<string> */
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /** @var list<array{string, string}> */
    public const SLOT_DEFAULT = [
        ['07:00', '09:30'],
        ['10:00', '12:30'],
        ['13:00', '15:30'],
        ['16:00', '18:30'],
    ];

    public static function jamOverlap(string $mulaiA, string $selesaiA, string $mulaiB, string $selesaiB): bool
    {
        $toMin = static fn (string $j): int => ((int) substr($j, 0, 2)) * 60 + ((int) substr($j, 3, 2));

        return $toMin($mulaiA) < $toMin($selesaiB) && $toMin($mulaiB) < $toMin($selesaiA);
    }

    /**
     * M-025/P1: predikat overlap jam di sisi DB (kolom TIME, format HH:MM:SS).
     * Input dinormalisasi ke HH:MM:SS agar batas tepat (10:00 vs 10:00:00)
     * tidak salah dianggap overlap.
     */
    private static function normalisasiJam(string $jam): string
    {
        $jam = substr(trim($jam), 0, 5);

        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $jam) ? $jam : $jam.':00';
    }

    /**
     * Cek bentrok ruang pada hari+jam tertentu (jadwal kuliah mingguan).
     */
    public static function ruangBentrok(?int $ruangId, string $hari, string $mulai, string $selesai, ?int $ignoreKelasId = null): bool
    {
        if (! $ruangId) {
            return false;
        }

        $mulai = self::normalisasiJam($mulai);
        $selesai = self::normalisasiJam($selesai);

        return Kelas::where('ruang_id', $ruangId)
            ->where('hari', $hari)
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai')
            ->when($ignoreKelasId, fn ($q) => $q->where('id', '!=', $ignoreKelasId))
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai)
            ->exists();
    }

    /**
     * Cek bentrok dosen pada hari+jam tertentu.
     */
    public static function dosenBentrok(?int $dosenId, string $hari, string $mulai, string $selesai, ?int $ignoreKelasId = null): bool
    {
        if (! $dosenId) {
            return false;
        }

        $mulai = self::normalisasiJam($mulai);
        $selesai = self::normalisasiJam($selesai);

        return Kelas::where('dosen_id', $dosenId)
            ->where('hari', $hari)
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai')
            ->when($ignoreKelasId, fn ($q) => $q->where('id', '!=', $ignoreKelasId))
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai)
            ->exists();
    }

    /**
     * Validasi satu slot jadwal; lempar DomainException bila bentrok.
     */
    public static function pastikanSlotBebas(?int $ruangId, ?int $dosenId, string $hari, string $mulai, string $selesai, ?int $ignoreKelasId = null): void
    {
        if (strcmp(substr($mulai, 0, 5), substr($selesai, 0, 5)) >= 0) {
            throw new DomainException('Jam selesai harus setelah jam mulai.');
        }

        if (self::ruangBentrok($ruangId, $hari, $mulai, $selesai, $ignoreKelasId)) {
            throw new DomainException('Ruang bentrok dengan jadwal lain.');
        }

        if (self::dosenBentrok($dosenId, $hari, $mulai, $selesai, $ignoreKelasId)) {
            throw new DomainException('Dosen bentrok dengan jadwal lain.');
        }
    }

    /**
     * M-025/P2: cek bentrok terhadap koleksi yang sudah dimuat (tanpa query).
     * Predikat sama dengan versi DB (overlap setengah-terbuka).
     */
    private static function bentrokDalamKoleksi(Collection $jadwal, string $kolom, int|string|null $id, string $hari, string $mulai, string $selesai, ?int $ignoreId = null): bool
    {
        if ($id === null || $id === '' || $id === 0) {
            return false;
        }

        $mulai = substr((string) $mulai, 0, 5);
        $selesai = substr((string) $selesai, 0, 5);

        foreach ($jadwal as $k) {
            if ($ignoreId !== null && (int) ($k->id ?? 0) === $ignoreId) {
                continue;
            }

            if ((string) ($k->{$kolom} ?? '') !== (string) $id || (string) ($k->hari ?? '') !== $hari) {
                continue;
            }

            $jm = $k->jam_mulai;
            $js = $k->jam_selesai;

            if (! $jm || ! $js) {
                continue;
            }

            if (self::jamOverlap(substr((string) $jm, 0, 5), substr((string) $js, 0, 5), $mulai, $selesai)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Auto-generate jadwal untuk kelas yang belum punya hari/jam.
     *
     * @param  array{hari?: list<string>, slot?: list<array{string,string}>, ruang_ids?: list<int>}  $opsi
     * @return array{dijadwalkan: int, gagal: list<string>}
     */
    public static function generate(int $tahunAkademikId, array $opsi = []): array
    {
        $haris = $opsi['hari'] ?? self::HARI;
        $slots = $opsi['slot'] ?? self::SLOT_DEFAULT;
        $ruangIds = $opsi['ruang_ids'] ?? Ruang::orderBy('id')->pluck('id')->all();

        if ($ruangIds === []) {
            throw new DomainException('Belum ada data ruang.');
        }

        /** @var Collection<int, Kelas> $kelas */
        $kelas = Kelas::where('tahun_akademik_id', $tahunAkademikId)
            ->whereNull('hari')
            ->orderBy('id')
            ->get();

        // M-025/P2: 1 query untuk seluruh jadwal terisi (bukan ~2 query/sel).
        /** @var Collection<int, Kelas> $terjadwal */
        $terjadwal = Kelas::where('tahun_akademik_id', $tahunAkademikId)
            ->whereNotNull('hari')
            ->get(['id', 'hari', 'jam_mulai', 'jam_selesai', 'ruang_id', 'dosen_id']);

        $dijadwalkan = 0;
        $gagal = [];

        foreach ($kelas as $k) {
            $terisi = false;
            foreach ($haris as $hari) {
                foreach ($slots as $slot) {
                    [$mulai, $selesai] = $slot;
                    foreach ($ruangIds as $ruangId) {
                        if (! self::bentrokDalamKoleksi($terjadwal, 'ruang_id', $ruangId, $hari, $mulai, $selesai)
                            && ! self::bentrokDalamKoleksi($terjadwal, 'dosen_id', $k->dosen_id, $hari, $mulai, $selesai, $k->id)) {
                            $k->update([
                                'hari' => $hari,
                                'jam_mulai' => $mulai,
                                'jam_selesai' => $selesai,
                                'ruang_id' => $ruangId,
                            ]);
                            $terjadwal->push($k->fresh());
                            $dijadwalkan++;
                            $terisi = true;
                            break 3;
                        }
                    }
                }
            }
            if (! $terisi) {
                $gagal[] = "Kelas {$k->nama} (id {$k->id}) tidak dapat slot bebas.";
            }
        }

        return ['dijadwalkan' => $dijadwalkan, 'gagal' => $gagal];
    }

    public static function hariDariTanggal(string $tanggal): string
    {
        return match (Carbon::parse($tanggal)->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Minggu',
        };
    }

    /**
     * Ajukan pinjam ruang; tolak bila bentrok jadwal kuliah / booking lain.
     */
    public static function pinjamRuang(int $ruangId, int $userId, string $tanggal, string $mulai, string $selesai, ?string $keperluan = null): PeminjamanRuang
    {
        $mulai = substr($mulai, 0, 5);
        $selesai = substr($selesai, 0, 5);

        if (strcmp($mulai, $selesai) >= 0) {
            throw new DomainException('Jam selesai harus setelah jam mulai.');
        }

        $hari = self::hariDariTanggal($tanggal);

        // M-025/P1: overlap di sisi DB (bukan get()->contains()).
        $bentrokKuliah = Kelas::where('ruang_id', $ruangId)
            ->where('hari', $hari)
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai')
            ->where('jam_mulai', '<', self::normalisasiJam($selesai))
            ->where('jam_selesai', '>', self::normalisasiJam($mulai))
            ->exists();

        if ($bentrokKuliah) {
            throw new DomainException('Ruang dipakai jadwal kuliah pada jam tersebut.');
        }

        $bentrokBooking = PeminjamanRuang::where('ruang_id', $ruangId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('status', [PeminjamanRuang::STATUS_DIAJUKAN, PeminjamanRuang::STATUS_DISETUJUI])
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai')
            ->where('jam_mulai', '<', self::normalisasiJam($selesai))
            ->where('jam_selesai', '>', self::normalisasiJam($mulai))
            ->exists();

        if ($bentrokBooking) {
            throw new DomainException('Ruang sudah dipinjam pada jam tersebut.');
        }

        return PeminjamanRuang::create([
            'ruang_id' => $ruangId,
            'user_id' => $userId,
            'tanggal' => $tanggal,
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'keperluan' => $keperluan,
            'status' => PeminjamanRuang::STATUS_DIAJUKAN,
        ]);
    }

    public static function setujuiPinjam(PeminjamanRuang $pinjam, ?string $catatan = null): PeminjamanRuang
    {
        // M-041: cek-ulang bentrok di dalam transaksi terkunci — dua persetujuan
        // bersamaan untuk slot yang sama tidak bisa lolos dua-duanya.
        return DB::transaction(function () use ($pinjam, $catatan): PeminjamanRuang {
            $locked = PeminjamanRuang::whereKey($pinjam->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->status !== PeminjamanRuang::STATUS_DIAJUKAN, 422, 'Hanya pengajuan diajukan yang bisa disetujui.');

            $mulai = substr((string) $locked->jam_mulai, 0, 5);
            $selesai = substr((string) $locked->jam_selesai, 0, 5);

            $hari = self::hariDariTanggal($locked->tanggal instanceof \DateTimeInterface
                ? $locked->tanggal->format('Y-m-d')
                : (string) $locked->tanggal);

            $bentrokKuliah = Kelas::where('ruang_id', $locked->ruang_id)
                ->where('hari', $hari)
                ->whereNotNull('jam_mulai')
                ->whereNotNull('jam_selesai')
                ->where('jam_mulai', '<', self::normalisasiJam($selesai))
                ->where('jam_selesai', '>', self::normalisasiJam($mulai))
                ->exists();

            if ($bentrokKuliah) {
                throw new DomainException('Ruang dipakai jadwal kuliah pada jam tersebut.');
            }

            $bentrokBooking = PeminjamanRuang::where('ruang_id', $locked->ruang_id)
                ->whereDate('tanggal', $locked->tanggal instanceof \DateTimeInterface ? $locked->tanggal->format('Y-m-d') : (string) $locked->tanggal)
                ->where('id', '!=', $locked->id)
                ->whereIn('status', [PeminjamanRuang::STATUS_DIAJUKAN, PeminjamanRuang::STATUS_DISETUJUI])
                ->whereNotNull('jam_mulai')
                ->whereNotNull('jam_selesai')
                ->where('jam_mulai', '<', self::normalisasiJam($selesai))
                ->where('jam_selesai', '>', self::normalisasiJam($mulai))
                ->exists();

            if ($bentrokBooking) {
                throw new DomainException('Ruang sudah dipinjam pada jam tersebut.');
            }

            $locked->update(['status' => PeminjamanRuang::STATUS_DISETUJUI, 'catatan_admin' => $catatan]);

            return $locked->refresh();
        });
    }

    public static function tolakPinjam(PeminjamanRuang $pinjam, ?string $catatan = null): PeminjamanRuang
    {
        $pinjam->update(['status' => PeminjamanRuang::STATUS_DITOLAK, 'catatan_admin' => $catatan]);

        return $pinjam->refresh();
    }

    /**
     * Presensi dosen untuk satu pertemuan; otomatis membuat/memperbarui BAP (jurnal_dosen).
     *
     * @param  array{realisasi?: string, kendala?: string}  $bap
     */
    public static function presensiDosen(Pertemuan $pertemuan, string $statusHadir = 'hadir', array $bap = []): JurnalDosen
    {
        if (! in_array($statusHadir, ['hadir', 'izin', 'sakit', 'alfa'], true)) {
            throw new DomainException('Status presensi dosen tidak valid.');
        }

        $pertemuan->loadMissing('kelas');
        $dosenId = $pertemuan->kelas?->dosen_id;

        $hadir = Absensi::where('pertemuan_id', $pertemuan->id)->where('status', 'hadir')->count();
        $total = Absensi::where('pertemuan_id', $pertemuan->id)->count();
        $topik = $pertemuan->topik ?: "Pertemuan ke-{$pertemuan->ke}";

        $realisasi = $bap['realisasi']
            ?? "Topik: {$topik}. Kehadiran mahasiswa {$hadir}/{$total}. Dosen: {$statusHadir}.";

        $jurnal = JurnalDosen::updateOrCreate(
            ['pertemuan_id' => $pertemuan->id],
            [
                'dosen_id' => $dosenId,
                'realisasi' => $realisasi,
                'kendala' => $bap['kendala'] ?? null,
                'status_hadir' => $statusHadir,
                'tanggal' => $pertemuan->tanggal,
            ]
        );

        return $jurnal->refresh();
    }

    /**
     * Bangun teks BAP dari data pertemuan + absensi.
     *
     * @return array{judul: string, isi: string}
     */
    public static function buatBap(Pertemuan $pertemuan): array
    {
        $pertemuan->loadMissing(['kelas.matakuliah', 'kelas.dosen.user', 'kelas.ruang']);
        $hadir = Absensi::where('pertemuan_id', $pertemuan->id)->where('status', 'hadir')->count();
        $total = Absensi::where('pertemuan_id', $pertemuan->id)->count();

        $mk = $pertemuan->kelas?->matakuliah?->nama ?? '—';
        $dosen = $pertemuan->kelas?->dosen?->user?->name ?? '—';
        $ruang = $pertemuan->kelas?->ruang?->nama ?? '—';
        $tanggal = $pertemuan->tanggal?->format('Y-m-d') ?? '—';

        $jurnal = JurnalDosen::where('pertemuan_id', $pertemuan->id)->first();

        return [
            'judul' => "BAP {$mk} — Pertemuan ke-{$pertemuan->ke} ({$tanggal})",
            'isi' => "Matakuliah: {$mk}\nKelas: {$pertemuan->kelas?->nama}\n"
                ."Dosen: {$dosen} (".($jurnal?->status_hadir ?? 'hadir').")\n"
                ."Ruang: {$ruang}\nTopik: {$pertemuan->topik}\n"
                ."Kehadiran mahasiswa: {$hadir}/{$total}\n"
                .'Realisasi: '.($jurnal?->realisasi ?? '-')."\n"
                .'Kendala: '.($jurnal?->kendala ?? '-'),
        ];
    }
}
