<?php

namespace Tests\Feature;

use App\Models\Beasiswa;
use App\Models\DendaRule;
use App\Models\Mahasiswa;
use App\Models\MahasiswaBeasiswa;
use App\Models\Pembayaran;
use App\Models\Prodi;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Models\UktTemplate;
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

    public function test_generate_memakai_template_ukt_dan_cicilan(): void
    {
        $prodi = Prodi::factory()->create();
        $tahun = TahunAkademik::factory()->create(['aktif' => true]);
        UktTemplate::create(['prodi_id' => $prodi->id, 'angkatan' => '2024', 'jalur' => 'SNBT', 'jenis' => 'UKT', 'nominal' => 6000000, 'aktif' => true]);
        Mahasiswa::factory()->create(['prodi_id' => $prodi->id, 'angkatan' => 2024, 'jalur' => 'SNBT']);

        $result = KeuanganService::generateMassal($tahun->id, 'UKT', 0, $prodi->id, '2024', 'SNBT', now()->addDays(30)->toDateString(), 3);

        $this->assertSame(1, $result['created']);
        $tagihan = Tagihan::first();
        $this->assertSame(6000000, (int) $tagihan->nominal);
        $this->assertCount(3, $tagihan->cicilan);
        $this->assertSame(6000000, $tagihan->cicilan->sum('nominal'));
    }

    public function test_beasiswa_otomatis_memotong_tagihan(): void
    {
        $prodi = Prodi::factory()->create();
        $tahun = TahunAkademik::factory()->create(['aktif' => true]);
        $mhs = Mahasiswa::factory()->create(['prodi_id' => $prodi->id, 'angkatan' => 2024]);
        $beasiswa = Beasiswa::create(['nama' => 'KIP', 'jenis' => 'persen', 'nilai' => 50, 'aktif' => true]);
        MahasiswaBeasiswa::create(['mahasiswa_id' => $mhs->id, 'beasiswa_id' => $beasiswa->id, 'tahun_akademik_id' => $tahun->id, 'status' => 'aktif']);

        KeuanganService::generateMassal($tahun->id, 'UKT', 4000000, $prodi->id, '2024');

        $tagihan = Tagihan::first();
        $this->assertSame(2000000, (int) $tagihan->diskon);
        $this->assertSame(2000000, (int) $tagihan->nominal);
    }

    public function test_denda_otomatis_dan_dashboard_tunggakan(): void
    {
        $prodi = Prodi::factory()->create();
        $tahun = TahunAkademik::factory()->create(['aktif' => true]);
        $mhs = Mahasiswa::factory()->create(['prodi_id' => $prodi->id, 'angkatan' => 2024]);
        DendaRule::create(['tahun_akademik_id' => $tahun->id, 'jenis' => 'UKT', 'terlambat_hari_min' => 1, 'mode' => 'persen', 'nilai' => 10, 'aktif' => true]);
        Tagihan::create([
            'mahasiswa_id' => $mhs->id, 'tahun_akademik_id' => $tahun->id,
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0,
            'status' => 'belum_bayar', 'jatuh_tempo' => now()->subDays(5)->toDateString(), 'denda' => 0,
        ]);

        $result = KeuanganService::applyDendaOtomatis($tahun->id);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(100000, (int) Tagihan::first()->denda);

        $summary = KeuanganService::tunggakanSummary($tahun->id);
        $this->assertSame(1, $summary['menunggak']);
        $this->assertSame(1100000, $summary['total_sisa']);
    }
}
