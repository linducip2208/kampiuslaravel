<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\TteService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function isiKredensial(): void
    {
        Setting::set('tte.base_url', 'https://tte.test');
        Setting::set('tte.username', 'user-tte');
        Setting::set('tte.password', 'rahasia');
    }

    public function test_tanpa_setting_belum_dikonfigurasi(): void
    {
        $layanan = app(TteService::class);

        $this->assertFalse($layanan->isConfigured());
        $this->assertSame(['ok' => false, 'message' => 'Belum dikonfigurasi'], $layanan->status());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TTE belum dikonfigurasi');
        $layanan->sign('ABCDEF', '001/UN/2026');
    }

    public function test_status_ok_bila_layanan_merespons(): void
    {
        $this->isiKredensial();

        Http::fake(['tte.test/status' => Http::response(['layanan' => 'siap'], 200)]);

        $hasil = app(TteService::class)->status();

        $this->assertTrue($hasil['ok']);
    }

    public function test_sign_sukses_mengirim_payload(): void
    {
        $this->isiKredensial();

        Http::fake(['tte.test/sign' => Http::response(['dokumen' => 'tertandatangan'], 200)]);

        $hasil = app(TteService::class)->sign('ABCDEF123', '001/UN/2026');

        $this->assertTrue($hasil['ok']);
        $this->assertSame(['dokumen' => 'tertandatangan'], $hasil['data']);

        Http::assertSent(function ($req) {
            return $req->url() === 'https://tte.test/sign'
                && $req['hash'] === 'ABCDEF123'
                && $req['nomor'] === '001/UN/2026';
        });
    }

    public function test_sign_http_500_melempar_runtime_exception(): void
    {
        $this->isiKredensial();

        Http::fake(['tte.test/sign' => Http::response(['message' => 'Kesalahan server'], 500)]);

        $this->expectException(RuntimeException::class);
        app(TteService::class)->sign('ABCDEF123', '001/UN/2026');
    }

    /** Controller memakai TteService: 422 tanpa konfigurasi, data terus saat sukses. */
    public function test_controller_tte_memakai_layanan(): void
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $this->actingAs($pimpinan)->postJson('/pddikti/tte', ['dokumen_hash' => 'ABC'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Integrasi TTE BSrE belum dikonfigurasi di halaman Integrasi (tte.base_url).');

        $this->isiKredensial();

        Http::fake(['tte.test/sign' => Http::response(['signed' => true, 'id' => 'DOC-1'], 200)]);

        $this->actingAs($pimpinan)->postJson('/pddikti/tte', ['dokumen_hash' => 'ABC', 'nomor' => '001/UN/2026'])
            ->assertOk()
            ->assertJsonPath('signed', true);
    }
}
