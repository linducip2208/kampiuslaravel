<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Prodi;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Services\KeuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeuanganTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_massal_per_prodi_angkatan(): void
    {
        $prodiA = Prodi::factory()->create();
        $prodiB = Prodi::factory()->create();
        $tahun = TahunAkademik::factory()->create(['aktif' => true]);

        Mahasiswa::factory()->count(2)->create(['prodi_id' => $prodiA->id, 'angkatan' => 2024]);
        Mahasiswa::factory()->create(['prodi_id' => $prodiB->id, 'angkatan' => 2024]);

        $result = KeuanganService::generateMassal($tahun->id, 'UKT', 5000000, $prodiA->id, '2024');

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(2, Tagihan::count());

        $repeat = KeuanganService::generateMassal($tahun->id, 'UKT', 5000000, $prodiA->id, '2024');
        $this->assertSame(0, $repeat['created']);
        $this->assertSame(2, $repeat['skipped']);
    }

    public function test_block_krs_dan_verifikasi_membuka_blokir(): void
    {
        $mhs = Mahasiswa::factory()->create();
        $tahun = TahunAkademik::factory()->create(['aktif' => true]);
        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs->id, 'tahun_akademik_id' => $tahun->id,
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        $this->assertTrue(KeuanganService::isBlockedForKrs($mhs->id));
        $this->assertTrue(KeuanganService::hasTunggakan($mhs->id));

        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'transfer', 'nominal' => 1000000, 'status' => 'pending',
        ]);
        KeuanganService::verifyPembayaran($bayar->id, $mhs->user_id, true);

        $this->assertSame('lunas', $tagihan->fresh()->status);
        $this->assertFalse(KeuanganService::isBlockedForKrs($mhs->id));
    }
}
