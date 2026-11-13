<?php

namespace Tests\Feature;

use App\Models\DendaRule;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DendaOtomatisTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_menerapkan_denda_dan_mencatat_audit(): void
    {
        $tahun = TahunAkademik::factory()->create();
        DendaRule::create([
            'tahun_akademik_id' => null,
            'jenis' => 'UKT',
            'terlambat_hari_min' => 1,
            'mode' => 'nominal',
            'nilai' => 50000,
            'aktif' => true,
        ]);
        $mhs = Mahasiswa::factory()->create();
        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs->id,
            'tahun_akademik_id' => $tahun->id,
            'jenis' => 'UKT',
            'nominal' => 1000000,
            'terbayar' => 0,
            'status' => 'belum_bayar',
            'jatuh_tempo' => today()->subDays(5)->toDateString(),
        ]);

        $this->artisan('denda:terapkan')->assertSuccessful();

        $this->assertSame(50000, (int) $tagihan->fresh()->denda);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tagihan.terapkan_denda_otomatis']);

        // Idempoten: jalan kedua tidak menambah denda.
        $this->artisan('denda:terapkan')->assertSuccessful();
        $this->assertSame(50000, (int) $tagihan->fresh()->denda);
    }
}
