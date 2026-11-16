<?php

namespace Tests\Feature;

use App\Models\AkreditasiDokumen;
use App\Models\TracerAlumni;
use App\Models\User;
use App\Services\BorangService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Batch 23 ide bank: draf borang akreditasi dari data internal.
 */
class Batch23Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    /** Ringkasan borang memuat profil + tracer + dokumen per prodi. */
    public function test_borang_merangkum_data_prodi(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTB']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PB', 'slug' => 'pb']);

        foreach ([['202499B1', 'aktif'], ['202499B2', 'lulus']] as [$nim, $status]) {
            DB::table('mahasiswa')->insert([
                'user_id' => User::factory()->create()->id, 'nim' => $nim,
                'prodi_id' => $prodi, 'angkatan' => 2020, 'status' => $status,
            ]);
        }

        $lulusId = DB::table('mahasiswa')->where('nim', '202499B2')->value('id');
        TracerAlumni::create(['mahasiswa_id' => $lulusId, 'tahun_lulus' => 2024, 'status_kerja' => 'bekerja']);
        AkreditasiDokumen::create(['prodi_id' => $prodi, 'kategori' => 'borang-3a', 'judul' => 'Dok B23', 'status' => 'terverifikasi']);

        $ringkas = BorangService::ringkasan((int) $prodi);

        $this->assertSame(1, $ringkas['profil']['aktif']);
        $this->assertSame(1, $ringkas['profil']['lulus']);
        $this->assertSame(2024, (int) $ringkas['tracer'][0]['tahun']);
        $this->assertSame(1, $ringkas['tracer'][0]['terserap']);
        $this->assertSame('borang-3a', $ringkas['dokumen'][0]['kategori']);
        $this->assertSame(1, $ringkas['dokumen'][0]['terverifikasi']);

        // Ekspor CSV untuk admin.
        $u = User::factory()->create();
        $u->assignRole('superadmin');

        $res = $this->actingAs($u)->get(route('admin.akreditasi.borang', $prodi))->assertOk();
        $this->assertStringContainsString('borang-prodi-', (string) $res->headers->get('content-disposition'));
    }
}
