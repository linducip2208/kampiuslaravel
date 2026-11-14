<?php

namespace Tests\Feature;

use App\Livewire\Beasiswa\Kelola;
use App\Models\AuditLog;
use App\Models\Beasiswa;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MahasiswaBeasiswa;
use App\Models\PmbPendaftar;
use App\Models\Setting;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\AkademikService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave A batch 2 — sisa 7 item: M-005, M-006, M-010, M-016, M-017, M-018, M-020.
 */
class WaveABatch2Test extends TestCase
{
    use RefreshDatabase;

    private function struktur(): array
    {
        $this->seed(RolesPermissionsSeeder::class);
        $t1 = DB::table('tahun_akademik')->insertGetId(['tahun' => '2025', 'semester' => 'ganjil', 'aktif' => false]);
        $t2 = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'genap', 'aktif' => true]);
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'Informatika', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'informatika']);

        return [$t1, $t2, $prodi];
    }

    private function userDenganRole(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** M-005: act liar di push PDDikti → 422; act allowlist lolos validasi. */
    public function test_pddikti_push_act_liar_ditolak(): void
    {
        $this->struktur();
        $pimpinan = $this->userDenganRole('pimpinan');
        Http::fake(['*' => Http::response(['data' => ['token' => 'tok']])]);

        $this->actingAs($pimpinan)->postJson(route('pddikti.push'), ['act' => 'DropTable', 'record' => ['a' => 1]])
            ->assertStatus(422);

        $this->actingAs($pimpinan)->postJson(route('pddikti.push'), ['act' => 'InsertMhs', 'record' => ['nim' => '1']])
            ->assertOk();
    }

    /** M-006: tiap export PDDikti menulis 1 baris AuditLog milik user. */
    public function test_pddikti_export_menulis_audit_log(): void
    {
        $this->struktur();
        $pimpinan = $this->userDenganRole('pimpinan');

        $this->actingAs($pimpinan)->get(route('pddikti.export', 'mahasiswa'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $pimpinan->id,
            'action' => 'pddikti.export',
            'model' => 'pddikti',
        ]);
    }

    /** M-010: ringkasan/KHS hanya menghitung nilai terkunci. */
    public function test_ringkasan_hanya_nilai_terkunci(): void
    {
        [$t1, $t2, $prodi] = $this->struktur();
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId(['user_id' => $user->id, 'nim' => '20260101', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'L1', 'nama' => 'MK L1', 'sks' => 3, 'semester' => 1]);
        $kLocked = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t2, 'nama' => 'A', 'kuota' => 40]);
        $mk2 = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'L2', 'nama' => 'MK L2', 'sks' => 3, 'semester' => 1]);
        $kDraft = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk2, 'tahun_akademik_id' => $t2, 'nama' => 'A', 'kuota' => 40]);

        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $kLocked, 'bobot' => 4.0, 'is_locked' => true]);
        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $kDraft, 'bobot' => 1.0, 'is_locked' => false]);

        $ringkasan = AkademikService::ringkasan($mhs);
        $this->assertEquals(4.0, $ringkasan['ipk']);
        $this->assertSame(3, $ringkasan['total_sks']);
        $this->assertSame(1, $ringkasan['nilai']->count());

        $data = $this->actingAs($user)->get(route('khs.show', $mhs))->assertOk()->viewData();
        $this->assertEquals(4.0, $data['ipk']);
    }

    /** M-016: pola permission Spatie — pemegang laporan.view boleh, mahasiswa ditolak. */
    public function test_audit_viewer_pakai_permission(): void
    {
        $this->struktur();
        $pimpinan = $this->userDenganRole('pimpinan');
        $mhs = $this->userDenganRole('mahasiswa');

        $this->actingAs($pimpinan)->get(route('admin.audit'))->assertOk();
        $this->actingAs($mhs)->get(route('admin.audit'))->assertForbidden();
    }

    /** M-017: replay webhook v2 (signature sama) → 200 tanpa efek ganda. */
    public function test_webhook_v2_replay_idempoten(): void
    {
        $this->struktur();
        Setting::set('webhook.secret', 'rahasia-min-16-karakter');
        $body = json_encode(['event' => 'krs.disetujui', 'payload' => ['id' => 1]]);
        $sig = hash_hmac('sha256', $body, 'rahasia-min-16-karakter');

        $r1 = $this->call('POST', '/api/v2/webhook/terima', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Webhook-Signature' => $sig,
        ], $body);
        $r1->assertOk();

        $r2 = $this->call('POST', '/api/v2/webhook/terima', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Webhook-Signature' => $sig,
        ], $body);
        $r2->assertOk()->assertJson(['deduped' => true]);

        $this->assertSame(1, WebhookEvent::where('sumber', 'v2')->count());
    }

    /** M-017b: callback payment dengan Idempotency-Key sama → 200 tanpa efek ganda. */
    public function test_payment_callback_idempotency_key(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        Setting::set('pay.driver', 'simulated');
        $pendaftar = PmbPendaftar::create([
            'no_daftar' => 'PMB-WAVE-B2-1', 'nama' => 'Tes', 'email' => 'b2@t.id', 'hp' => '081',
            'va_reference' => 'PMBVA-9-B2IDEM', 'bayar_status' => 'belum_bayar',
        ]);

        $r1 = $this->postJson(route('payment.callback'), ['reference' => 'PMBVA-9-B2IDEM'], ['Idempotency-Key' => 'kunci-unik-b2']);
        $r1->assertOk();
        $this->assertSame('lunas', $pendaftar->fresh()->bayar_status);

        $r2 = $this->postJson(route('payment.callback'), ['reference' => 'PMBVA-9-B2IDEM'], ['Idempotency-Key' => 'kunci-unik-b2']);
        $r2->assertOk()->assertJson(['deduped' => true]);
        $this->assertSame(1, WebhookEvent::where('sumber', 'payment')->count());
    }

    /** M-018: kuota beasiswa habis → assign ditolak. */
    public function test_kuota_beasiswa_habis_ditolak(): void
    {
        $this->struktur();
        $keu = $this->userDenganRole('keuangan');
        $m1 = Mahasiswa::factory()->create();
        $m2 = Mahasiswa::factory()->create();
        $b = Beasiswa::create(['nama' => 'Beasiswa Kuota 1', 'jenis' => 'persen', 'nilai' => 50, 'kuota' => 1, 'aktif' => true]);
        MahasiswaBeasiswa::create(['mahasiswa_id' => $m1->id, 'beasiswa_id' => $b->id, 'tahun_akademik_id' => null, 'status' => 'aktif']);

        Livewire::actingAs($keu)->test(Kelola::class)
            ->set('assignBeasiswaId', $b->id)
            ->set('assignNim', $m2->nim)
            ->call('assign')
            ->assertHasErrors(['assignBeasiswaId']);

        $this->assertSame(1, MahasiswaBeasiswa::where('beasiswa_id', $b->id)->count());
    }

    /** M-020: upload traversal per modul — nama jahat tidak lolos validasi / tidak menulis di luar disk. */
    public function test_upload_traversal_dinetralkan(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('public');
        $mhs = Mahasiswa::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs->update(['user_id' => $user->id]);
        $tugas = Tugas::create(['kelas_id' => Kelas::factory()->create()->id, 'judul' => 'T1', 'deskripsi' => 'd']);
        Sanctum::actingAs($user, ['mobile']);

        // Nama file traversal + executable: wajib ditolak validasi mimes.
        $jahat = UploadedFile::fake()->createWithContent('../evil.php', '<?php evil();');
        $this->postJson("/api/v1/tugas/{$tugas->id}/kumpul", ['file' => $jahat])
            ->assertStatus(422);

        // Nama valid: tersimpan dengan hash aman, tanpa '..' di path.
        $baik = UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf');
        $res = $this->postJson("/api/v1/tugas/{$tugas->id}/kumpul", ['file' => $baik]);
        $res->assertStatus(201);
        $path = $res->json('data.file_path') ?? '';
        $this->assertStringNotContainsString('..', (string) $path);
        Storage::disk('public')->assertExists((string) $path);

        // Pola DokumenTraversalTest tetap hijau: traversal di route dokumentasi 404.
        $this->get('/dokumentasi/../.env')->assertNotFound();
    }
}
