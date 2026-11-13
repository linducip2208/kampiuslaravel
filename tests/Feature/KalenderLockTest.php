<?php

namespace Tests\Feature;

use App\Livewire\Kalender\Kelola as KalenderKelola;
use App\Livewire\Krs\Pengajuan;
use App\Models\KalenderEvent;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\KalenderService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KalenderLockTest extends TestCase
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

    public function test_tanpa_event_semua_layanan_terbuka(): void
    {
        foreach (KalenderService::kunciTersedia() as $kunci) {
            $this->assertTrue(KalenderService::isOpen($kunci));
        }
    }

    public function test_jendela_masa_depan_menutup_layanan(): void
    {
        KalenderEvent::create([
            'judul' => 'KRS Ganjil',
            'jenis' => 'krs',
            'tgl_mulai' => now()->addMonth()->format('Y-m-d'),
            'tgl_selesai' => now()->addMonth()->addWeek()->format('Y-m-d'),
        ]);

        $this->assertFalse(KalenderService::isOpen('krs'));
        $this->assertTrue(KalenderService::isOpen('sidang'));
    }

    public function test_jendela_berjalan_membuka_layanan(): void
    {
        KalenderEvent::create([
            'judul' => 'Sidang Akhir',
            'jenis' => 'sidang',
            'tgl_mulai' => now()->subDay()->format('Y-m-d'),
            'tgl_selesai' => now()->addDay()->format('Y-m-d'),
        ]);

        $this->assertTrue(KalenderService::isOpen('sidang'));
    }

    public function test_kelola_mendukung_jenis_krs_dan_mencatat_audit(): void
    {
        Livewire::actingAs($this->admin())
            ->test(KalenderKelola::class)
            ->set('judul', 'Isi KRS')
            ->set('jenis', 'krs')
            ->set('tgl_mulai', now()->format('Y-m-d'))
            ->set('tgl_selesai', now()->addWeek()->format('Y-m-d'))
            ->call('simpan')
            ->assertSee('Agenda tersimpan');

        $this->assertDatabaseHas('kalender_events', ['judul' => 'Isi KRS', 'jenis' => 'krs']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'kalender.dibuat']);
    }

    public function test_krs_diblokir_di_luar_jadwal_kalender(): void
    {
        KalenderEvent::create([
            'judul' => 'KRS Ganjil',
            'jenis' => 'krs',
            'tgl_mulai' => now()->addMonth()->format('Y-m-d'),
        ]);

        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs = Mahasiswa::factory()->create(['user_id' => $user->id]);
        $tahun = TahunAkademik::factory()->create(['aktif' => true]);
        $kelas = Kelas::factory()->create(['tahun_akademik_id' => $tahun->id]);

        Livewire::actingAs($user)
            ->test(Pengajuan::class, ['tahunId' => $tahun->id])
            ->set('ipk', 3.0)
            ->set('kelasDipilih', [$kelas->id])
            ->call('ajukan')
            ->assertHasErrors(['kelasDipilih']);

        $this->assertSame(0, Krs::where('mahasiswa_id', $mhs->id)->count());
    }

    public function test_health_dan_ics_dan_audit_viewer(): void
    {
        $this->get('/health')->assertOk()->assertJson(['ok' => true]);

        KalenderEvent::create([
            'judul' => 'UTS Ganjil',
            'jenis' => 'uts',
            'tgl_mulai' => now()->format('Y-m-d'),
        ]);

        $biasa = User::factory()->create();
        $biasa->assignRole('mahasiswa');

        $ics = $this->actingAs($biasa)->get(route('kalender.ics'));
        $ics->assertOk();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics->getContent());
        $this->assertStringContainsString('UTS Ganjil', $ics->getContent());

        $this->actingAs($biasa)->get(route('admin.audit'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.audit'))->assertOk();
    }
}
