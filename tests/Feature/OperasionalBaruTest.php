<?php

namespace Tests\Feature;

use App\Livewire\Helpdesk\Tiket as HelpdeskTiket;
use App\Livewire\Notifikasi\Broadcast;
use App\Models\Aset;
use App\Models\AsetMaintenance;
use App\Models\BebanDosen;
use App\Models\BroadcastTemplate;
use App\Models\Dosen;
use App\Models\HelpdeskSla;
use App\Models\IzinCutiDosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\TahunAkademik;
use App\Models\Tiket;
use App\Models\TracerAlumni;
use App\Models\User;
use App\Services\BkdService;
use App\Services\HelpdeskSlaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperasionalBaruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('superadmin');

        return $u;
    }

    public function test_bkd_total_dan_sk_otomatis(): void
    {
        $dosen = Dosen::factory()->create();
        $tahun = TahunAkademik::factory()->create();
        $mk = Matakuliah::factory()->create(['sks' => 3]);
        Kelas::factory()->create(['dosen_id' => $dosen->id, 'tahun_akademik_id' => $tahun->id, 'matakuliah_id' => $mk->id]);

        $n = BkdService::sinkronMengajarDariKelas($dosen->id, $tahun->id);
        $this->assertSame(1, $n);

        BebanDosen::create(['dosen_id' => $dosen->id, 'tahun_akademik_id' => $tahun->id, 'kategori' => 'penelitian', 'uraian' => 'Jurnal Sinta', 'sks' => 2, 'status' => 'disetujui']);

        $this->assertSame(5, BkdService::totalSks($dosen->id, $tahun->id));

        $sk = BkdService::generateSk($dosen->id, $tahun->id);
        $this->assertStringStartsWith('SK/MENGAJAR/', $sk->nomor);
        $this->assertSame(3, $sk->total_sks);

        $lagi = BkdService::generateSk($dosen->id, $tahun->id);
        $this->assertSame($sk->id, $lagi->id);
    }

    public function test_izin_cuti_overlap_ditolak(): void
    {
        $dosen = Dosen::factory()->create();
        $cuti = IzinCutiDosen::create(['dosen_id' => $dosen->id, 'jenis' => 'cuti', 'tgl_mulai' => '2026-08-01', 'tgl_selesai' => '2026-08-05', 'alasan' => 'Keluarga', 'status' => 'disetujui']);

        $baru = new IzinCutiDosen(['dosen_id' => $dosen->id]);
        $this->assertTrue($baru->isOverlapping('2026-08-03', '2026-08-10'));
        $this->assertFalse($baru->isOverlapping('2026-08-06', '2026-08-10'));
        $this->assertDatabaseHas('izin_cuti_dosen', ['id' => $cuti->id, 'status' => 'disetujui']);
    }

    public function test_aset_dan_maintenance(): void
    {
        $aset = Aset::create(['kode' => 'LPT-001', 'nama' => 'Laptop Lab', 'kategori' => 'elektronik', 'kondisi' => 'baik', 'nilai' => 12000000]);
        AsetMaintenance::create(['aset_id' => $aset->id, 'jenis' => 'perbaikan', 'deskripsi' => 'Ganti SSD', 'biaya' => 800000, 'status' => 'selesai']);

        $this->assertSame(800000, $aset->fresh()->totalBiayaMaintenance());
    }

    public function test_helpdesk_sla_dan_eskalasi(): void
    {
        $this->assertDatabaseHas('helpdesk_sla', ['prioritas' => 'kritis']);

        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        Livewire::actingAs($mhs)
            ->test(HelpdeskTiket::class)
            ->set('subjek', 'Server down')
            ->set('isi', 'SIAKAD tidak bisa diakses')
            ->set('kategori', 'umum')
            ->set('prioritas', 'kritis')
            ->call('buat')
            ->assertSee('SLA dihitung otomatis');

        $tiket = Tiket::firstOrFail();
        $this->assertNotNull($tiket->due_respon_at);
        $this->assertNotNull($tiket->due_resolusi_at);

        $tiket->forceFill(['due_respon_at' => now()->subHour(), 'status' => 'terbuka'])->save();
        $this->assertTrue(HelpdeskSlaService::cekEskalasi($tiket->fresh()));
        $this->assertTrue($tiket->fresh()->dieskalasi);
    }

    public function test_broadcast_template(): void
    {
        BroadcastTemplate::create(['nama' => 'info-ukt', 'role_target' => 'mahasiswa', 'judul' => 'Info UKT {nama}', 'isi' => 'Halo {nama}, bayar UKT']);

        $tpl = BroadcastTemplate::where('nama', 'info-ukt')->firstOrFail();
        $render = $tpl->render(['nama' => 'Budi']);
        $this->assertSame('Info UKT Budi', $render['judul']);

        Livewire::actingAs($this->admin())
            ->test(Broadcast::class)
            ->set('templateId', $tpl->id)
            ->call('pakaiTemplate')
            ->assertSee('dimuat');

        Livewire::actingAs($this->admin())
            ->test(Broadcast::class)
            ->set('role', 'mahasiswa')
            ->set('judul', 'Judul Baru')
            ->set('isi', 'Isi baru')
            ->set('templateNama', 'baru-1')
            ->call('simpanTemplate')
            ->assertSee('tersimpan');

        $this->assertDatabaseHas('broadcast_templates', ['nama' => 'baru-1']);
    }

    public function test_tracer_alumni(): void
    {
        $mhs = Mahasiswa::factory()->create();
        TracerAlumni::create(['mahasiswa_id' => $mhs->id, 'tahun_lulus' => 2024, 'status_kerja' => 'bekerja', 'perusahaan' => 'PT Maju', 'jabatan' => 'Staff']);

        $this->assertDatabaseHas('tracer_alumni', ['mahasiswa_id' => $mhs->id, 'perusahaan' => 'PT Maju']);

        $sla = HelpdeskSla::target('umum', 'normal');
        $this->assertNotNull($sla);
        $this->assertGreaterThan(0, $sla->respon_jam);
    }
}
