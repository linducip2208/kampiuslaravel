<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AkademikService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Budget query anti-N+1 pada volume kecil (50-60 baris).
 * BUKAN klaim p95 produksi — langkah pertama §C MEGA_PERFORMANCE_REPORT:
 * gagal bila ada yang mengubah eager-loading/batch menjadi per-baris.
 */
class PerformanceBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function hitung(callable $aksi): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $aksi();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    private static int $periodeSeq = 0;

    private function mahasiswaDenganNilai(int $jumlahNilai, string $nim = '20209991', string $prefixKode = 'PB-'): array
    {
        $u = uniqid();
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT'.$u]);
        $prodi = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fak, 'nama' => 'I', 'jenjang' => 'S1', 'kode' => 'IF'.$u, 'slug' => 'if-'.$u,
        ]);
        self::$periodeSeq++;
        $semester = self::$periodeSeq % 2 === 0 ? 'genap' : 'ganjil';
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => $semester, 'aktif' => $semester === 'ganjil']);

        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        for ($i = 1; $i <= $jumlahNilai; $i++) {
            $mk = DB::table('matakuliah')->insertGetId([
                'prodi_id' => $prodi, 'kode' => $prefixKode.$i, 'nama' => 'MK '.$i, 'sks' => 3, 'semester' => 1,
            ]);
            $kelas = DB::table('kelas')->insertGetId([
                'matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 200,
            ]);
            DB::table('nilai')->insert([
                'mahasiswa_id' => $mhs, 'kelas_id' => $kelas, 'bobot' => 3.0, 'is_locked' => true,
            ]);
        }

        return [$user, $mhs];
    }

    /** KHS: jumlah query TETAP saat baris naik 5 → 60 (tanpa N+1) + plafon. */
    public function test_khs_tetap_datar_pada_60_nilai(): void
    {
        [$user, $mhs] = $this->mahasiswaDenganNilai(5, '20209991', 'PA-');
        $kecil = $this->hitung(fn () => $this->actingAs($user)->get(route('khs.show', $mhs))->assertOk());

        [$user2, $mhs2] = $this->mahasiswaDenganNilai(60, '20209992', 'PB-');
        $besar = $this->hitung(fn () => $this->actingAs($user2)->get(route('khs.show', $mhs2))->assertOk());

        $this->assertLessThanOrEqual($kecil, $besar, "Query tumbuh {$kecil} → {$besar} saat nilai 5 → 60 (N+1?).");
        $this->assertLessThanOrEqual(25, $besar, "KHS mengeksekusi {$besar} query (plafon 25).");
    }

    /** Transkrip: jumlah query TETAP saat baris naik 5 → 60 + plafon. */
    public function test_transkrip_tetap_datar_pada_60_nilai(): void
    {
        [$user, $mhs] = $this->mahasiswaDenganNilai(5, '20209993', 'PC-');
        $kecil = $this->hitung(fn () => $this->actingAs($user)->get(route('transkrip.cetak', $mhs))->assertOk());

        [$user2, $mhs2] = $this->mahasiswaDenganNilai(60, '20209994', 'PD-');
        $besar = $this->hitung(fn () => $this->actingAs($user2)->get(route('transkrip.cetak', $mhs2))->assertOk());

        $this->assertLessThanOrEqual($kecil, $besar, "Query tumbuh {$kecil} → {$besar} saat nilai 5 → 60 (N+1?).");
        $this->assertLessThanOrEqual(25, $besar, "Transkrip mengeksekusi {$besar} query (plafon 25).");
    }

    /** validasiKrs 10 kelas: tetap ~8 query (bukan 2N). */
    public function test_validasi_krs_tetap_datar_pada_10_kelas(): void
    {
        [$user, $mhs] = $this->mahasiswaDenganNilai(0);

        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T2', 'kode' => 'FT2']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'J', 'jenjang' => 'S1', 'kode' => 'JJ', 'slug' => 'jj']);
        $t = DB::table('tahun_akademik')->first()->id;
        $kelasIds = [];

        for ($i = 1; $i <= 10; $i++) {
            $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'VK-'.$i, 'nama' => 'V'.$i, 'sks' => 2, 'semester' => 1]);
            $kelasIds[] = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 200]);
        }

        $jumlah = $this->hitung(fn () => AkademikService::validasiKrs($mhs, $kelasIds, 3.5));

        $this->assertLessThanOrEqual(12, $jumlah, "validasiKrs mengeksekusi {$jumlah} query untuk 10 kelas (batas 12).");
    }
}
