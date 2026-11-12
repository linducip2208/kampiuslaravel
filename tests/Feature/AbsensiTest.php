<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Pertemuan;
use App\Services\AbsensiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AbsensiTest extends TestCase
{
    use RefreshDatabase;

    private int $kelasId;

    private int $mahasiswaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kelasId = Kelas::factory()->create()->id;
        $this->mahasiswaId = Mahasiswa::factory()->create()->id;
    }

    public function test_scan_token_valid_mencatat_hadir(): void
    {
        $pertemuan = Pertemuan::create([
            'kelas_id' => $this->kelasId,
            'ke' => 1,
            'tanggal' => now()->toDateString(),
            'topik' => 'Intro',
            'qr_token' => Str::random(32),
            'qr_expires_at' => now()->addHour(),
            'window_mulai' => now()->subMinutes(5),
            'window_selesai' => now()->addHour(),
        ]);

        $result = AbsensiService::scan($pertemuan, $this->mahasiswaId, $pertemuan->qr_token, now());

        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('absensi', [
            'pertemuan_id' => $pertemuan->id,
            'mahasiswa_id' => $this->mahasiswaId,
        ]);
    }

    public function test_scan_token_salah_ditolak(): void
    {
        $pertemuan = Pertemuan::create([
            'kelas_id' => $this->kelasId,
            'ke' => 2,
            'tanggal' => now()->toDateString(),
            'topik' => 'Topik 2',
            'qr_token' => Str::random(32),
            'qr_expires_at' => now()->addHour(),
        ]);

        $result = AbsensiService::scan($pertemuan, $this->mahasiswaId, 'token-salah', now());

        $this->assertFalse($result['ok']);
        $this->assertDatabaseMissing('absensi', [
            'pertemuan_id' => $pertemuan->id,
            'mahasiswa_id' => $this->mahasiswaId,
        ]);
    }

    public function test_scan_token_expired_ditolak(): void
    {
        $pertemuan = Pertemuan::create([
            'kelas_id' => $this->kelasId,
            'ke' => 3,
            'tanggal' => now()->toDateString(),
            'topik' => 'Topik 3',
            'qr_token' => Str::random(32),
            'qr_expires_at' => now()->subMinute(),
        ]);

        $result = AbsensiService::scan($pertemuan, $this->mahasiswaId, $pertemuan->qr_token, now());

        $this->assertFalse($result['ok']);
        $this->assertSame('Token QR kedaluwarsa.', $result['message']);
    }

    public function test_scan_di_luar_window_ditolak(): void
    {
        $pertemuan = Pertemuan::create([
            'kelas_id' => $this->kelasId,
            'ke' => 4,
            'tanggal' => now()->toDateString(),
            'topik' => 'Topik 4',
            'qr_token' => Str::random(32),
            'qr_expires_at' => now()->addHours(2),
            'window_mulai' => now()->addHour(),
            'window_selesai' => now()->addHours(2),
        ]);

        $result = AbsensiService::scan($pertemuan, $this->mahasiswaId, $pertemuan->qr_token, now());

        $this->assertFalse($result['ok']);
        $this->assertDatabaseCount('absensi', 0);
    }
}
