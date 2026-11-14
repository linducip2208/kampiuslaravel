<?php

namespace Tests\Feature;

use App\Mail\TagihanReminderMail;
use App\Models\Kampus;
use App\Models\User;
use App\Scopes\KampusScope;
use App\Services\MailService;
use App\Services\PddiktiService;
use App\Services\WaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Wave D readiness: M-054 (desain menunggu pengesahan, tanpa kode prematur),
 * M-064 (channel email), matriks kesiapan adapter (kode siap, kredensial belum).
 */
class WaveDReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    /** M-054: desain ada + eksplisit MENUNGGU PENGESAHAN + tanpa kode isolasi. */
    public function test_desain_multikampus_menunggu_pengesahan(): void
    {
        $path = base_path('docs/DESAIN_MULTI_KAMPUS.md');
        $this->assertFileExists($path);

        $doc = (string) file_get_contents($path);
        $this->assertStringContainsString('MENUNGGU PENGESAHAN', $doc);
        $this->assertStringContainsString('Opsi A', $doc);
        $this->assertStringContainsString('KampusScope', $doc);

        // Aturan BACKLOG: tanpa kode isolasi sebelum pengesahan.
        $this->assertFalse(class_exists(KampusScope::class));
        $this->assertFalse(class_exists(Kampus::class));
    }

    /** M-064: guard konfigurasi SMTP + kirim via Mail::fake. */
    public function test_email_hanya_bila_smtp_terkonfigurasi(): void
    {
        // Env test: mailer array → tidak terkonfigurasi (no-op, tanpa error).
        $this->assertFalse(MailService::terkonfigurasi());

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '']);
        $this->assertFalse(MailService::terkonfigurasi());

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.kampus.ac.id']);
        $this->assertTrue(MailService::terkonfigurasi());

        Mail::fake();
        MailService::kirimTagihan('mhs@kampus.ac.id', 'Ani', 'Tagihan UKT 1jt.', 'UKT', '1.000.000');
        Mail::assertSent(TagihanReminderMail::class, fn ($m) => $m->hasTo('mhs@kampus.ac.id'));
    }

    /** M-064: command reminder tetap hijau + tidak kirim email bila tak terkonfigurasi. */
    public function test_reminder_tanpa_smtp_tidak_kirim_email(): void
    {
        User::factory()->create(['email' => 'mhs@kampus.ac.id']);

        Mail::fake();
        $this->artisan('tagihan:ingatkan', ['--arah' => 'h-7'])->assertSuccessful();
        Mail::assertNothingSent();
    }

    /** Matriks Wave D: adapter terdaftar, kredensial kosong → BLOCKED jujur. */
    public function test_matriks_kesiapan_wave_d(): void
    {
        // M-061 Feeder: adapter + tes koneksi + rute ada.
        $this->assertTrue(class_exists(PddiktiService::class));
        $this->assertTrue(Route::has('pddikti.sync'));
        $this->assertTrue(Route::has('pddikti.tte'));

        // M-062 gateway prod: pola driver + guard simulasi.
        $this->assertNotEmpty((array) config('payment.drivers', []));

        // M-063 WA: tanpa gateway → simulasi true (tidak error).
        $this->assertTrue(app(WaService::class)->send('081', 'tes'));

        // M-065 SSO: rute callback terdokumentasi di INTEGRASI (tanpa socialite = password tetap jalan).
        $this->assertStringContainsString('socialite', (string) file_get_contents(base_path('docs/INTEGRASI.md')));

        // M-066 TTE: endpoint forward + rute ada.
        $this->assertTrue(Route::has('pddikti.tte'));

        // M-064 FCM: registrasi token teruji (kontrak mobile) — lihat WaveCBatch2Test.
        $this->assertTrue(Route::has('payment.callback'));
    }
}
