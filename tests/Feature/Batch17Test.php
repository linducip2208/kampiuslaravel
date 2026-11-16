<?php

namespace Tests\Feature;

use App\Models\Nilai;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\WaliMahasiswa;
use App\Services\Ai\RagService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Batch 17 ide bank: evaluasi RAG + batas konteks, API orangtua read-only.
 */
class Batch17Test extends TestCase
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

    /** Evaluasi RAG: pasangan relevan berskor > pasangan tak relevan. */
    public function test_rag_evaluasi_membedakan_relevansi(): void
    {
        RagService::ingest(
            'Kalender Akademik',
            'Pengisian KRS dibuka setiap awal semester. Mahasiswa wajib mengisi KRS sebelum kuliah dimulai.'
        );

        $hasil = RagService::evaluasi([
            ['tanya' => 'kapan pengisian KRS dibuka', 'harap' => 'pengisian KRS dibuka setiap awal semester'],
            ['tanya' => 'kapan pengisian KRS dibuka', 'harap' => 'jadwal penerbangan pesawat terbang malam hari'],
        ]);

        $this->assertGreaterThan($hasil['detail'][1]['skor'], $hasil['detail'][0]['skor']);
        $this->assertGreaterThan(0, $hasil['detail'][0]['skor']);
        $this->assertSame(0.0, $hasil['detail'][1]['skor']);

        // Command evaluasi membaca file emas + mencetak rata-rata.
        $file = tempnam(sys_get_temp_dir(), 'emas').'.json';
        file_put_contents($file, (string) json_encode([
            ['tanya' => 'kapan pengisian KRS dibuka', 'harap' => 'pengisian KRS dibuka setiap awal semester'],
        ]));

        try {
            $this->artisan('rag:evaluasi', ['--file' => $file])
                ->expectsOutputToContain('Rata-rata:')
                ->assertExitCode(0);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /** Jawaban RAG tetap kembali untuk dokumen sangat panjang (batas konteks). */
    public function test_rag_jawab_dokumen_panjang_tetap_kembali(): void
    {
        RagService::ingest('Dokumen Panjang', str_repeat('Kalimat akademik tentang kurikulum kampus. ', 500));

        $hasil = RagService::jawab('kurikulum kampus', null);

        $this->assertFalse($hasil['ditolak']);
        $this->assertContains('Dokumen Panjang', $hasil['sumber']);
    }

    /** Wali: daftar anak + nilai terkunci + tagihan; non-anak ditolak. */
    public function test_wali_api_hanya_data_anak_tertaut(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTH']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PH', 'slug' => 'ph']);

        $wali = User::factory()->create();
        $anakUser = User::factory()->create();
        $anakId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $anakUser->id, 'nim' => '20249991', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);

        // Tanpa tautan → daftar kosong + akses anak ditolak.
        $this->getJson('/api/v1/wali/anak', $this->token($wali))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/wali/anak/{$anakId}/nilai", $this->token($wali))->assertForbidden();

        $this->artisan('wali:tautkan', ['user' => (string) $wali->id, 'mahasiswa' => (string) $anakId])->assertExitCode(0);

        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'WL-1', 'nama' => 'WL', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        Nilai::create(['mahasiswa_id' => $anakId, 'kelas_id' => $kelas, 'na' => 85, 'huruf' => 'A', 'bobot' => 4, 'is_locked' => true]);
        Tagihan::create([
            'mahasiswa_id' => $anakId, 'tahun_akademik_id' => $t,
            'jenis' => 'UKT', 'nominal' => 1000000, 'denda' => 0, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        $this->getJson('/api/v1/wali/anak', $this->token($wali))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/wali/anak/{$anakId}/nilai", $this->token($wali))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/wali/anak/{$anakId}/tagihan", $this->token($wali))->assertOk()->assertJsonPath('ringkasan.total_tagihan', 1000000);

        // Anak orang lain tetap ditolak.
        $lainId = DB::table('mahasiswa')->insertGetId([
            'user_id' => User::factory()->create()->id, 'nim' => '20249992', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);
        $this->getJson("/api/v1/wali/anak/{$lainId}/nilai", $this->token($wali))->assertForbidden();

        $this->assertSame(1, WaliMahasiswa::count());
    }
}
