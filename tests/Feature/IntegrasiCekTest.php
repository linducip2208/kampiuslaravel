<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrasiCekTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $kunci = [
        'pddikti.base_url', 'pddikti.username', 'pddikti.password',
        'pay.driver', 'pay.merchant_code', 'pay.server_key', 'pay.private_key',
        'wa.gateway_url', 'wa.token',
        'fcm.project_id', 'fcm.service_account_path',
        'sso.google_client_id', 'sso.google_client_secret',
        'sso.microsoft_client_id', 'sso.microsoft_client_secret',
        'tte.base_url', 'tte.username', 'tte.password',
        'ai.driver', 'ai.base_url', 'ai.api_key',
    ];

    private ?string $berkasFcm = null;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Http::preventStrayRequests(true);
        Cache::flush();

        foreach ($this->kunci as $key) {
            Setting::set($key, null);
        }
    }

    protected function tearDown(): void
    {
        if (is_string($this->berkasFcm) && is_file($this->berkasFcm)) {
            @unlink($this->berkasFcm);
        }

        parent::tearDown();
    }

    private function jalankan(string $harusMuat = 'belum dikonfigurasi'): void
    {
        $this->artisan('integrasi:cek')
            ->expectsOutputToContain($harusMuat)
            ->assertExitCode(0);
    }

    public function test_tanpa_konfigurasi_exit_nol_dan_lapor_belum_dikonfigurasi(): void
    {
        $this->jalankan('belum dikonfigurasi');

        Http::assertNothingSent();
    }

    public function test_fcm_file_valid_bar_o_k_tanpa_http(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fcm-test-').'.json';
        file_put_contents($path, json_encode(['client_email' => 'tes@proyek.iam.gserviceaccount.com']));
        $this->berkasFcm = $path;

        Setting::set('fcm.project_id', 'tes-proyek');
        Setting::set('fcm.service_account_path', $path);

        $this->jalankan('fcm');
        $this->jalankan('tes@proyek.iam.gserviceaccount.com');
        Http::assertNothingSent();
    }
}
