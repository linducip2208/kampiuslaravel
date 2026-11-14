<?php

namespace Tests\Feature;

use App\Livewire\Admin\Akreditasi;
use App\Models\AiKuota;
use App\Models\AkreditasiDokumen;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizSoal;
use App\Models\User;
use App\Services\AiKuotaService;
use App\Services\QuizService;
use Database\Seeders\RolesPermissionsSeeder;
use DomainException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave C Batch 1: M-050 (akreditasi), M-051 (CBT), M-058 (kuota AI), M-060 (PWA).
 */
class WaveCBatch1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function struktur(): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fak, 'nama' => 'Informatika', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'informatika',
        ]);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'CBT-1', 'nama' => 'CBT MK', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        return [$prodi, $t, $mk, $kelas];
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** M-051: token CBT wajib; urutan acak dikunci per attempt (recovery konsisten). */
    public function test_cbt_token_dan_urutan_acak(): void
    {
        [$prodi, , , $kelas] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20261001', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        $quiz = Quiz::create(['kelas_id' => $kelas, 'judul' => 'CBT UTS', 'mode' => 'cbt', 'token' => 'ABC123', 'acak_soal' => true, 'durasi_menit' => 60]);
        foreach (['S1', 'S2', 'S3', 'S4', 'S5', 'S6'] as $i => $kode) {
            QuizSoal::create(['quiz_id' => $quiz->id, 'pertanyaan' => $kode, 'tipe' => 'pg', 'bobot' => 10, 'kunci_jawaban' => 'A']);
        }

        try {
            QuizService::mulai($quiz->id, $mhs);
            $this->fail('Token salah harus ditolak.');
        } catch (DomainException $e) {
            $this->assertSame('Token CBT salah.', $e->getMessage());
        }

        try {
            QuizService::mulai($quiz->id, $mhs, 'SALAH');
            $this->fail('Token salah harus ditolak.');
        } catch (DomainException) {
        }

        $a1 = QuizService::mulai($quiz->id, $mhs, 'ABC123');
        $this->assertCount(6, $a1->urutan_soal);
        $this->assertEqualsCanonicalizing(
            QuizSoal::where('quiz_id', $quiz->id)->pluck('id')->all(),
            $a1->urutan_soal
        );

        // Recovery: mulai lagi → attempt sama + urutan sama.
        $a2 = QuizService::mulai($quiz->id, $mhs, 'ABC123');
        $this->assertSame($a1->id, $a2->id);
        $this->assertSame($a1->urutan_soal, $a2->urutan_soal);
    }

    /** M-051: seal otomatis attempt kedaluwarsa + terjadwal. */
    public function test_seal_kedaluwarsa_dan_terjadwal(): void
    {
        [$prodi, , , $kelas] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20261002', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        $quiz = Quiz::create(['kelas_id' => $kelas, 'judul' => 'CBT Seal', 'mode' => 'cbt', 'durasi_menit' => 1]);
        $soal = QuizSoal::create(['quiz_id' => $quiz->id, 'pertanyaan' => 'Q', 'tipe' => 'pg', 'bobot' => 10, 'kunci_jawaban' => 'A']);
        $attempt = QuizService::mulai($quiz->id, $mhs);
        QuizService::simpanJawaban($attempt->id, $mhs, $soal->id, 'A');
        $attempt->update(['mulai_at' => now()->subHours(2)]);

        $this->artisan('kuis:seal-kedaluwarsa')->assertSuccessful();

        $fresh = QuizAttempt::find($attempt->id);
        $this->assertSame(QuizAttempt::STATUS_SELESAI, $fresh->status);
        $this->assertSame(10.0, (float) $fresh->total_skor);

        $events = collect(app(Schedule::class)->events())
            ->map(fn ($e) => $e->command ?? '')->filter(fn ($c) => str_contains((string) $c, 'kuis:seal-kedaluwarsa'));
        $this->assertNotEmpty($events);
    }

    /** M-050: dokumen akreditasi + verifikasi; mahasiswa ditolak. */
    public function test_akreditasi_simpan_dan_verifikasi(): void
    {
        [$prodi] = $this->struktur();
        $adm = $this->user('admin_prodi');
        $mhs = $this->user('mahasiswa');

        $this->actingAs($mhs)->get(route('admin.akreditasi'))->assertForbidden();
        $this->actingAs($adm)->get(route('admin.akreditasi'))->assertOk();

        Livewire::actingAs($adm)->test(Akreditasi::class)
            ->set('prodi_id', $prodi)
            ->set('kategori', 'borang-3a')
            ->set('judul', 'Rasio dosen:mahasiswa')
            ->call('simpan')
            ->assertHasNoErrors();

        $dok = AkreditasiDokumen::where('judul', 'Rasio dosen:mahasiswa')->firstOrFail();

        Livewire::actingAs($adm)->test(Akreditasi::class)->call('verifikasi', $dok->id)->assertHasNoErrors();
        $this->assertSame('terverifikasi', $dok->fresh()->status);
        $this->assertNotNull($dok->fresh()->diverifikasi_at);
    }

    /** M-058: kuota harian + estimasi biaya; chat 429 bila habis. */
    public function test_kuota_ai_dan_batas_chat(): void
    {
        $this->assertTrue(Schema::hasTable('ai_kuota'));

        $user = $this->user('mahasiswa');
        Sanctum::actingAs($user, ['mobile']);

        config(['ai.batas_harian' => 1, 'ai.tarif' => ['default' => 150, 'chat' => 150]]);

        $this->assertTrue(AiKuotaService::boleh($user->id, 'chat'));
        $this->postJson('/api/v1/ai/chat', ['pesan' => 'Halo, jadwal UTS kapan?'])->assertOk();
        $this->assertFalse(AiKuotaService::boleh($user->id, 'chat'));
        $this->postJson('/api/v1/ai/chat', ['pesan' => 'Satu lagi'])->assertStatus(429);

        $row = AiKuota::where('user_id', $user->id)->where('jenis', 'chat')->firstOrFail();
        $this->assertSame(1, (int) $row->jumlah);
        $this->assertSame(150, (int) $row->estimasi_biaya);
    }

    /** M-060: manifest valid + sw terdaftar + halaman offline. */
    public function test_pwa_manifest_dan_offline(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('KAMPUS — Portal Mahasiswa', $manifest['name']);
        $this->assertNotEmpty($manifest['icons']);
        $this->assertSame('#1769F5', $manifest['theme_color']);

        $sw = (string) file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('kampus-shell-v1', $sw);
        $this->assertStringContainsString('/offline', $sw);

        $layout = (string) file_get_contents(resource_path('views/layouts/tabler.blade.php'));
        $this->assertStringContainsString('manifest.webmanifest', $layout);
        $this->assertStringContainsString('theme-color', $layout);
        $this->assertStringContainsString('/sw.js', $layout);

        $this->get(route('publik.offline'))->assertOk()->assertSee('luring');
    }
}
