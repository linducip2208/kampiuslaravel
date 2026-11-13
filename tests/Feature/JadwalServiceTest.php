<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\PeminjamanRuang;
use App\Models\Pertemuan;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\JadwalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class JadwalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ruang_bentrok_terdeteksi(): void
    {
        $kelas = Kelas::factory()->create(['hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00']);

        $this->assertTrue(JadwalService::ruangBentrok($kelas->ruang_id, 'Senin', '09:00', '11:00'));
        $this->assertFalse(JadwalService::ruangBentrok($kelas->ruang_id, 'Senin', '10:00', '12:00'));
        $this->assertFalse(JadwalService::ruangBentrok($kelas->ruang_id, 'Selasa', '09:00', '11:00'));
        $this->assertTrue(JadwalService::dosenBentrok($kelas->dosen_id, 'Senin', '09:00', '11:00', null));
        $this->assertFalse(JadwalService::dosenBentrok($kelas->dosen_id, 'Senin', '09:00', '11:00', $kelas->id));
    }

    public function test_generate_tanpa_bentrok(): void
    {
        $tahun = TahunAkademik::factory()->create();
        Ruang::factory()->count(2)->create();
        Kelas::factory()->count(3)->create([
            'tahun_akademik_id' => $tahun->id, 'hari' => null,
            'jam_mulai' => null, 'jam_selesai' => null, 'ruang_id' => null,
        ]);

        $hasil = JadwalService::generate($tahun->id);

        $this->assertSame(3, $hasil['dijadwalkan']);
        $this->assertSame([], $hasil['gagal']);

        $slots = Kelas::where('tahun_akademik_id', $tahun->id)->get(['hari', 'jam_mulai', 'jam_selesai', 'ruang_id', 'dosen_id']);
        foreach ($slots as $i => $a) {
            foreach ($slots as $j => $b) {
                if ($i >= $j) {
                    continue;
                }
                if ($a->hari === $b->hari && JadwalService::jamOverlap(
                    substr($a->jam_mulai, 0, 5), substr($a->jam_selesai, 0, 5),
                    substr($b->jam_mulai, 0, 5), substr($b->jam_selesai, 0, 5)
                )) {
                    $this->assertNotEquals($a->ruang_id, $b->ruang_id, 'Ruang ganda pada slot sama.');
                    $this->assertNotEquals($a->dosen_id, $b->dosen_id, 'Dosen ganda pada slot sama.');
                }
            }
        }
    }

    public function test_pinjam_ruang_bentrok_kuliah_ditolak(): void
    {
        // 2026-11-16 = Senin.
        $kelas = Kelas::factory()->create(['hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00']);
        $user = User::factory()->create();

        $this->expectException(DomainException::class);
        JadwalService::pinjamRuang($kelas->ruang_id, $user->id, '2026-11-16', '09:00', '11:00', 'Rapat');
    }

    public function test_pinjam_ruang_ganda_ditolak(): void
    {
        $ruang = Ruang::factory()->create();
        $user = User::factory()->create();

        JadwalService::pinjamRuang($ruang->id, $user->id, '2026-11-17', '10:00', '12:00', 'UKM');

        $this->expectException(DomainException::class);
        JadwalService::pinjamRuang($ruang->id, $user->id, '2026-11-17', '11:00', '13:00', 'Rapat');
    }

    public function test_pinjam_dan_setujui(): void
    {
        $ruang = Ruang::factory()->create();
        $user = User::factory()->create();

        $pinjam = JadwalService::pinjamRuang($ruang->id, $user->id, '2026-11-18', '10:00', '12:00', 'Seminar');
        $this->assertSame('diajukan', $pinjam->status);

        $ok = JadwalService::setujuiPinjam($pinjam);
        $this->assertSame(PeminjamanRuang::STATUS_DISETUJUI, $ok->status);
    }

    public function test_presensi_dosen_membuat_bap_otomatis(): void
    {
        $kelas = Kelas::factory()->create(['hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00']);
        $mhs = Mahasiswa::factory()->create();
        $pertemuan = Pertemuan::create([
            'kelas_id' => $kelas->id, 'ke' => 1, 'tanggal' => now()->toDateString(),
            'topik' => 'Pengantar', 'qr_token' => Str::random(32),
        ]);
        $pertemuan->absensi()->create(['mahasiswa_id' => $mhs->id, 'status' => 'hadir']);

        $jurnal = JadwalService::presensiDosen($pertemuan, 'hadir');

        $this->assertDatabaseHas('jurnal_dosen', ['pertemuan_id' => $pertemuan->id, 'status_hadir' => 'hadir']);
        $this->assertStringContainsString('1/1', $jurnal->realisasi);

        $bap = JadwalService::buatBap($pertemuan);
        $this->assertStringContainsString('BAP', $bap['judul']);
        $this->assertStringContainsString('1/1', $bap['isi']);
    }
}
