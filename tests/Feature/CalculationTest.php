<?php

namespace Tests\Feature;

use App\Models\Krs;
use App\Models\Nilai;
use App\Models\Tagihan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalculationTest extends TestCase
{
    #[Test]
    public function nilai_dihitung_dengan_bobot_default(): void
    {
        // 30/30/30/10: 80*0.3+70*0.3+90*0.3+100*0.1 = 82
        $this->assertSame(82.0, Nilai::hitungNa(80, 70, 90, 100));
        $this->assertSame('B', Nilai::konversiHuruf(82));
        $this->assertSame('A', Nilai::konversiHuruf(85));
        $this->assertSame('E', Nilai::konversiHuruf(10));
    }

    #[Test]
    public function batas_sks_mengikuti_ipk(): void
    {
        $this->assertSame(24, Krs::sksMaksByIpk(3.5));
        $this->assertSame(22, Krs::sksMaksByIpk(2.6));
        $this->assertSame(20, Krs::sksMaksByIpk(2.1));
        $this->assertSame(18, Krs::sksMaksByIpk(1.5));
    }

    #[Test]
    public function tagihan_sisa_tidak_negatif(): void
    {
        $tagihan = new Tagihan(['nominal' => 1000000, 'denda' => 50000, 'terbayar' => 200000]);
        $this->assertSame(850000, $tagihan->sisa());
        $this->assertSame(1050000, $tagihan->totalTagihan());

        $lunas = new Tagihan(['nominal' => 1000000, 'denda' => 0, 'terbayar' => 1500000]);
        $this->assertSame(0, $lunas->sisa());
    }

    #[Test]
    public function remedial_hanya_dan_e_dan_maks_c(): void
    {
        $nilai = new Nilai(['tugas' => 50, 'uts' => 50, 'uas' => 30, 'kehadiran' => 100, 'huruf' => 'E']);
        $this->assertTrue($nilai->terapkanRemedial(90));
        $this->assertLessThanOrEqual(55.0, (float) $nilai->na);

        $bagus = new Nilai(['tugas' => 90, 'uts' => 90, 'uas' => 90, 'kehadiran' => 100, 'huruf' => 'A']);
        $this->assertFalse($bagus->terapkanRemedial(100));
    }
}
