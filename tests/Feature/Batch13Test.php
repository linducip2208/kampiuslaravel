<?php

namespace Tests\Feature;

use App\Models\Coa;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\GlService;
use App\Services\KeuanganService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Batch 13 ide bank: piutang aging, laba-rugi, pengingat tracer via email.
 */
class Batch13Test extends TestCase
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

    private function struktur(): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTD']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'KD', 'slug' => 'kd']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Aging: bucket benar dan total per bucket = total piutang. */
    public function test_piutang_aging_bucket_dan_total(): void
    {
        [$prodi, $t] = $this->struktur();
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $this->user('mahasiswa')->id, 'nim' => '20249981',
            'prodi_id' => $prodi, 'angkatan' => 2024, 'status' => 'aktif',
        ]);

        Tagihan::create([
            'mahasiswa_id' => $mhsId, 'tahun_akademik_id' => $t, 'jenis' => 'UKT',
            'nominal' => 1000000, 'denda' => 0, 'terbayar' => 0, 'status' => 'belum_bayar',
            'jatuh_tempo' => now()->subDays(10)->toDateString(),
        ]);
        Tagihan::create([
            'mahasiswa_id' => $mhsId, 'tahun_akademik_id' => $t, 'jenis' => 'SPP',
            'nominal' => 500000, 'denda' => 0, 'terbayar' => 0, 'status' => 'belum_bayar',
            'jatuh_tempo' => now()->subDays(45)->toDateString(),
        ]);

        $laporan = KeuanganService::piutangAging($t);

        $this->assertSame(1000000, $laporan['total']['0-30']);
        $this->assertSame(500000, $laporan['total']['31-60']);
        $this->assertSame(1500000, array_sum($laporan['total']));
        $this->assertCount(2, $laporan['rows']);

        // Ekspor CSV untuk peran keuangan (stream: badan tak tertangkap di tes,
        // jadi verifikasi via header + isi lewat service di atas).
        $res = $this->actingAs($this->user('superadmin'))->get(route('keuangan.piutang-aging', ['tahun' => $t]))->assertOk();
        $this->assertStringContainsString('piutang-aging-', (string) $res->headers->get('content-disposition'));
    }

    /** Laba-rugi: pendapatan − beban = surplus. */
    public function test_laba_rugi_menghitung_surplus(): void
    {
        [, $t] = $this->struktur();
        $kas = Coa::create(['kode' => '1000', 'nama' => 'Kas', 'tipe' => 'aset', 'normal' => 'debet', 'aktif' => true]);
        $pend = Coa::create(['kode' => '4000', 'nama' => 'Pendapatan UKT', 'tipe' => 'pendapatan', 'normal' => 'kredit', 'aktif' => true]);
        $beban = Coa::create(['kode' => '5000', 'nama' => 'Beban Operasional', 'tipe' => 'beban', 'normal' => 'debet', 'aktif' => true]);

        GlService::posting($t, now()->toDateString(), 'Terima UKT', [
            ['coa_id' => $kas->id, 'debet' => 1000000, 'kredit' => 0],
            ['coa_id' => $pend->id, 'debet' => 0, 'kredit' => 1000000],
        ]);
        GlService::posting($t, now()->toDateString(), 'Bayar operasional', [
            ['coa_id' => $beban->id, 'debet' => 400000, 'kredit' => 0],
            ['coa_id' => $kas->id, 'debet' => 0, 'kredit' => 400000],
        ]);

        $lr = GlService::labaRugi($t);

        $this->assertSame(1000000, $lr['total_pendapatan']);
        $this->assertSame(400000, $lr['total_beban']);
        $this->assertSame(600000, $lr['surplus']);
    }

    /** Pengingat tracer: alumni lama tanpa isian terdaftar sebagai target (dry-run). */
    public function test_tracer_ingatkan_dry_run(): void
    {
        [$prodi] = $this->struktur();
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $this->user('mahasiswa')->id, 'nim' => '20209981',
            'prodi_id' => $prodi, 'angkatan' => 2020, 'status' => 'lulus',
        ]);
        DB::table('mahasiswa')->where('id', $mhsId)->update([
            'created_at' => now()->subMonths(8), 'updated_at' => now()->subMonths(7),
        ]);

        $this->artisan('tracer:ingatkan')
            ->expectsOutputToContain('20209981')
            ->assertExitCode(0);
    }
}
