<?php

namespace Tests\Feature;

use App\Livewire\Notifikasi\Broadcast;
use App\Models\PmbPendaftar;
use App\Models\Setting;
use App\Models\User;
use App\Services\WaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WaSimulasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_tanpa_konfigurasi_tetap_true_tapi_terdeteksi_simulasi(): void
    {
        Setting::set('wa.gateway_url', null);
        Setting::set('wa.token', null);

        $this->assertTrue(app(WaService::class)->send('081234567890', 'Halo'));
        $this->assertTrue(WaService::isSimulated());
    }

    public function test_kirim_wa_tanpa_konfigurasi_lapor_simulasi_tanpa_bump_timestamp(): void
    {
        Setting::set('wa.gateway_url', null);
        Setting::set('wa.token', null);

        $this->seed(RolesPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $pendaftar = PmbPendaftar::factory()->create([
            'status' => PmbPendaftar::STATUS_DIAJUKAN,
            'last_follow_up_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(Broadcast::class)
            ->set('waMessage', 'Pengingat PMB')
            ->call('kirimWa')
            ->assertSet('message', fn ($msg) => str_contains($msg, 'simulasi'));

        $this->assertNull($pendaftar->fresh()->last_follow_up_at);
    }

    public function test_send_dengan_gateway_terisi_bukan_simulasi(): void
    {
        Setting::set('wa.gateway_url', 'https://wa.example.test/kirim');
        Setting::set('wa.token', 'tok123');

        Http::fake(['*' => Http::response([], 200)]);

        $this->assertTrue(app(WaService::class)->send('081234567890', 'Halo'));
        $this->assertFalse(WaService::isSimulated());
    }
}
