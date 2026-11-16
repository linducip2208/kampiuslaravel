<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\Hutang;
use App\Models\Coa;
use App\Models\HutangDagang;
use App\Models\JurnalKeuangan;
use App\Models\Pemasok;
use App\Models\User;
use App\Services\GlService;
use App\Services\HutangService;
use App\Services\KeuanganService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 14 ide bank: saldo awal periode, arus kas (kas keluar dijurnalkan).
 */
class Batch14Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** Saldo awal diposting sekali sebagai jurnal; penolakan bila diulang. */
    public function test_saldo_awal_idempoten_dan_masuk_neraca(): void
    {
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);
        $kas = Coa::create(['kode' => '1000', 'nama' => 'Kas', 'tipe' => 'aset', 'normal' => 'debet', 'aktif' => true]);
        $eku = Coa::create(['kode' => '3000', 'nama' => 'Ekuitas', 'tipe' => 'ekuitas', 'normal' => 'kredit', 'aktif' => true]);

        GlService::postingSaldoAwal($t, [
            ['coa_id' => $kas->id, 'debet' => 5000000, 'kredit' => 0],
            ['coa_id' => $eku->id, 'debet' => 0, 'kredit' => 5000000],
        ]);

        $neraca = GlService::neracaSaldo($t);
        $this->assertTrue($neraca['seimbang']);
        $this->assertSame(5000000, $neraca['total_debet']);

        $this->expectException(\RuntimeException::class);
        GlService::postingSaldoAwal($t, [
            ['coa_id' => $kas->id, 'debet' => 1, 'kredit' => 0],
            ['coa_id' => $eku->id, 'debet' => 0, 'kredit' => 1],
        ]);
    }

    /** Bayar hutang menjurnalkan kas keluar; arus kas = masuk − keluar. */
    public function test_arus_kas_masuk_vs_keluar(): void
    {
        JurnalKeuangan::create([
            'tanggal' => today()->toDateString(), 'jenis' => 'penerimaan',
            'akun_debet' => 'Kas/Bank', 'akun_kredit' => 'Piutang UKT',
            'nominal' => 2000000, 'keterangan' => 'Tes masuk',
        ]);

        $pemasok = Pemasok::create(['nama' => 'PT Tes']);
        $hutang = HutangDagang::create([
            'pemasok_id' => $pemasok->id, 'nomor' => 'H-001',
            'uraian' => 'Belanja ATK', 'nominal' => 800000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        $hasil = HutangService::bayar($hutang->id, 800000, $this->user('superadmin')->id);
        $this->assertSame(800000, $hasil['dibayar']);

        $arus = KeuanganService::arusKas();
        $this->assertSame(2000000, $arus['masuk']);
        $this->assertSame(800000, $arus['keluar']);
        $this->assertSame(1200000, $arus['bersih']);

        // Halaman hutang menampilkan ringkas arus kas.
        $html = Livewire::actingAs($this->user('superadmin'))->test(Hutang::class)->html();
        $this->assertStringContainsString('Arus Kas', $html);
        $this->assertStringContainsString('1.200.000', $html);
    }

    /** Enum jenis jurnal kini mencakup pengeluaran (skema). */
    public function test_jenis_pengeluaran_tersimpan(): void
    {
        $j = JurnalKeuangan::create([
            'tanggal' => today()->toDateString(), 'jenis' => 'pengeluaran',
            'akun_debet' => 'Utang Usaha', 'akun_kredit' => 'Kas/Bank',
            'nominal' => 100000, 'keterangan' => 'Tes skema',
        ]);

        $this->assertSame('pengeluaran', $j->fresh()->jenis);
    }
}
