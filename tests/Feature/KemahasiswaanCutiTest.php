<?php

namespace Tests\Feature;

use App\Livewire\Kemahasiswaan\Cuti;
use App\Models\CutiPengajuan;
use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class KemahasiswaanCutiTest extends TestCase
{
    use RefreshDatabase;

    private int $tahunId;

    private int $prodiId;

    private int $prodiLainId;

    private User $mahasiswaUser;

    private User $adminUser;

    private int $mahasiswaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);

        $this->tahunId = DB::table('tahun_akademik')->insertGetId([
            'tahun' => '2026',
            'semester' => 'ganjil',
            'aktif' => true,
        ]);

        $fakultasId = DB::table('fakultas')->insertGetId(['nama' => 'Teknik', 'kode' => 'FT']);

        $this->prodiId = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fakultasId,
            'nama' => 'Informatika',
            'jenjang' => 'S1',
            'kode' => 'IF',
            'slug' => 'informatika',
        ]);

        $this->prodiLainId = DB::table('prodi')->insertGetId([
            'fakultas_id' => $fakultasId,
            'nama' => 'Sistem Informasi',
            'jenjang' => 'S1',
            'kode' => 'SI',
            'slug' => 'sistem-informasi',
        ]);

        $this->mahasiswaUser = User::factory()->create();
        $this->mahasiswaUser->assignRole('mahasiswa');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin_prodi');

        $this->mahasiswaId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $this->mahasiswaUser->id,
            'nim' => '20260001',
            'prodi_id' => $this->prodiId,
            'angkatan' => 2026,
            'status' => 'aktif',
        ]);
    }

    public function test_mahasiswa_dapat_mengajukan_cuti(): void
    {
        $this->actingAs($this->mahasiswaUser);

        Livewire::test(Cuti::class)
            ->set('tahunAkademikId', $this->tahunId)
            ->set('alasan', 'Alasan cuti karena sakit berkepanjangan.')
            ->call('ajukan')
            ->assertHasNoErrors();

        $pengajuan = CutiPengajuan::where('mahasiswa_id', $this->mahasiswaId)->first();

        $this->assertNotNull($pengajuan);
        $this->assertSame(CutiPengajuan::STATUS_DIAJUKAN, $pengajuan->status);
        $this->assertSame('aktif', Mahasiswa::find($this->mahasiswaId)->status);
    }

    public function test_mahasiswa_tidak_bisa_ajukan_ganda_saat_pending(): void
    {
        $this->actingAs($this->mahasiswaUser);

        CutiPengajuan::create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'alasan' => 'Pengajuan pertama yang masih pending.',
            'status' => CutiPengajuan::STATUS_DIAJUKAN,
        ]);

        Livewire::test(Cuti::class)
            ->set('tahunAkademikId', $this->tahunId)
            ->set('alasan', 'Pengajuan kedua yang harus ditolak.')
            ->call('ajukan')
            ->assertHasErrors(['alasan']);

        $this->assertSame(1, CutiPengajuan::where('mahasiswa_id', $this->mahasiswaId)->count());
    }

    public function test_admin_menyetujui_cuti_mengubah_status_mahasiswa(): void
    {
        $this->actingAs($this->adminUser);

        $pengajuan = CutiPengajuan::create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'alasan' => 'Butuh istirahat satu semester penuh.',
            'status' => CutiPengajuan::STATUS_DIAJUKAN,
        ]);

        Livewire::test(Cuti::class)
            ->set('catatan', 'Disetujui, silakan registrasi ulang semester depan.')
            ->call('setujui', $pengajuan->id)
            ->assertHasNoErrors();

        $this->assertSame(CutiPengajuan::STATUS_DISETUJUI, $pengajuan->fresh()->status);
        $this->assertSame(Mahasiswa::STATUS_CUTI, Mahasiswa::find($this->mahasiswaId)->status);
    }

    public function test_admin_dapat_menolak_cuti_tanpa_mengubah_status(): void
    {
        $this->actingAs($this->adminUser);

        $pengajuan = CutiPengajuan::create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'alasan' => 'Alasan yang kurang kuat menurut admin.',
            'status' => CutiPengajuan::STATUS_DIAJUKAN,
        ]);

        Livewire::test(Cuti::class)
            ->set('catatan', 'Ditolak karena alasan kurang kuat.')
            ->call('tolak', $pengajuan->id)
            ->assertHasNoErrors();

        $this->assertSame(CutiPengajuan::STATUS_DITOLAK, $pengajuan->fresh()->status);
        $this->assertSame('aktif', Mahasiswa::find($this->mahasiswaId)->status);
    }

    public function test_admin_dapat_mengubah_status_dan_pindah_prodi(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(Cuti::class)
            ->set('statusBaru', Mahasiswa::STATUS_NONAKTIF)
            ->call('ubahStatus', $this->mahasiswaId)
            ->assertHasNoErrors();

        $this->assertSame(Mahasiswa::STATUS_NONAKTIF, Mahasiswa::find($this->mahasiswaId)->status);

        Livewire::test(Cuti::class)
            ->set('prodiTujuanId', $this->prodiLainId)
            ->call('pindahProdi', $this->mahasiswaId)
            ->assertHasNoErrors();

        $this->assertSame($this->prodiLainId, Mahasiswa::find($this->mahasiswaId)->prodi_id);
    }

    public function test_mahasiswa_tidak_bisa_menyetujui_cuti(): void
    {
        $pengajuan = CutiPengajuan::create([
            'mahasiswa_id' => $this->mahasiswaId,
            'tahun_akademik_id' => $this->tahunId,
            'alasan' => 'Mencoba menyetujui pengajuan sendiri.',
            'status' => CutiPengajuan::STATUS_DIAJUKAN,
        ]);

        Livewire::actingAs($this->mahasiswaUser)->test(Cuti::class)->call('setujui', $pengajuan->id);

        // Aksi admin ditolak: status pengajuan dan mahasiswa tidak berubah.
        $this->assertSame(CutiPengajuan::STATUS_DIAJUKAN, $pengajuan->fresh()->status);
        $this->assertSame('aktif', Mahasiswa::find($this->mahasiswaId)->status);
    }

    public function test_route_kemahasiswaan_cuti_terdaftar_dan_butuh_auth(): void
    {
        $this->assertNotNull(collect(Route::getRoutes())->firstWhere('uri', 'kemahasiswaan/cuti'));

        $this->get(route('kemahasiswaan.cuti'))->assertRedirect(route('login'));

        $this->actingAs($this->mahasiswaUser)->get(route('kemahasiswaan.cuti'))->assertOk();
    }
}
