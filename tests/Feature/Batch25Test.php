<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\NotifikasiService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Batch 25 ide bank: push topik FCM per prodi + rotasi token (opt-in).
 */
class Batch25Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    /** Tanpa kredensial: push dilewati dengan pesan, exit 0. */
    public function test_push_topik_tanpa_kredensial_dilewati(): void
    {
        $this->assertFalse(app(NotifikasiService::class)->broadcastTopik(1, 'Judul', 'Isi'));

        $this->artisan('push:topik', ['prodi' => '1', 'judul' => 'J', 'isi' => 'I'])
            ->expectsOutputToContain('dilewati')
            ->assertExitCode(0);
    }

    /** Full-path dengan kredensial RSA (butuh OpenSSL sehat; skip bila tidak). */
    public function test_push_topik_mengirim_ke_topic_prodi(): void
    {
        $cnf = 'D:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\extras\\ssl\\openssl.cnf';
        // Windows/Laragon butuh path config openssl eksplisit.
        $cfg = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        if (DIRECTORY_SEPARATOR === '\\' && is_file($cnf)) {
            $cfg['config'] = $cnf;
        }

        $res = openssl_pkey_new($cfg);
        $pem = '';
        if ($res !== false) {
            openssl_pkey_export($res, $pem);
        }

        if ($res === false || $pem === '') {
            $this->markTestSkipped('OpenSSL environment ini tidak bisa membuat kunci RSA.');
        }

        $sa = tempnam(sys_get_temp_dir(), 'sa').'.json';
        file_put_contents($sa, (string) json_encode([
            'client_email' => 'x@y.iam.gserviceaccount.com',
            // Kunci RSA fiktif agar penandatanganan JWT lolos (tanpa panggil Google).
            'private_key' => $pem,
        ]));

        Setting::set('fcm.project_id', 'demo-proj');
        Setting::set('fcm.service_account_path', $sa);

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo-proj/messages/1']),
        ]);

        try {
            $this->assertTrue(app(NotifikasiService::class)->broadcastTopik(7, 'UAS', 'Jadwal'));

            Http::assertSent(function ($req) {
                $data = $req->data();

                return str_contains((string) $req->url(), 'fcm.googleapis.com')
                    && ($data['message']['topic'] ?? null) === 'prodi_7';
            });
        } finally {
            if (is_file($sa)) {
                unlink($sa);
            }
        }
    }

    /** Payload topic terverifikasi deterministik (tanpa butuh kredensial). */
    public function test_kirim_pesan_topik_memakai_topic_prodi(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo/messages/1'])]);

        $this->assertTrue(app(NotifikasiService::class)->kirimPesanTopik('demo', 'tok', 7, 'UAS', 'Jadwal'));

        Http::assertSent(function ($req) {
            $data = $req->data();

            return str_contains((string) $req->url(), 'fcm.googleapis.com')
                && ($data['message']['topic'] ?? null) === 'prodi_7'
                && ($data['message']['notification']['title'] ?? null) === 'UAS';
        });
    }

    /** Default: token abadi (kontrak lama). Opt-in: expiry + refresh memutar. */
    public function test_token_abadi_default_dan_rotasi_opt_in(): void
    {
        $user = User::factory()->create(['password' => bcrypt('rahasia')]);

        // Default: tanpa expiry.
        $r1 = $this->postJson('/api/v1/login', ['nim_or_email' => $user->email, 'password' => 'rahasia'])->assertOk();
        $this->assertNull(DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->value('expires_at'));

        // Opt-in 30 hari.
        putenv('MOBILE_TOKEN_EXPIRY_DAYS=30');

        try {
            $r2 = $this->postJson('/api/v1/login', ['nim_or_email' => $user->email, 'password' => 'rahasia'])->assertOk();
            $this->assertNotNull(DB::table('personal_access_tokens')->orderByDesc('id')->value('expires_at'));

            // Refresh: token lama dicabut, token baru diterbitkan.
            $lama = $r2->json('token');
            $r3 = $this->postJson('/api/v1/token/refresh', [], ['Authorization' => 'Bearer '.$lama])->assertOk();
            $this->assertNotSame($lama, $r3->json('token'));

            // Guard singleton me-memo user antar-call dalam satu test;
            // lupakan agar request berikut dinilai ulang dari DB.
            auth()->forgetGuards();

            $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$lama])->assertUnauthorized();
            $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$r3->json('token')])->assertOk();
        } finally {
            putenv('MOBILE_TOKEN_EXPIRY_DAYS');
        }
    }
}
