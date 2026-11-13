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
     * Cek bentrok ruang pada hari+jam tertentu (jadwal kuliah mingguan).
     */
    public static function ruangBentrok(?int $ruangId, string $hari, string $mulai, string $selesai, ?int $ignoreKelasId = null): bool
    {
        if (! $ruangId) {
            return false;
        }

        return Kelas::where('ruang_id', $ruangId)
            ->where('hari', $hari)
            ->when($ignoreKelasId, fn ($q) => $q->where('id', '!=', $ignoreKelasId))
            ->get(['jam_mulai', 'jam_selesai'])
            ->contains(fn ($k) => $k->jam_mulai && $k->jam_selesai
                && self::jamOverlap(
                    substr((string) $k->jam_mulai, 0, 5), substr((string) $k->jam_selesai, 0, 5),
                    substr($mulai, 0, 5), substr($selesai, 0, 5)
                ));
    }

    /**
     * Cek bentrok dosen pada hari+jam tertentu.
     */
    public static function dosenBentrok(?int $dosenId, string $hari, string $mulai, string $selesai, ?int $ignoreKelasId = null): bool
    {
        if (! $dosenId) {
            return false;
        }

        return Kelas::where('dosen_id', $dosenId)
            ->where('hari', $hari)
            ->when($ignoreKelasId, fn ($q) => $q->where('id', '!=', $ignoreKelasId))
            ->get(['jam_mulai', 'jam_selesai'])
            ->contains(fn ($k) => $k->jam_mulai && $k->jam_selesai
                && self::jamOverlap(
                    substr((string) $k->jam_mulai, 0, 5), substr((string) $k->jam_selesai, 0, 5),
                    substr($mulai, 0, 5), substr($selesai, 0, 5)
                ));
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

        $dijadwalkan = 0;
        $gagal = [];

        foreach ($kelas as $k) {
            $terisi = false;
            foreach ($haris as $hari) {
                foreach ($slots as $slot) {
                    [$mulai, $selesai] = $slot;
                    foreach ($ruangIds as $ruangId) {
                        if (! self::ruangBentrok($ruangId, $hari, $mulai, $selesai)
                            && ! self::dosenBentrok($k->dosen_id, $hari, $mulai, $selesai, $k->id)) {
                            $k->update([
                                'hari' => $hari,
                                'jam_mulai' => $mulai,
                                'jam_selesai' => $selesai,
                                'ruang_id' => $ruangId,
                            ]);
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

        $bentrokKuliah = Kelas::where('ruang_id', $ruangId)
            ->where('hari', $hari)
            ->get(['jam_mulai', 'jam_selesai'])
            ->contains(fn ($k) => $k->jam_mulai && $k->jam_selesai
                && self::jamOverlap(substr((string) $k->jam_mulai, 0, 5), substr((string) $k->jam_selesai, 0, 5), $mulai, $selesai));

        if ($bentrokKuliah) {
            throw new DomainException('Ruang dipakai jadwal kuliah pada jam tersebut.');
        }

        $bentrokBooking = PeminjamanRuang::where('ruang_id', $ruangId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('status', [PeminjamanRuang::STATUS_DIAJUKAN, PeminjamanRuang::STATUS_DISETUJUI])
            ->get(['jam_mulai', 'jam_selesai'])
            ->contains(fn ($b) => self::jamOverlap(
                substr((string) $b->jam_mulai, 0, 5), substr((string) $b->jam_selesai, 0, 5), $mulai, $selesai
            ));

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
        $pinjam->update(['status' => PeminjamanRuang::STATUS_DISETUJUI, 'catatan_admin' => $catatan]);

        return $pinjam->refresh();
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
