<?php

namespace Tests\Feature;

use App\Livewire\Lms\KumpulTugas;
use App\Livewire\Lms\MateriCrud;
use App\Livewire\Lms\TugasCrud;
use App\Models\TahunAkademik;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave B Batch 1 (P0/P1): M-021, M-024, M-027, M-037, M-045, M-046, M-047, M-048.
 */
class WaveBBatch1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function strukturAkademik(): array
    {
        $t1 = DB::table('tahun_akademik')->insertGetId(['tahun' => '2025', 'semester' => 'ganjil', 'aktif' => false]);
        $t2 = DB::table('tahun_akademik')->insertGetId(['tahun' => '2025', 'semester' => 'genap', 'aktif' => false]);
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);
        $prodi = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fak, 'nama' => 'Informatika', 'jenjang' => 'S1', 'kode' => 'IF', 'slug' => 'informatika',
        ]);

        return [$t1, $t2, $prodi];
    }

    private function userRole(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function kelas(int $prodi, int $tahunId, string $kode): int
    {
        $mk = DB::table('matakuliah')->insertGetId([
            'prodi_id' => $prodi, 'kode' => $kode, 'nama' => 'MK '.$kode, 'sks' => 3, 'semester' => 1,
        ]);

        return DB::table('kelas')->insertGetId([
            'matakuliah_id' => $mk, 'tahun_akademik_id' => $tahunId, 'nama' => 'A', 'kuota' => 40,
        ]);
    }

    /** M-021: aktifkan() menegakkan tepat 1 periode aktif. */
    public function test_satu_periode_aktif_ditegakkan(): void
    {
        [$t1, $t2] = $this->strukturAkademik();

        TahunAkademik::aktifkan($t1);
        $this->assertSame(1, TahunAkademik::where('aktif', true)->count());

        TahunAkademik::aktifkan($t2);
        $this->assertSame(1, TahunAkademik::where('aktif', true)->count());
        $this->assertTrue(TahunAkademik::find($t2)->aktif);
        $this->assertFalse(TahunAkademik::find($t1)->aktif);
    }

    /** M-021: rollover idempoten + peta ganjil/genap. */
    public function test_rollover_membuat_periode_berikut_idempoten(): void
    {
        [$t1] = $this->strukturAkademik();
        TahunAkademik::aktifkan($t1);

        $this->assertSame(['2025', 'genap'], TahunAkademik::berikutnya('2025', 'ganjil'));
        $this->assertSame(['2026', 'ganjil'], TahunAkademik::berikutnya('2025', 'genap'));

        // Tanpa argumen: lanjut dari aktif (2025 ganjil → 2025 genap yang sudah ada).
        $p1 = TahunAkademik::rollover();
        $this->assertSame('2025', $p1->tahun);
        $this->assertSame('genap', $p1->semester);

        // Idempoten: argumen eksplisit yang sama dipakai ulang, bukan diduplikasi.
        $count = TahunAkademik::count();
        $p2 = TahunAkademik::rollover('2025', 'genap');

        $this->assertSame($p1->id, $p2->id);
        $this->assertSame($count, TahunAkademik::count());
        $this->assertSame(1, TahunAkademik::where('aktif', true)->count());
    }

    /** M-021: command akademik:rollover. */
    public function test_command_akademik_rollover(): void
    {
        $this->strukturAkademik();

        $this->artisan('akademik:rollover', ['--tahun' => '2026', '--semester' => 'ganjil'])
            ->assertSuccessful();

        $this->assertDatabaseHas('tahun_akademik', ['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);
        $this->assertSame(1, TahunAkademik::where('aktif', true)->count());
    }

    /** M-024: KHS dipaginasi 25 + filter ?tahun=, IPK tetap global. */
    public function test_khs_paginasi_dan_filter_periode(): void
    {
        [$t1, $t2, $prodi] = $this->strukturAkademik();
        $user = $this->userRole('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => '20260201', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        for ($i = 1; $i <= 15; $i++) {
            DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $t1, 'K1-'.$i), 'bobot' => 3.0, 'is_locked' => true]);
            DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $t2, 'K2-'.$i), 'bobot' => 3.0, 'is_locked' => true]);
        }

        $semua = $this->actingAs($user)->get(route('khs.show', $mhs))->assertOk()->viewData();
        $this->assertSame(30, $semua['nilai']->total());
        $this->assertSame(25, $semua['nilai']->count());
        $this->assertSame(3.0, $semua['ipk']);

        $filter = $this->actingAs($user)->get(route('khs.show', $mhs).'?tahun='.$t2)->assertOk()->viewData();
        $this->assertSame(15, $filter['nilai']->total());
        $this->assertSame(3.0, $filter['ipk']);

        $hal2 = $this->actingAs($user)->get(route('khs.show', $mhs).'?page=2')->assertOk()->viewData();
        $this->assertSame(30, $hal2['nilai']->total());
        $this->assertSame(5, $hal2['nilai']->count());
    }

    /** M-024: transkrip ikut filter periode. */
    public function test_transkrip_filter_periode(): void
    {
        [$t1, $t2, $prodi] = $this->strukturAkademik();
        $user = $this->userRole('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => '20260202', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $t1, 'T1-A'), 'bobot' => 4.0, 'is_locked' => true]);
        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $this->kelas($prodi, $t2, 'T2-A'), 'bobot' => 1.0, 'is_locked' => true]);

        $view = $this->actingAs($user)->get(route('transkrip.cetak', $mhs).'?tahun='.$t1)->assertOk()->viewData();
        $this->assertSame(1, $view['nilai']->total());
        $this->assertSame(2.5, $view['ipk']);
    }

    /** M-027: TugasCrud — dosen boleh, mahasiswa ditolak 403 (route + komponen). */
    public function test_tugas_crud_dosen_dan_mahasiswa_ditolak(): void
    {
        [$t1, $t2, $prodi] = $this->strukturAkademik();
        $kelasId = $this->kelas($prodi, $t2, 'LMS-1');
        $dosen = $this->userRole('dosen');
        $mhs = $this->userRole('mahasiswa');

        // Route group dosen: mahasiswa tanpa role → 403 sebelum komponen.
        $this->actingAs($mhs)->get(route('lms.tugas', ['kelas' => $kelasId]))->assertForbidden();
        $this->actingAs($dosen)->get(route('lms.tugas', ['kelas' => $kelasId]))->assertOk();

        Livewire::actingAs($dosen)->test(TugasCrud::class, ['kelasId' => $kelasId])
            ->set('judul', 'Tugas 1')
            ->set('deskripsi', 'Kerjakan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tugas', ['kelas_id' => $kelasId, 'judul' => 'Tugas 1']);
    }

    /** M-027: KumpulTugas — deadline diblokir, anti-spoof owner. */
    public function test_kumpul_tugas_deadline_dan_antispoof(): void
    {
        [$t1, $t2, $prodi] = $this->strukturAkademik();
        $kelasId = $this->kelas($prodi, $t2, 'LMS-2');
        Storage::fake('public');

        $userA = $this->userRole('mahasiswa');
        $userB = $this->userRole('mahasiswa');
        $mhsA = DB::table('mahasiswa')->insertGetId(['user_id' => $userA->id, 'nim' => '20260211', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);
        $mhsB = DB::table('mahasiswa')->insertGetId(['user_id' => $userB->id, 'nim' => '20260212', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);

        $kadaluarsa = DB::table('tugas')->insertGetId(['kelas_id' => $kelasId, 'judul' => 'Tua', 'deadline' => now()->subDay()]);
        Livewire::actingAs($userA)->test(KumpulTugas::class, ['tugasId' => $kadaluarsa, 'mahasiswaId' => $mhsA])
            ->set('file', UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['file']);
        $this->assertDatabaseMissing('tugas_kumpul', ['tugas_id' => $kadaluarsa]);

        $buka = DB::table('tugas')->insertGetId(['kelas_id' => $kelasId, 'judul' => 'Baru', 'deadline' => now()->addDay()]);
        Livewire::actingAs($userA)->test(KumpulTugas::class, ['tugasId' => $buka, 'mahasiswaId' => $mhsB])
            ->set('file', UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tugas_kumpul', ['tugas_id' => $buka, 'mahasiswa_id' => $mhsA]);
    }

    /** M-027: MateriCrud — mimes ditolak, pdf diterima. */
    public function test_materi_crud_validasi_dan_upload(): void
    {
        [$t1, $t2, $prodi] = $this->strukturAkademik();
        $kelasId = $this->kelas($prodi, $t2, 'LMS-3');
        $pertemuan = DB::table('pertemuan')->insertGetId(['kelas_id' => $kelasId, 'ke' => 1, 'qr_token' => 'tok-'.uniqid()]);
        $dosen = $this->userRole('dosen');
        Storage::fake('public');

        Livewire::actingAs($dosen)->test(MateriCrud::class, ['pertemuanId' => $pertemuan])
            ->set('judul', 'Materi X')
            ->set('file', UploadedFile::fake()->create('jahat.exe', 50))
            ->call('save')
            ->assertHasErrors(['file']);

        Livewire::actingAs($dosen)->test(MateriCrud::class, ['pertemuanId' => $pertemuan])
            ->set('judul', 'Materi 1')
            ->set('file', UploadedFile::fake()->create('materi.pdf', 100, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('materi', ['pertemuan_id' => $pertemuan, 'judul' => 'Materi 1']);
    }

    /** M-037: ekspor butuh permission laporan.view (defense-in-depth). */
    public function test_ekspor_butuh_permission_laporan_view(): void
    {
        $mhs = $this->userRole('mahasiswa');
        $pim = $this->userRole('pimpinan');
        $adm = $this->userRole('admin_prodi');

        $this->actingAs($mhs)->get(route('laporan.export.tagihan'))->assertForbidden();
        $this->actingAs($pim)->get(route('laporan.export.tagihan'))->assertOk();
        $this->actingAs($adm)->get(route('admin.impor.template', 'nilai'))->assertOk();
    }

    /** M-045: envelope konsisten + OpenAPI ter-generate. */
    public function test_openapi_json_dan_envelope_v2(): void
    {
        $this->getJson('/api/openapi.json')->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('paths./api/v1/me.get.summary', 'AuthController#me')
            ->assertJsonPath('paths./api/v2/status.get.summary', 'PublikApiController#status');

        $this->getJson('/api/v2/prodi')->assertOk()->assertJson(['ok' => true]);
        $this->getJson('/api/v2/status')->assertOk()
            ->assertJson(['ok' => true, 'api' => 'v2', 'db' => 'up']);
    }

    /** M-046: rute sensitif wajib ber-throttle. */
    public function test_rute_sensitif_punya_throttle(): void
    {
        foreach (['payment.callback', 'pddikti.push', 'pddikti.export', 'keuangan.refund', 'pddikti.sync'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} tidak terdaftar.");
            $middleware = implode('|', (array) $route->gatherMiddleware());
            $this->assertStringContainsString('throttle', $middleware, "Route {$name} tanpa throttle.");
        }
    }

    /** M-047: backup:create lalu backup:verify VALID. */
    public function test_backup_create_dan_verify(): void
    {
        $this->artisan('backup:create')->assertSuccessful();

        $disk = Storage::disk('local');
        $this->assertTrue($disk->exists('backups/latest.txt'));

        $stamp = trim((string) $disk->get('backups/latest.txt'));
        $this->assertTrue($disk->exists("backups/{$stamp}/manifest.json"));

        $this->artisan('backup:verify')->assertSuccessful();
    }

    /** M-048: health memuat checks observabilitas. */
    public function test_health_memuat_checks(): void
    {
        $this->get(route('health'))->assertOk()
            ->assertJsonPath('db', 'up')
            ->assertJsonPath('checks.database', 'up')
            ->assertJsonPath('checks.migrations', 'ok');
    }
}
