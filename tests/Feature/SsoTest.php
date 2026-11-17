<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\SsoController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google', [
            'client_id' => 'google-test-id',
            'client_secret' => 'google-test-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);
        config()->set('services.microsoft', [
            'client_id' => 'ms-test-id',
            'client_secret' => 'ms-test-secret',
            'redirect' => 'http://localhost/auth/microsoft/callback',
        ]);

        Route::middleware('web')->group(function (): void {
            Route::get('/auth/{provider}/redirect', [SsoController::class, 'redirect']);
            Route::get('/auth/{provider}/callback', [SsoController::class, 'callback']);
        });
    }

    public function test_provider_tidak_dikenal_mengembalikan_404(): void
    {
        $this->get('/auth/github/redirect')->assertNotFound();
        $this->get('/auth/github/callback?state=x&code=y')->assertNotFound();
    }

    public function test_tanpa_konfigurasi_kembali_ke_login_dengan_error(): void
    {
        config()->set('services.google.client_id', '');
        config()->set('services.google.client_secret', '');
        config()->set('services.google.redirect', '');

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'SSO belum dikonfigurasi']);
    }

    public function test_state_tidak_valid_mengembalikan_403(): void
    {
        $this->withSession(['sso.state' => 'state-benar', 'sso.nonce' => 'nonce-benar'])
            ->get('/auth/google/callback?state=state-salah&code=kode-abc')
            ->assertForbidden();
    }

    public function test_email_tidak_dikenal_mengembalikan_403(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'token-abc', 'token_type' => 'Bearer'], 200),
            'www.googleapis.com/*' => Http::response([
                'email' => 'asing@example.com',
                'email_verified' => true,
                'name' => 'Orang Asing',
            ], 200),
        ]);

        $this->withSession(['sso.state' => 'state-benar', 'sso.nonce' => 'nonce-benar'])
            ->get('/auth/google/callback?state=state-benar&code=kode-abc')
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_email_dikenal_login_dan_redirect_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'terdaftar@example.com']);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'token-abc', 'token_type' => 'Bearer'], 200),
            'www.googleapis.com/*' => Http::response([
                'email' => 'Terdaftar@Example.com',
                'email_verified' => true,
                'name' => 'Pengguna Terdaftar',
            ], 200),
        ]);

        $this->withSession(['sso.state' => 'state-benar', 'sso.nonce' => 'nonce-benar'])
            ->get('/auth/google/callback?state=state-benar&code=kode-abc')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_microsoft_email_dikenal_login_berhasil(): void
    {
        $user = User::factory()->create(['email' => 'pegawai@example.com']);

        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'token-ms', 'token_type' => 'Bearer'], 200),
            'graph.microsoft.com/*' => Http::response([
                'mail' => 'pegawai@example.com',
                'userPrincipalName' => 'pegawai@example.com',
                'displayName' => 'Pegawai',
            ], 200),
        ]);

        $this->withSession(['sso.state' => 'state-ms', 'sso.nonce' => 'nonce-ms'])
            ->get('/auth/microsoft/callback?state=state-ms&code=kode-ms')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_redirect_mengarah_ke_authorization_url_dengan_state(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $target = (string) $response->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $target);
        $this->assertStringContainsString('client_id=google-test-id', $target);
        $this->assertStringContainsString('response_type=code', $target);
        $this->assertStringContainsString('state=', $target);
        $this->assertStringContainsString('nonce=', $target);
    }
}
