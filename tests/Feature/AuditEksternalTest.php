<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\SsoController;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\Payment\MidtransAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditEksternalTest extends TestCase
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

        Route::middleware('web')->group(function (): void {
            Route::get('/auth/{provider}/callback', [SsoController::class, 'callback']);
        });

        Cache::flush();
    }

    public function test_refund_midtrans_sukses_mencatat_audit(): void
    {
        Http::fake(['*' => Http::response(['status_code' => '200'], 200)]);

        $ok = (new MidtransAdapter)->refund('ORDER-1', 50000);

        $this->assertTrue($ok);

        $log = AuditLog::where('action', 'gateway.refund')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('ORDER-1', $log->diff_json['reference'] ?? null);
        $this->assertSame(50000, $log->diff_json['nominal'] ?? null);
        $this->assertSame('sukses', $log->diff_json['hasil'] ?? null);
    }

    public function test_broadcast_topik_tanpa_kredensial_tetap_mencatat_audit(): void
    {
        $this->assertFalse(app(NotifikasiService::class)->broadcastTopik(3, 'Judul', 'Isi'));

        $log = AuditLog::where('action', 'notifikasi.broadcast_topik')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('dilewati', $log->diff_json['hasil'] ?? null);
        $this->assertSame(3, $log->diff_json['prodi_id'] ?? null);
    }

    public function test_sso_login_sukses_mencatat_audit(): void
    {
        $user = User::factory()->create(['email' => 'terdaftar@example.com']);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'token-abc', 'token_type' => 'Bearer'], 200),
            'www.googleapis.com/*' => Http::response([
                'email' => 'terdaftar@example.com',
                'email_verified' => true,
                'name' => 'Pengguna Terdaftar',
            ], 200),
        ]);

        $this->withSession(['sso.state' => 'state-benar', 'sso.nonce' => 'nonce-benar'])
            ->get('/auth/google/callback?state=state-benar&code=kode-abc')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $log = AuditLog::where('action', 'sso.login')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, (int) $log->model_id);
        $this->assertSame(User::class, $log->model);
    }
}
