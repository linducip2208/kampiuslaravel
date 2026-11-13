<?php

namespace Tests\Feature;

use App\Livewire\Skripsi\Yudisium;
use App\Models\Alumni;
use App\Models\Kelas;
use App\Models\MagangLamaran;
use App\Models\MagangLowongan;
use App\Models\Mahasiswa;
use App\Models\MbkmKonversi;
use App\Models\MbkmLogbook;
use App\Models\MbkmMitra;
use App\Models\Nilai;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Models\Surat;
use App\Models\User;
use App\Models\WisudaBatch;
use App\Models\WisudaPeserta;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelulusanMbkmTest extends TestCase
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

    private function lulusanSiap(): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create();
        $kelas = Kelas::factory()->create();
        Nilai::create([
            'mahasiswa_id' => $mhs->id, 'kelas_id' => $kelas->id,
            'tugas' => 85, 'uts' => 85, 'uas' => 85, 'kehadiran' => 100,
            'na' => 85, 'huruf' => 'A', 'bobot' => 4.0, 'is_locked' => true,
        ]);
        $judul = SkripsiJudul::create([
            'mahasiswa_id' => $mhs->id, 'judul' => 'Sistem MBKM Terpadu',
            'deskripsi' => 'x', 'status' => 'disetujui',
        ]);
        Sidang::create([
            'judul_id' => $judul->id, 'jenis' => 'tutup',
            'tanggal' => now()->addDay(), 'status' => 'lulus',
        ]);

        return $mhs;
    }

    public function test_mbkm_mitra_konversi_logbook(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $mitra = MbkmMitra::create(['nama' => 'PT Maju', 'jenis' => 'industri', 'aktif' => true]);
        $mk = Kelas::factory()->create()->matakuliah;

        $konversi = MbkmKonversi::create([
            'mahasiswa_id' => $mhs->id, 'mitra_id' => $mitra->id,
            'jenis_kegiatan' => 'magang', 'matakuliah_id' => $mk->id,
            'sks_diakui' => 6, 'huruf' => 'A', 'status' => 'disetujui',
        ]);
        MbkmLogbook::create([
            'konversi_id' => $konversi->id, 'tanggal' => now()->toDateString(),
            'aktivitas' => 'Orientasi + setup proyek', 'status' => 'disetujui',
        ]);

        $this->assertSame(1, $konversi->logbook()->count());
        $this->assertSame(6, (int) $konversi->fresh()->sks_diakui);
    }

    public function test_magang_hub_lamaran_unik(): void
    {
        $mitra = MbkmMitra::create(['nama' => 'PT Hub', 'jenis' => 'industri']);
        $low = MagangLowongan::create([
            'mitra_id' => $mitra->id, 'judul' => 'Backend Intern',
            'deskripsi' => 'Laravel', 'kuota' => 3, 'status' => 'buka',
        ]);
        $mhs = Mahasiswa::factory()->create();
        MagangLamaran::create(['lowongan_id' => $low->id, 'mahasiswa_id' => $mhs->id, 'status' => 'diajukan']);

        $this->expectException(QueryException::class);
        MagangLamaran::create(['lowongan_id' => $low->id, 'mahasiswa_id' => $mhs->id, 'status' => 'diajukan']);
    }

    public function test_yudisium_terbit_skl_alumni_wisuda_toga_pin(): void
    {
        $mhs = $this->lulusanSiap();

        Livewire::actingAs($this->admin())
            ->test(Yudisium::class)
            ->call('luluskan', $mhs->id)
            ->assertSee('dinyatakan lulus');

        $this->assertSame('lulus', $mhs->fresh()->status);
        $this->assertDatabaseHas('surat', ['mahasiswa_id' => $mhs->id, 'jenis' => 'keterangan_lulus', 'status' => 'selesai']);
        $this->assertNotNull(Surat::where('mahasiswa_id', $mhs->id)->first()->nomor_surat);
        $this->assertDatabaseHas('alumni', ['mahasiswa_id' => $mhs->id]);

        $batch = WisudaBatch::create(['nama' => 'Wisuda 72', 'periode' => '2026/2027', 'kuota' => 500, 'status' => 'dibuka']);

        Livewire::actingAs($this->admin())
            ->test(Yudisium::class)
            ->set('batchId', $batch->id)
            ->call('daftarkanWisuda', $mhs->id)
            ->assertSee('Masuk batch');

        $peserta = WisudaPeserta::where('mahasiswa_id', $mhs->id)->firstOrFail();
        $this->assertNotNull($peserta->no_ijazah);
        $this->assertNotNull($peserta->pin_ijazah);

        Livewire::actingAs($this->admin())
            ->test(Yudisium::class)
            ->set('batchId', $batch->id)
            ->call('updateToga', $peserta->id, 'pinjam')
            ->assertSee('pinjam');

        $this->assertSame('pinjam', $peserta->fresh()->status_toga);

        $this->actingAs($this->admin())->get(route('skripsi.yudisium'))->assertOk();
        $this->get(route('publik.alumni'))->assertOk();
        $this->get(route('publik.lowongan'))->assertOk();
        $this->assertTrue(Alumni::where('mahasiswa_id', $mhs->id)->exists());
    }
}
