<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\PddiktiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeederGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_menolak_ip_loopback_bila_guard_aktif(): void
    {
        Setting::set('pddikti.base_url', 'http://127.0.0.1:8082/x');
        Setting::set('pddikti.guard_ssrf', '1');
        Setting::set('pddikti.username', 'u');
        Setting::set('pddikti.password', 'p');

        Http::preventStrayRequests(true);
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/privat|reserved|diblokir/i');

        try {
            app(PddiktiService::class)->token();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_token_lolos_bila_guard_mati(): void
    {
        Setting::set('pddikti.base_url', 'http://feeder.test/ws/live2.php');
        Setting::set('pddikti.guard_ssrf', '0');
        Setting::set('pddikti.username', 'u');
        Setting::set('pddikti.password', 'p');

        Http::fake(['*' => Http::response(['error' => 0, 'data' => ['token' => 'tok123']], 200)]);

        $this->assertSame('tok123', app(PddiktiService::class)->token());
    }

    public function test_token_menolak_host_tidak_valid(): void
    {
        Setting::set('pddikti.base_url', '::://');
        Setting::set('pddikti.guard_ssrf', '0');

        Http::preventStrayRequests(true);
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/tidak valid|host/i');

        app(PddiktiService::class)->token();
    }
}
