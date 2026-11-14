<?php

namespace Tests\Feature;

use App\Models\PeminjamanRuang;
use App\Models\User;
use App\Services\Ai\RekomendasiMkService;
use App\Services\AkademikService;
use App\Services\JadwalService;
use App\Services\KeuanganService;
use Database\Seeders\RolesPermissionsSeeder;
use DomainException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Wave B Batch 2: M-022, M-023, M-025, M-026, M-035, M-041.
 */
class WaveBBatch2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function struktur(): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fak, 'nama' => 'Informatika', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'informatika',
        ]);

        return [$fak, $prodi];
    }

    private function tahun(string $tahun, string $semester, bool $aktif = false): int
    {
        return DB::table('tahun_akademik')->insertGetId([
            'tahun' => $tahun, 'semester' => $semester, 'aktif' => $aktif,
        ]);
    }

    private function kelas(int $prodi, int $tahunId, string $kode, int $sks = 3, array $extra = []): int
    {
        $mk = DB::table('matakuliah')->insertGetId([
            'prodi_id' => $prodi, 'kode' => $kode, 'nama' => 'MK '.$kode, 'sks' => $sks, 'semester' => 1,
        ]);

        return DB::table('kelas')->insertGetId(array_merge([
            'matakuliah_id' => $mk, 'tahun_akademik_id' => $tahunId, 'nama' => 'A', 'kuota' => 40,
        ], $extra));
    }

    private function mahasiswa(int $prodi, string $nim, string $role = 'mahasiswa'): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $id = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        return [$user, $id];
    }

    /** M-025/P1: overlap dicek di DB; batas tepat tidak dianggap bentrok. */
    public function test_bentrok_ruang_dosen_db_side_dan_batas_tepat(): void
    {
        [, $prodi] = $this->struktur();
        $t = $this->tahun('2025', 'ganjil', true);
        $ruang = DB::table('ruang')->insertGetId(['gedung' => 'G1', 'nama' => 'R1', 'kapasitas' => 40]);
        $kelasId = $this->kelas($prodi, $t, 'J1', 3, [
            'hari' => 'Senin', 'jam_mulai' => '07:00:00', 'jam_selesai' => '09:30:00', 'ruang_id' => $ruang,
        ]);

        $this->assertTrue(JadwalService::ruangBentrok($ruang, 'Senin', '08:00', '10:00'));
        $this->assertFalse(JadwalService::ruangBentrok($ruang, 'Senin', '09:30', '10:00'));
        $this->assertFalse(JadwalService::ruangBentrok($ruang, 'Selasa', '08:00', '10:00'));
        $this->assertTrue(JadwalService::ruangBentrok($ruang, 'Senin', '08:00', '10:00', 9999));
        $this->assertFalse(JadwalService::ruangBentrok($ruang, 'Senin', '08:00', '10:00', $kelasId));
        $this->assertFalse(JadwalService::ruangBentrok(null, 'Senin', '08:00', '10:00'));
    }

    /** M-025/P3: validasi prasyarat + kuota tetap benar setelah batching. */
    public function test_validasi_krs_batch_tetap_benar(): void
    {
        [, $prodi] = $this->struktur();
        $t = $this->tahun('2026', 'ganjil', true);
        [, $mhs] = $this->mahasiswa($prodi, '20260301');

        $mkA = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'PRA-A', 'nama' => 'Dasar', 'sks' => 3, 'semester' => 1]);
        $mkC = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'LAN-C', 'nama' => 'Lanjutan', 'sks' => 3, 'semester' => 2, 'prasyarat_id' => $mkA]);
        $kelasA = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkA, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        $kelasC = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkC, 'tahun_akademik_id' => $t, 'nama' => 'C', 'kuota' => 40]);

        $pesan = AkademikService::validasiKrs($mhs, [$kelasC], 3.5);
        $this->assertNotEmpty(array_filter($pesan, fn ($m) => str_contains($m, 'Prasyarat')));

        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $kelasA, 'bobot' => 3.0, 'is_locked' => true]);
        $pesan = AkademikService::validasiKrs($mhs, [$kelasC], 3.5);
        $this->assertEmpty(array_filter($pesan, fn ($m) => str_contains($m, 'Prasyarat')));

        $penuh = $this->kelas($prodi, $t, 'PENUH-1', 3, ['kuota' => 1]);
        [$uLain] = $this->mahasiswa($prodi, '20260302');
        $krsLain = DB::table('krs')->insertGetId(['mahasiswa_id' => DB::table('mahasiswa')->where('nim', '20260302')->value('id'), 'tahun_akademik_id' => $t, 'status' => 'disetujui']);
        DB::table('krs_detail')->insert(['krs_id' => $krsLain, 'kelas_id' => $penuh]);
        $this->assertSame([$penuh => 1], AkademikService::kursiTerisiBanyak([$penuh, $kelasC]));

        $pesan = AkademikService::validasiKrs($mhs, [$penuh], 3.5);
        $this->assertNotEmpty(array_filter($pesan, fn ($m) => str_contains($m, 'waiting list')));
    }

    /** M-023: kandidat DO terdeteksi; kering tak mengubah; --terapkan + audit. */
    public function test_evaluasi_do_kering_dan_terapkan(): void
    {
        [, $prodi] = $this->struktur();
        $tas = [];
        foreach ([['2022', 'ganjil'], ['2022', 'genap'], ['2023', 'ganjil'], ['2023', 'genap']] as [$th, $sm]) {
            $tas[] = $this->tahun($th, $sm);
        }

        [, $drop] = $this->mahasiswa($prodi, '20260401');
        [, $baik] = $this->mahasiswa($prodi, '20260402');

        $i = 0;
        foreach ($tas as $t) {
            $i++;
            DB::table('nilai')->insert(['mahasiswa_id' => $drop, 'kelas_id' => $this->kelas($prodi, $t, 'DO-'.$i), 'bobot' => 1.0, 'is_locked' => true]);
            DB::table('nilai')->insert(['mahasiswa_id' => $baik, 'kelas_id' => $this->kelas($prodi, $t, 'OK-'.$i), 'bobot' => 4.0, 'is_locked' => true]);
        }

        $kandidat = AkademikService::kandidatDo();
        $this->assertSame([$drop], $kandidat->pluck('mahasiswa_id')->all());
        $this->assertSame(1.0, $kandidat->first()['ipk']);
        $this->assertSame(4, $kandidat->first()['semester']);

        $this->artisan('akademik:evaluasi-do')->assertSuccessful();
        $this->assertSame('aktif', DB::table('mahasiswa')->where('id', $drop)->value('status'));

        $this->artisan('akademik:evaluasi-do', ['--terapkan' => true])->assertSuccessful();
        $this->assertSame('do', DB::table('mahasiswa')->where('id', $drop)->value('status'));
        $this->assertSame('aktif', DB::table('mahasiswa')->where('id', $baik)->value('status'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'mahasiswa.status.do', 'model_id' => $drop]);

        // Idempoten: kandidat berstatus do tidak dievaluasi lagi.
        $this->assertTrue(AkademikService::kandidatDo()->isEmpty());
    }

    /** M-026: rekomendasi memetakan kelas periode aktif + posisi waitlist. */
    public function test_rekomendasi_memetakan_kelas_dan_waitlist(): void
    {
        [, $prodi] = $this->struktur();
        $tAktif = $this->tahun('2026', 'ganjil', true);
        $tLalu = $this->tahun('2025', 'genap');
        [$user, $mhs] = $this->mahasiswa($prodi, '20260501');

        $mkGagal = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'GL-1', 'nama' => 'Gagal 1', 'sks' => 3, 'semester' => 1]);
        $kelasLalu = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkGagal, 'tahun_akademik_id' => $tLalu, 'nama' => 'A', 'kuota' => 40]);
        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $kelasLalu, 'huruf' => 'E', 'bobot' => 0.0, 'is_locked' => true]);

        $kelasAktif = DB::table('kelas')->insertGetId(['matakuliah_id' => $mkGagal, 'tahun_akademik_id' => $tAktif, 'nama' => 'B', 'kuota' => 40]);

        $hasil = app(RekomendasiMkService::class)->rekomendasi($mhs);
        $this->assertCount(1, $hasil['ulangi']);
        $this->assertSame($mkGagal, $hasil['ulangi'][0]['matakuliah_id']);
        $this->assertFalse($hasil['kelas_tersedia'][$mkGagal]['penuh']);
        $this->assertSame($kelasAktif, $hasil['kelas_tersedia'][$mkGagal]['kelas_id']);

        // Penuhkan kelas + masuk waitlist → peta menandai penuh + posisi 1.
        DB::table('kelas')->where('id', $kelasAktif)->update(['kuota' => 1]);
        [$uLain] = $this->mahasiswa($prodi, '20260502');
        $krsLain = DB::table('krs')->insertGetId(['mahasiswa_id' => DB::table('mahasiswa')->where('nim', '20260502')->value('id'), 'tahun_akademik_id' => $tAktif, 'status' => 'disetujui']);
        DB::table('krs_detail')->insert(['krs_id' => $krsLain, 'kelas_id' => $kelasAktif]);
        DB::table('kelas_waitlist')->insert(['kelas_id' => $kelasAktif, 'mahasiswa_id' => $mhs, 'status' => 'menunggu', 'created_at' => now(), 'updated_at' => now()]);

        $hasil = app(RekomendasiMkService::class)->rekomendasi($mhs);
        $this->assertTrue($hasil['kelas_tersedia'][$mkGagal]['penuh']);
        $this->assertSame(1, $hasil['kelas_tersedia'][$mkGagal]['posisi_waitlist']);
    }

    /** M-035: rekon tak_dikenal + idempoten (re-run tak mengubah reconciled_at). */
    public function test_rekon_tak_dikenal_dan_idempoten(): void
    {
        [, $prodi] = $this->struktur();
        $t = $this->tahun('2026', 'ganjil', true);
        [, $mhs] = $this->mahasiswa($prodi, '20260601');

        $tagihan = DB::table('tagihan')->insertGetId([
            'mahasiswa_id' => $mhs, 'tahun_akademik_id' => $t, 'jenis' => 'UKT',
            'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);
        $bayar = DB::table('pembayaran')->insertGetId([
            'tagihan_id' => $tagihan, 'metode' => 'va_x', 'nominal' => 1000000,
            'status' => 'pending', 'reference' => 'SIM-0001',
        ]);

        $csv = "reference,nominal\nSIM-0001,1000000\nSIM-9999,500000\n";
        $r1 = KeuanganService::rekonsiliasiCsv($csv);
        $this->assertSame(1, $r1['matched']);
        $this->assertSame(['SIM-9999'], $r1['tak_dikenal']);
        $this->assertSame([], $r1['selisih']);
        $pertama = DB::table('pembayaran')->where('id', $bayar)->value('reconciled_at');
        $this->assertNotNull($pertama);

        $r2 = KeuanganService::rekonsiliasiCsv($csv);
        $this->assertSame(1, $r2['matched']);
        $this->assertSame($pertama, DB::table('pembayaran')->where('id', $bayar)->value('reconciled_at'));
    }

    /** M-041: setujui re-check bentrok dalam tx; rute khusus tersedia. */
    public function test_setujui_pinjam_tolak_double_booking(): void
    {
        $this->struktur();
        $ruang = DB::table('ruang')->insertGetId(['gedung' => 'G2', 'nama' => 'Lab 1', 'kapasitas' => 30]);
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $tanggal = now()->addDay()->format('Y-m-d');

        $a = PeminjamanRuang::create([
            'ruang_id' => $ruang, 'user_id' => $user->id, 'tanggal' => $tanggal,
            'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'status' => PeminjamanRuang::STATUS_DIAJUKAN,
        ]);

        JadwalService::setujuiPinjam($a);
        $this->assertSame(PeminjamanRuang::STATUS_DISETUJUI, $a->fresh()->status);

        // Booking tumpang-tindih yang menyelinap setelah persetujuan pertama
        // (simulasi race) wajib ditolak saat disetujui.
        $b = PeminjamanRuang::create([
            'ruang_id' => $ruang, 'user_id' => $user->id, 'tanggal' => $tanggal,
            'jam_mulai' => '11:00', 'jam_selesai' => '13:00', 'status' => PeminjamanRuang::STATUS_DIAJUKAN,
        ]);

        try {
            JadwalService::setujuiPinjam($b);
            $this->fail('Persetujuan double-booking seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertSame('Ruang sudah dipinjam pada jam tersebut.', $e->getMessage());
        }

        $this->assertSame(PeminjamanRuang::STATUS_DIAJUKAN, $b->fresh()->status);
    }

    public function test_rute_fasilitas_pinjam(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');

        $this->get(route('fasilitas.pinjam'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('fasilitas.pinjam'))->assertOk();
    }

    /** M-022: rollover terjadwal tiap awal semester. */
    public function test_scheduler_memuat_rollover_akademik(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->map(fn ($e) => $e->command ?? '')
            ->filter(fn ($c) => str_contains((string) $c, 'akademik:rollover'));

        $this->assertGreaterThanOrEqual(2, $events->count());
    }
}
