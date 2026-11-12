<?php

namespace Tests\Feature;

use App\Models\Nilai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hitung_na_dan_huruf(): void
    {
        $na = Nilai::hitungNa(80, 70, 90, 100);
        $this->assertEquals(82.0, $na);
        $this->assertSame('B', Nilai::konversiHuruf($na));
        $this->assertSame(3.0, Nilai::bobotHuruf('B'));

        $this->assertSame('A', Nilai::konversiHuruf(85));
        $this->assertSame('B', Nilai::konversiHuruf(70));
        $this->assertSame('C', Nilai::konversiHuruf(55));
        $this->assertSame('D', Nilai::konversiHuruf(40));
        $this->assertSame('E', Nilai::konversiHuruf(39.99));
    }

    public function test_recalculate_menyimpan_na_huruf_bobot(): void
    {
        $nilai = new Nilai(['tugas' => 80, 'uts' => 70, 'uas' => 90, 'kehadiran' => 100]);
        $nilai->recalculate();

        $this->assertEquals(82.0, $nilai->na);
        $this->assertSame('B', $nilai->huruf);
        $this->assertEquals(3.0, $nilai->bobot);
    }
}
