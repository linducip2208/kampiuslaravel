<?php

namespace Tests\Feature;

use App\Livewire\Sidang\NilaiSidang;
use App\Livewire\Sidang\Pendaftaran;
use App\Livewire\Skripsi\PengajuanJudul;
use App\Livewire\Skripsi\SetPembimbing;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Models\Surat;
use App\Models\User;
use App\Services\SuratNomorService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SkripsiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_happy_path_judul_sampai_sidang_lulus_dan_nomor_surat(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $mhs = Mahasiswa::factory()->create(['angkatan' => 2024]);
        $mhs->user->assignRole('mahasiswa');
        $admin = User::factory()->create();
        $admin->assignRole('admin_prodi');
        $dosen = Dosen::factory()->create();
        $dosen->user->assignRole('dosen');
        $ruang = Ruang::factory()->create();

        $this->actingAs($mhs->user);
        Livewire::test(PengajuanJudul::class)
            ->set('judul', 'Sistem Informasi Akademik')
            ->set('deskripsi', 'Membangun SIAKAD')
            ->call('save')
            ->assertSee('berhasil diajukan');

        $judul = SkripsiJudul::where('mahasiswa_id', $mhs->id)->firstOrFail();
        $this->assertSame('diajukan', $judul->status);

        $this->actingAs($admin);
        Livewire::test(SetPembimbing::class)
            ->set('pembimbing1_id', $dosen->id)
            ->set('status', 'disetujui')
            ->call('setPembimbing', $judul->id)
            ->assertSee('berhasil ditetapkan');

        $this->assertSame('disetujui', $judul->fresh()->status);

        $this->actingAs($mhs->user);
        Livewire::test(Pendaftaran::class)
            ->set('judul_id', $judul->id)
            ->set('jenis', 'tutup')
            ->set('tanggal', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('ruang_id', $ruang->id)
            ->call('save')
            ->assertSee('berhasil didaftarkan');

        $sidang = Sidang::where('judul_id', $judul->id)->firstOrFail();

        $this->actingAs($admin);
        Livewire::test(NilaiSidang::class)
            ->set('sidang_id', $sidang->id)
            ->set('dosen_id', $dosen->id)
            ->set('peran', 'pembimbing')
            ->set('nilai', 85)
            ->call('addPenguji')
            ->assertSee('tersimpan');

        Livewire::test(NilaiSidang::class)
            ->set('sidang_id', $sidang->id)
            ->set('status', 'lulus')
            ->call('setStatus')
            ->assertSee('diperbarui');

        $this->assertSame('lulus', $sidang->fresh()->status);
        $this->assertSame('lulus', $judul->fresh()->status);

        $surat = Surat::create([
            'mahasiswa_id' => $mhs->id, 'jenis' => 'keterangan_lulus',
            'keperluan' => 'wisuda', 'status' => 'diajukan',
        ]);
        $nomor = SuratNomorService::assignIfEmpty($surat);

        $this->assertMatchesRegularExpression(
            '/^KETERANGAN_LULUS\/UNIV\/'.date('Y').'\/\d{4}$/',
            $nomor
        );
    }
}
