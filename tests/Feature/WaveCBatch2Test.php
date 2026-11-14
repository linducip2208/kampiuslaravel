<?php

namespace Tests\Feature;

use App\Livewire\Admin\Tracer;
use App\Livewire\Alumni\Karier;
use App\Livewire\Analitik\Eksekutif;
use App\Livewire\Bkd\Kelola;
use App\Livewire\Tracer\Isi;
use App\Models\Alumni;
use App\Models\BebanDosen;
use App\Models\Dosen;
use App\Models\TracerAlumni;
use App\Models\User;
use App\Services\BkdService;
use App\Services\EksekutifService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave C Batch 2: M-053 (BKD), M-055 (alumni+tracer), M-056 (eksekutif KPI),
 * M-059 (kontrak API Flutter).
 */
class WaveCBatch2Test extends TestCase
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

        return [$prodi, $t];
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** M-053: ajukan → setujui + rekap + SK; mahasiswa ditolak. */
    public function test_bkd_ajukan_setujui_dan_sk(): void
    {
        [$prodi, $t] = $this->struktur();
        $dosenUser = $this->user('dosen');
        $dosen = Dosen::factory()->create(['user_id' => $dosenUser->id]);
        $adm = $this->user('admin_prodi');
        $mhs = $this->user('mahasiswa');

        $this->actingAs($mhs)->get(route('bkd.kelola'))->assertForbidden();
        $this->actingAs($dosenUser)->get(route('bkd.kelola'))->assertOk();

        Livewire::actingAs($dosenUser)->test(Kelola::class)
            ->set('tahunId', $t)
            ->set('kategori', 'penelitian')
            ->set('uraian', 'Penelitian AI')
            ->set('sks', 4)
            ->call('ajukan')
            ->assertHasNoErrors();

        $beban = BebanDosen::where('uraian', 'Penelitian AI')->firstOrFail();
        $this->assertSame('diajukan', $beban->status);

        Livewire::actingAs($adm)->test(Kelola::class)->call('setujui', $beban->id)->assertHasNoErrors();
        $this->assertSame('disetujui', $beban->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'bkd.disetujui']);
        $this->assertSame(4, BkdService::totalSks($dosen->id, $t));

        $sk = BkdService::generateSk($dosen->id, $t);
        $this->assertStringStartsWith('SK/MENGAJAR/', $sk->nomor);
        $this->assertSame($sk->id, BkdService::generateSk($dosen->id, $t)->id);
    }

    /** M-055: alumni update karier; tracer publik terverifikasi NIM; admin rekap. */
    public function test_alumni_karier_dan_tracer(): void
    {
        [$prodi] = $this->struktur();
        $lulusUser = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $lulusUser->id, 'nim' => '20201111', 'prodi_id' => $prodi,
            'angkatan' => 2020, 'status' => 'lulus',
        ]);
        Alumni::create(['mahasiswa_id' => $mhs, 'tahun_lulus' => 2024]);

        $this->actingAs($lulusUser)->get(route('alumni.karier'))->assertOk();

        Livewire::actingAs($lulusUser)->test(Karier::class)
            ->set('pekerjaan', 'Software Engineer')
            ->set('instansi', 'PT Maju')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumni', ['mahasiswa_id' => $mhs, 'pekerjaan' => 'Software Engineer']);

        $this->get(route('publik.tracer'))->assertOk();
        Livewire::test(Isi::class)
            ->set('nim', '20201111')
            ->set('status_kerja', 'bekerja')
            ->set('perusahaan', 'PT Maju')
            ->call('kirim')
            ->assertHasNoErrors();
        Livewire::test(Isi::class)
            ->set('nim', 'TIDAK-ADA')
            ->call('kirim')
            ->assertHasErrors(['nim']);

        $this->assertSame(1, TracerAlumni::where('mahasiswa_id', $mhs)->count());

        $adm = $this->user('admin_prodi');
        $this->actingAs($adm)->get(route('admin.tracer'))->assertOk()
            ->assertSee('PT Maju');
    }

    /** M-056: KPI agregat benar (tanpa N+1). */
    public function test_eksekutif_kpi(): void
    {
        [$prodi, $t] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20201201', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'KP-1', 'nama' => 'KPI MK', 'sks' => 4, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $kelas, 'bobot' => 3.0, 'is_locked' => true]);
        Dosen::factory()->create();

        $kpi = EksekutifService::kpi();
        $this->assertSame(3.0, $kpi['ipk_rata']);
        $this->assertSame(1.0, $kpi['rasio_mhs_dosen']);

        $pim = $this->user('pimpinan');
        $this->actingAs($pim)->get(route('laporan.eksekutif'))->assertOk()->assertSee('IPK Rata-rata');
    }

    /** M-059: kontrak API yang dipakai aplikasi Flutter — shape stabil. */
    public function test_kontrak_api_flutter(): void
    {
        [$prodi] = $this->struktur();
        $u = $this->user('mahasiswa');
        DB::table('mahasiswa')->insert([
            'user_id' => $u->id, 'nim' => '20201301', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
        Sanctum::actingAs($u, ['mobile']);

        $this->getJson('/api/v1/me')->assertOk()->assertJsonStructure(['data' => ['id', 'name']]);
        $this->getJson('/api/v1/jadwal-hari-ini')->assertOk();
        $this->getJson('/api/v1/tagihan')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/nilai-ringkas')->assertOk()->assertJsonStructure(['data' => ['ipk', 'total_sks']]);
        $this->getJson('/api/v1/tugas')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/bimbingan')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/pengumuman')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/api/v1/notifikasi')->assertOk()->assertJsonStructure(['data']);
        $this->postJson('/api/v1/device-token', ['fcm_token' => 'fcm-test-token'])->assertCreated()->assertJson(['ok' => true]);
        $this->postJson('/api/v1/tugas/9999/kumpul', ['catatan' => 'x'])->assertStatus(422);
        $this->postJson('/api/v1/absensi/scan', [])->assertStatus(422);
        $this->postJson('/api/v1/tagihan/9999/va')->assertNotFound();
        $this->postJson('/api/v2/webhook/terima', ['event' => 'x'])->assertStatus(503);
        $this->getJson('/api/openapi.json')->assertOk()->assertJsonPath('openapi', '3.1.0');
    }
}
