<?php

namespace Tests\Feature;

use App\Livewire\Pmb\Verifikasi;
use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PmbFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_publik_dapat_mendaftar_dan_mendapat_no_daftar(): void
    {
        $gelombang = PmbGelombang::factory()->create();
        $prodi1 = Prodi::factory()->create();
        $prodi2 = Prodi::factory()->create();

        $response = $this->post(route('pmb.store'), [
            'nama' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'hp' => '081234567890',
            'sekolah_asal' => 'SMA Negeri 1',
            'gelombang_id' => $gelombang->id,
            'prodi1_id' => $prodi1->id,
            'prodi2_id' => $prodi2->id,
        ]);

        $pendaftar = PmbPendaftar::query()->where('email', 'budi@example.com')->first();

        $this->assertNotNull($pendaftar);
        $this->assertSame(PmbPendaftar::STATUS_DIAJUKAN, $pendaftar->status);
        $this->assertMatchesRegularExpression('/^PMB-\d{4}-\d{6}$/', $pendaftar->no_daftar);

        $response->assertRedirect(route('pmb.cek', ['no_daftar' => $pendaftar->no_daftar]));
    }

    public function test_validasi_pendaftaran_publik(): void
    {
        $response = $this->post(route('pmb.store'), []);
        $response->assertSessionHasErrors(['nama', 'email', 'hp', 'sekolah_asal', 'gelombang_id', 'prodi1_id']);

        PmbPendaftar::factory()->create([
            'email' => 'ganda@example.com',
            'gelombang_id' => PmbGelombang::factory()->create([
                'tahun_akademik_id' => TahunAkademik::factory()->create(['tahun' => '2000/2001', 'semester' => 'ganjil'])->id,
            ])->id,
        ]);

        $gelombang = PmbGelombang::factory()->create([
            'tahun_akademik_id' => TahunAkademik::factory()->create(['tahun' => '2001/2002', 'semester' => 'ganjil'])->id,
        ]);

        $response = $this->post(route('pmb.store'), [
            'nama' => 'AB',
            'email' => 'ganda@example.com',
            'hp' => 'xx',
            'sekolah_asal' => 'SM',
            'gelombang_id' => $gelombang->id + 999,
            'prodi1_id' => 1,
            'prodi2_id' => 1,
        ]);

        $response->assertSessionHasErrors(['nama', 'email', 'hp', 'sekolah_asal', 'gelombang_id', 'prodi2_id']);
    }

    public function test_pengumuman_dapat_dicek_via_no_daftar(): void
    {
        $pendaftar = PmbPendaftar::factory()->create(['status' => PmbPendaftar::STATUS_LULUS]);

        $response = $this->get(route('pmb.cek', ['no_daftar' => $pendaftar->no_daftar]));

        $response->assertOk();
        $response->assertSee($pendaftar->no_daftar);
        $response->assertSee(PmbPendaftar::STATUS_LULUS);

        $response = $this->get(route('pmb.cek', ['no_daftar' => 'PMB-2099-000000']));
        $response->assertOk();
        $response->assertSee('tidak ditemukan');
    }

    public function test_admin_dapat_memverifikasi_pendaftar(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin_prodi');
        $pendaftar = PmbPendaftar::factory()->create(['status' => PmbPendaftar::STATUS_DIAJUKAN]);

        Livewire::actingAs($admin)
            ->test(Verifikasi::class)
            ->call('ubahStatus', $pendaftar->id, PmbPendaftar::STATUS_DIVERIFIKASI);

        $this->assertSame(
            PmbPendaftar::STATUS_DIVERIFIKASI,
            $pendaftar->fresh()->status
        );

        Livewire::actingAs($admin)
            ->test(Verifikasi::class)
            ->set('selectedId', $pendaftar->id)
            ->set('catatan', 'Berkas tidak lengkap.')
            ->call('ubahStatus', $pendaftar->id, PmbPendaftar::STATUS_DITOLAK);

        $pendaftar = $pendaftar->fresh();
        $this->assertSame(PmbPendaftar::STATUS_DITOLAK, $pendaftar->status);
        $this->assertSame('Berkas tidak lengkap.', $pendaftar->catatan_verifikasi);
    }
}
