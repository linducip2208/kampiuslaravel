<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Pertemuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_token_registrasi_idempoten_dengan_topik_prodi(): void
    {
        // Guard: tanpa token harus 401 (cek dulu sebelum guard Sanctum me-resolve user).
        $this->postJson('/api/v1/device-token', ['fcm_token' => 'x'])->assertUnauthorized();

        $mhs = Mahasiswa::factory()->create();
        $token = $mhs->user->createToken('mobile', ['mobile'])->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $payload = ['fcm_token' => 'fcm-abc-123', 'platform' => 'android', 'app_version' => '0.1.0'];

        $this->postJson('/api/v1/device-token', $payload, $headers)
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('topic', "prodi_{$mhs->prodi_id}");

        // Retry dengan token sama: tetap satu baris (idempoten).
        $this->postJson('/api/v1/device-token', $payload, $headers)->assertCreated();
        $this->assertDatabaseCount('device_tokens', 1);
    }

    public function test_absensi_scan_idempoten_dengan_kunci(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $token = $mhs->user->createToken('mobile', ['mobile'])->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $pertemuan = Pertemuan::create([
            'kelas_id' => Kelas::factory()->create()->id,
            'ke' => 1,
            'tanggal' => now()->toDateString(),
            'topik' => 'Intro',
            'qr_token' => Str::random(32),
            'qr_expires_at' => now()->addHour(),
        ]);

        $payload = ['qr_token' => $pertemuan->qr_token, 'idempotency_key' => Str::uuid()->toString()];

        $first = $this->postJson('/api/v1/absensi/scan', $payload, $headers);
        $first->assertCreated();

        // Replay dengan kunci sama: tidak menambah baris, flag duplicate.
        $retry = $this->postJson('/api/v1/absensi/scan', $payload, $headers);
        $retry->assertOk()->assertJsonPath('duplicate', true);

        // Scan ulang tanpa kunci pada pertemuan+mhs sama: 200 duplicate.
        $again = $this->postJson('/api/v1/absensi/scan', ['qr_token' => $pertemuan->qr_token], $headers);
        $again->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseCount('absensi', 1);
    }
}
