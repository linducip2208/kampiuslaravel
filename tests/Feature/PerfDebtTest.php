<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\PmbPendaftar;
use App\Models\User;
use App\Services\PmbVaService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Utang teknis P5 (jadwal unbounded) + P8 (va_reference belum unik).
 * P6 (ringkasan full-collection) dan P7 (filter PHP per-tagihan) diterima
 * apa adanya: P6 dibutuhkan untuk hitung IPK (display sudah paginasi M-024),
 * P7 terbatasi per tagihan (sedikit baris pending).
 */
class PerfDebtTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function hariIni(): string
    {
        return match (Carbon::now('Asia/Jakarta')->dayOfWeekIso) {
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', default => 'Minggu',
        };
    }

    private function kelasLengkap(int $prodi, int $tahun, string $kode, ?int $dosenId = null): int
    {
        $mk = DB::table('matakuliah')->insertGetId([
            'prodi_id' => $prodi, 'kode' => $kode, 'nama' => 'MK '.$kode, 'sks' => 3, 'semester' => 1,
        ]);

        return DB::table('kelas')->insertGetId([
            'matakuliah_id' => $mk, 'tahun_akademik_id' => $tahun, 'nama' => 'A',
            'kuota' => 40, 'dosen_id' => $dosenId, 'hari' => $this->hariIni(),
            'jam_mulai' => '07:00:00', 'jam_selesai' => '09:30:00',
        ]);
    }

    /** P8: va_reference unik — duplikat ditolak DB, settle tetap jalan. */
    public function test_va_reference_unik(): void
    {
        PmbPendaftar::create([
            'no_daftar' => 'PMB-P8-1', 'nama' => 'A', 'email' => 'a@t.id', 'hp' => '081',
            'va_reference' => 'PMBVA-9-UNIK', 'bayar_status' => 'belum_bayar',
        ]);

        try {
            PmbPendaftar::create([
                'no_daftar' => 'PMB-P8-2', 'nama' => 'B', 'email' => 'b@t.id', 'hp' => '082',
                'va_reference' => 'PMBVA-9-UNIK', 'bayar_status' => 'belum_bayar',
            ]);
            $this->fail('va_reference ganda harus ditolak unique constraint.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->assertTrue(app(PmbVaService::class)->settleByReference('PMBVA-9-UNIK'));
    }

    /** P5: dosen hanya melihat kelasnya sendiri (bukan semua kelas hari ini). */
    public function test_jadwal_dosen_tercakup(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'I', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'if']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);

        $uA = User::factory()->create();
        $uA->assignRole('dosen');
        $uB = User::factory()->create();
        $uB->assignRole('dosen');
        $dA = Dosen::factory()->create(['user_id' => $uA->id]);
        $dB = Dosen::factory()->create(['user_id' => $uB->id]);

        $this->kelasLengkap($prodi, $t, 'D-A1', $dA->id);
        $this->kelasLengkap($prodi, $t, 'D-A2', $dA->id);
        $this->kelasLengkap($prodi, $t, 'D-B1', $dB->id);

        Sanctum::actingAs($uA, ['mobile']);
        $res = $this->getJson('/api/v1/jadwal-hari-ini')->assertOk();
        $this->assertCount(2, $res->json('data'));

        Sanctum::actingAs($uB, ['mobile']);
        $this->getJson('/api/v1/jadwal-hari-ini')->assertOk()->assertJsonCount(1, 'data');
    }

    /** P5: non-dosen non-mahasiswa dibatasi 200 baris, bentuk tetap array. */
    public function test_jadwal_admin_dibatasi_tanpa_ubah_bentuk(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT2']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'I', 'jenjang' => 'S1', 'kode' => 'IF2', 'slug' => 'if2']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);

        for ($i = 1; $i <= 205; $i++) {
            $this->kelasLengkap($prodi, $t, 'AD-'.$i);
        }

        $adm = User::factory()->create();
        $adm->assignRole('superadmin');
        Sanctum::actingAs($adm, ['mobile']);

        $res = $this->getJson('/api/v1/jadwal-hari-ini')->assertOk();
        $data = $res->json('data');
        $this->assertIsArray($data);
        $this->assertCount(200, $data);
        $this->assertArrayNotHasKey('meta', $res->json());
    }
}
