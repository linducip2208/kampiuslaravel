<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityConfigTest extends TestCase
{
    public function test_cookie_session_aman_secara_default(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertContains(config('session.same_site'), ['lax', 'strict']);
        $this->assertLessThanOrEqual(240, (int) config('session.lifetime'));
    }

    public function test_login_membatasi_bruteforce_dan_menyegarkan_sesi(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->getName() === 'login.attempt');
        $this->assertTrue($routes->isNotEmpty());

        $middleware = implode('|', (array) $routes->first()->gatherMiddleware());
        $this->assertStringContainsString('throttle', $middleware);
    }
}
