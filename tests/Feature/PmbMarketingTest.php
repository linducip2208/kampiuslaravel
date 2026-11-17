<?php

namespace Tests\Feature;

use App\Models\Beasiswa;
use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use App\Models\PmbReferral;
use App\Models\Prodi;
use App\Models\Setting;
use App\Models\User;
use App\Services\PmbVaService;
use App\Services\WaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PmbMarketingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function pimpinan(): User
    {
        $u = User::factory()->create();
        $u->assignRole('pimpinan');

        return $u;
    }

    public function test_referral_tabel_tersedia(): void
    {
        $this->assertTrue(Schema::hasTable('pmb_referrals'));

        $ref = PmbReferral::create(['kode' => 'SMAN1-001', 'sekolah' => 'SMA Negeri 1', 'aktif' => true]);

        $this->assertDatabaseHas('pmb_referrals', ['kode' => 'SMAN1-001']);
        $this->assertSame('SMA Negeri 1', $ref->sekolah);
    }

    public function test_pmb_store_terbitkan_va_dengan_referral_dan_beasiswa(): void
    {
        $gelombang = PmbGelombang::factory()->create(['biaya' => 500000]);
        $prodi = Prodi::factory()->create();
        PmbReferral::create(['kode' => 'SMAN1-001', 'sekolah' => 'SMA Negeri 1', 'aktif' => true]);
        $bea = Beasiswa::create(['nama' => 'PMB Prestasi 50%', 'jenis' => 'persen', 'nilai' => 50, 'aktif' => true]);

        $response = $this->post(route('pmb.store'), [
            'nama' => 'Marketing Test',
            'email' => 'marketing@example.com',
            'hp' => '081234567891',
            'sekolah_asal' => 'SMA Negeri 1',
            'referral_kode' => 'SMAN1-001',
            'beasiswa_id' => $bea->id,
            'gelombang_id' => $gelombang->id,
            'prodi1_id' => $prodi->id,
        ]);

        $pendaftar = PmbPendaftar::query()->where('email', 'marketing@example.com')->firstOrFail();

        $this->assertSame('SMAN1-001', $pendaftar->referral_kode);
        $this->assertSame($bea->id, (int) $pendaftar->beasiswa_id);
        $this->assertNotNull($pendaftar->va_number);
        $this->assertNotNull($pendaftar->va_reference);
        $this->assertSame(250000, app(PmbVaService::class)->nominal($pendaftar));
        $this->assertSame('belum_bayar', $pendaftar->bayar_status);

        $response->assertRedirect(route('pmb.cek', ['no_daftar' => $pendaftar->no_daftar]));
        $response->assertSessionHas('sukses');
    }

    public function test_prodi_detail_memuat_seo_meta(): void
    {
        $prodi = Prodi::factory()->create([
            'deskripsi' => 'Prodi unggulan dengan kurikulum merdeka.',
            'seo_title' => 'Prodi Test SEO',
            'seo_description' => 'Deskripsi SEO prodi test.',
        ]);

        $response = $this->get(route('publik.prodi.detail', $prodi->slug));

        $response->assertOk();
        $response->assertSee('name="description"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('Prodi Test SEO');
    }

    public function test_funnel_butuh_role_pimpinan(): void
    {
        $this->get(route('pmb.funnel'))->assertRedirect(route('login'));

        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('pmb.funnel'))->assertForbidden();

        PmbPendaftar::factory()->create();

        $this->actingAs($this->pimpinan())->get(route('pmb.funnel'))
            ->assertOk()
            ->assertSee('Funnel');
    }

    public function test_wa_follow_up_tercatat(): void
    {
        $pendaftar = PmbPendaftar::factory()->create([
            'status' => PmbPendaftar::STATUS_DIAJUKAN,
            'last_follow_up_at' => null,
        ]);

        // Mode simulasi (tanpa gateway): return true tapi timestamp TIDAK
        // dibump — jujur, bukan klaim terkirim.
        $this->assertTrue(app(WaService::class)->followUp($pendaftar));
        $this->assertNull($pendaftar->fresh()->last_follow_up_at);

        // Gateway terkonfigurasi: timestamp dicatat.
        Setting::set('wa.gateway_url', 'https://wa.example.test/send');
        Setting::set('wa.token', 'tok');
        Http::fake(['*' => Http::response(['ok' => true])]);

        $pendaftar2 = PmbPendaftar::factory()->create([
            'status' => PmbPendaftar::STATUS_DIAJUKAN,
            'last_follow_up_at' => null,
        ]);

        $this->assertTrue(app(WaService::class)->followUp($pendaftar2));
        $this->assertNotNull($pendaftar2->fresh()->last_follow_up_at);
    }
}
