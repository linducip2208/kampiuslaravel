<?php

namespace Tests\Feature;

use App\Livewire\Admin\Integrasi as AdminIntegrasi;
use App\Livewire\Master\Data;
use App\Models\Mahasiswa;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class IntegrasiTest extends TestCase
{
    use RefreshDatabase;

    private function tagihan(): Tagihan
    {
        $mhs = Mahasiswa::factory()->create();

        return Tagihan::create([
            'mahasiswa_id' => $mhs->id,
            'tahun_akademik_id' => TahunAkademik::factory()->create()->id,
            'jenis' => 'UKT',
            'nominal' => 5000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);
    }

    public function test_simulated_va_settles_tagihan(): void
    {
        Setting::set('pay.driver', 'simulated');

        $pay = app(PaymentService::class);
        $pembayaran = $pay->createVa($this->tagihan(), ['nama' => 'Tes']);

        $this->assertNotNull($pembayaran->reference);
        $this->assertSame('pending', $pembayaran->status);
        $this->assertTrue($pay->settleByReference($pembayaran->reference));
        $this->assertSame('lunas', $pembayaran->tagihan->fresh()->status);
    }

    public function test_callback_simulated_marks_paid(): void
    {
        Setting::set('pay.driver', 'simulated');
        $pembayaran = app(PaymentService::class)->createVa($this->tagihan(), []);

        $this->postJson(route('payment.callback'), ['status' => 'paid', 'reference' => $pembayaran->reference])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('verified', $pembayaran->fresh()->status);
    }

    public function test_callback_rejects_invalid(): void
    {
        $this->postJson(route('payment.callback'), ['status' => 'x'])->assertStatus(400);
    }

    public function test_notifikasi_broadcast_and_api(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');

        $this->assertSame(1, app(NotifikasiService::class)->broadcast('mahasiswa', 'Halo', 'Isi'));

        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/notifikasi')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $notif = Notifikasi::first();
        $this->withToken($token)->postJson("/api/v1/notifikasi/{$notif->id}/read")->assertOk();
        $this->assertNotNull($notif->fresh()->dibaca_at);
    }

    public function test_pddikti_export_csv(): void
    {
        $this->tagihan();

        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('pimpinan');
        $this->actingAs($user)->get(route('pddikti.export', 'mahasiswa'))->assertOk();
        $this->actingAs($user)->get(route('pddikti.export', 'nilai'))->assertOk();
        $this->actingAs($user)->get(route('pddikti.export', 'kelulusan'))->assertOk();
    }

    public function test_integrasi_page_renders(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        $this->actingAs($user)->get(route('admin.integrasi'))->assertOk();
    }

    public function test_drivers_come_from_config(): void
    {
        $drivers = PaymentService::availableDrivers();

        $this->assertArrayHasKey('simulated', $drivers);
        $this->assertArrayHasKey('tripay', $drivers);
        $this->assertArrayHasKey('midtrans', $drivers);
        $this->assertArrayHasKey('xendit', $drivers);
        $this->assertInstanceOf(
            PaymentGatewayInterface::class,
            app(PaymentService::class)->driver()
        );
    }

    public function test_unknown_driver_throws_helpful_error(): void
    {
        Setting::set('pay.driver', 'tidak_ada');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/config\/payment\.php/');
        app(PaymentService::class)->driver();
    }

    public function test_xendit_create_via_http_fake(): void
    {
        Setting::set('pay.driver', 'xendit');
        Setting::set('pay.server_key', 'xnd_test');
        Setting::set('pay.bank', 'BNI');

        Http::fake([
            '*' => Http::response([
                'external_id' => 'simak-1-abc123',
                'account_number' => '8808000123',
                'expiration_date' => now()->addDay()->toIso8601String(),
            ], 200),
        ]);

        $pembayaran = app(PaymentService::class)->createVa($this->tagihan(), ['nama' => 'Tes']);

        $this->assertSame('8808000123', $pembayaran->va_number);
        $this->assertStringStartsWith('va_xendit', $pembayaran->metode);
    }

    public function test_status_endpoint_syncs(): void
    {
        Setting::set('pay.driver', 'simulated');

        $this->seed(RolesPermissionsSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('mahasiswa');
        $tagihan = Tagihan::create([
            'mahasiswa_id' => Mahasiswa::factory()->create(['user_id' => $owner->id])->id,
            'tahun_akademik_id' => TahunAkademik::factory()->create()->id,
            'jenis' => 'UKT',
            'nominal' => 1000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);
        $pembayaran = app(PaymentService::class)->createVa($tagihan, []);

        $this->actingAs($owner)->getJson(route('keuangan.bayar.status', $pembayaran))
            ->assertOk()
            ->assertJson(['reference' => $pembayaran->reference, 'status' => 'pending']);
    }

    public function test_tripay_create_via_http_fake(): void
    {
        Setting::set('pay.driver', 'tripay');
        Setting::set('pay.merchant_code', 'T12345');
        Setting::set('pay.server_key', 'tripay-api-key');
        Setting::set('pay.private_key', 'secret-key');
        Setting::set('pay.method', 'BRIVA');

        Http::fake([
            'tripay.co.id/api-sandbox/transaction/create' => Http::response([
                'success' => true,
                'message' => 'Created',
                'data' => [
                    'merchant_ref' => 'SIMAK-1-ABCDEF',
                    'pay_code' => '80070123456789',
                    'expired_time' => now()->addDay()->timestamp,
                    'status' => 'UNPAID',
                ],
            ], 200),
        ]);

        $pembayaran = app(PaymentService::class)->createVa($this->tagihan(), ['nama' => 'Tes']);

        $this->assertSame('80070123456789', $pembayaran->va_number);
        $this->assertStringStartsWith('va_tripay', $pembayaran->metode);
        $this->assertSame('SIMAK-1-ABCDEF', $pembayaran->reference);
    }

    public function test_tripay_callback_route_settles_on_paid(): void
    {
        Setting::set('pay.driver', 'tripay');
        Setting::set('pay.private_key', 'secret-key');

        $raw = json_encode([
            'reference' => 'TRX123',
            'merchant_ref' => 'SIMAK-9-ZZZZZZ',
            'status' => 'PAID',
        ]);
        $sig = hash_hmac('sha256', $raw, 'secret-key');

        $tagihan = $this->tagihan();
        $pembayaran = Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'reference' => 'SIMAK-9-ZZZZZZ',
            'va_number' => '8007099999',
            'metode' => 'va_tripay',
            'nominal' => $tagihan->nominal,
            'status' => 'pending',
        ]);

        $this->call('POST', route('payment.callback'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => $sig,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
        ], $raw)->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('verified', $pembayaran->fresh()->status);
        $this->assertSame('lunas', $tagihan->fresh()->status);
    }

    public function test_tripay_callback_rejects_bad_signature(): void
    {
        Setting::set('pay.driver', 'tripay');
        Setting::set('pay.private_key', 'secret-key');

        $this->call('POST', route('payment.callback'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_SIGNATURE' => 'salah',
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
        ], json_encode(['merchant_ref' => 'X', 'status' => 'PAID']))->assertStatus(400);
    }

    public function test_create_va_ditolak_bila_admin_belum_isi_kredensial(): void
    {
        Setting::set('pay.driver', 'tripay');
        Setting::set('pay.merchant_code', null);
        Setting::set('pay.server_key', null);
        Setting::set('pay.private_key', null);

        $this->seed(RolesPermissionsSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('mahasiswa');
        $tagihan = Tagihan::create([
            'mahasiswa_id' => Mahasiswa::factory()->create(['user_id' => $owner->id])->id,
            'tahun_akademik_id' => TahunAkademik::factory()->create()->id,
            'jenis' => 'UKT',
            'nominal' => 1000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);

        $this->actingAs($owner)->postJson(route('keuangan.bayar.va', $tagihan))
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'Integrasi'));
    }

    public function test_tes_koneksi_fcm_lapor_belum_dikonfigurasi(): void
    {
        Setting::set('fcm.project_id', null);
        Setting::set('fcm.service_account_path', null);

        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        Livewire::actingAs($user)
            ->test(AdminIntegrasi::class)
            ->call('test', 'fcm')
            ->assertSet('tes.fcm', 'Belum dikonfigurasi (isi Project ID + path JSON service-account). Notifikasi tetap masuk inbox.');
    }

    public function test_api_buat_va_simulated(): void
    {
        Setting::set('pay.driver', 'simulated');

        $owner = User::factory()->create();
        $tagihan = Tagihan::create([
            'mahasiswa_id' => Mahasiswa::factory()->create(['user_id' => $owner->id])->id,
            'tahun_akademik_id' => TahunAkademik::factory()->create()->id,
            'jenis' => 'UKT',
            'nominal' => 2000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
        ]);

        $token = $owner->createToken('mobile', ['mobile'])->plainTextToken;

        $this->withToken($token)->postJson("/api/v1/tagihan/{$tagihan->id}/va")
            ->assertCreated()
            ->assertJsonStructure(['reference', 'va_number', 'nominal']);
    }

    public function test_master_crud_butuh_admin_dan_berfungsi(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('admin.master'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin_prodi');
        $this->actingAs($admin)->get(route('admin.master'))->assertOk();

        Livewire::actingAs($admin)
            ->test(Data::class)
            ->set('entitas', 'ruang')
            ->set('form.gedung', 'Gedung A')
            ->set('form.nama', 'R101')
            ->set('form.kapasitas', 40)
            ->call('simpan')
            ->assertSee('Data tersimpan');

        $this->assertDatabaseHas('ruang', ['nama' => 'R101', 'kapasitas' => 40]);
    }
}
