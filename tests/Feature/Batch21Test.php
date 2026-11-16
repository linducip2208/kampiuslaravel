<?php

namespace Tests\Feature;

use App\Models\Pengumuman;
use App\Models\Tagihan;
use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Batch 21 ide bank: kontrak paging API v1 (q/sort/per_page + envelope meta).
 */
class Batch21Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function token(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('mobile', ['mobile'])->plainTextToken];
    }

    /** Tugas: q menyaring, sort mengurut, meta konsisten. */
    public function test_tugas_mendukung_q_sort_meta(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('superadmin');
        Tugas::create(['judul' => 'Alpha Beta', 'deskripsi' => 'satu']);
        Tugas::create(['judul' => 'Gamma Delta', 'deskripsi' => 'dua']);

        $this->getJson('/api/v1/tugas?q='.urlencode('Alpha'), $this->token($adm))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        $res = $this->getJson('/api/v1/tugas?sort=-id&per_page=1', $this->token($adm))->assertOk();
        $res->assertJsonCount(1, 'data');
        $this->assertSame(2, $res->json('meta.total'));
        $this->assertSame(2, $res->json('meta.last_page'));
        $ids = [$this->getJson('/api/v1/tugas?sort=id&per_page=2', $this->token($adm))->json('data.0.id')];
        $this->assertSame(Tugas::orderBy('id')->value('id'), $ids[0]);
    }

    /** Tagihan: filter jenis + meta + ringkasan penuh. */
    public function test_tagihan_mendukung_q_meta_ringkasan(): void
    {
        [$prodi, $t] = $this->struktur();
        $u = User::factory()->create();
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '202499Q1', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);

        foreach (['UKT' => 1000000, 'SPP' => 500000] as $jenis => $nominal) {
            Tagihan::create([
                'mahasiswa_id' => $mhsId, 'tahun_akademik_id' => $t, 'jenis' => $jenis,
                'nominal' => $nominal, 'denda' => 0, 'terbayar' => 0, 'status' => 'belum_bayar',
            ]);
        }

        $token = $this->token($u);

        $this->getJson('/api/v1/tagihan?q=UKT', ['Authorization' => $token['Authorization']])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            // Ringkasan tetap dari keseluruhan filter (bukan halaman).
            ->assertJsonPath('ringkasan.total_tagihan', 1000000);
    }

    /** Pengumuman: q + sort + meta per_page. */
    public function test_pengumuman_mendukung_q_sort_meta(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('superadmin');
        Pengumuman::create(['judul' => 'Libur Nasional', 'isi' => 'Kampus tutup', 'target' => 'semua', 'published_at' => now()->subDay()]);
        Pengumuman::create(['judul' => 'Jadwal UAS', 'isi' => 'Ujian akhir', 'target' => 'semua', 'published_at' => now()]);

        $this->getJson('/api/v1/pengumuman?q=UAS', $this->token($adm))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 15);

        $res = $this->getJson('/api/v1/pengumuman?sort=id', $this->token($adm))->assertOk();
        $this->assertSame('Libur Nasional', $res->json('data.0.judul'));
    }

    private function struktur(): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTQ']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PQ', 'slug' => 'pq']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }
}
