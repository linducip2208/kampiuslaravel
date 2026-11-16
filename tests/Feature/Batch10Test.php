<?php

namespace Tests\Feature;

use App\Livewire\Bkd\Kelola;
use App\Livewire\Kemahasiswaan\Cuti;
use App\Models\BebanDosen;
use App\Models\CutiPengajuan;
use App\Models\Dosen;
use App\Models\User;
use App\Services\AkademikService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 10 ide bank: masa studi transkrip, lampiran cuti, rekap BKD per prodi.
 */
class Batch10Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function struktur(): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTA']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'KA', 'slug' => 'ka']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Masa studi: angkatan 2024 × 2027/ganjil = semester 7. */
    public function test_masa_studi_dihitung_benar(): void
    {
        $this->struktur();

        $ms = AkademikService::masaStudi(2024);

        $this->assertSame(7, $ms['semester_ke']);
        $this->assertSame(3, $ms['tahun']);
        $this->assertStringContainsString('Semester 7', $ms['label']);
    }

    /** Transkrip cetak menampilkan masa studi. */
    public function test_transkrip_cetak_memuat_masa_studi(): void
    {
        [$prodi] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20249901', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);

        $this->actingAs($u)->get(route('transkrip.cetak', $mhsId))
            ->assertOk()
            ->assertSee('Masa studi:')
            ->assertSee('Semester 7');
    }

    /** Pengajuan cuti dengan lampiran tersimpan + tertaut di riwayat. */
    public function test_cuti_dengan_lampiran(): void
    {
        [$prodi, $t] = $this->struktur();
        Storage::fake('public');

        $u = $this->user('mahasiswa');
        DB::table('mahasiswa')->insert(['user_id' => $u->id, 'nim' => '20249902', 'prodi_id' => $prodi, 'angkatan' => 2024, 'status' => 'aktif']);

        $file = UploadedFile::fake()->create('surat-sakit.pdf', 100, 'application/pdf');

        $html = Livewire::actingAs($u)->test(Cuti::class)
            ->set('tahunAkademikId', $t)
            ->set('alasan', 'Sakit berkepanjangan perlu istirahat total')
            ->set('lampiran', $file)
            ->call('ajukan')
            ->assertHasNoErrors()
            ->html();

        $pengajuan = CutiPengajuan::first();
        $this->assertNotNull($pengajuan->lampiran_path);
        Storage::disk('public')->assertExists($pengajuan->lampiran_path);
        $this->assertStringContainsString('Lihat', $html);
    }

    /** Rekap beban BKD diagregat per prodi untuk admin. */
    public function test_rekap_beban_per_prodi(): void
    {
        [$prodi, $t] = $this->struktur();
        $dosenUser = $this->user('dosen');
        $dosen = Dosen::create(['user_id' => $dosenUser->id, 'nidn' => 'NIDNA', 'prodi_id' => $prodi]);
        BebanDosen::create([
            'dosen_id' => $dosen->id, 'tahun_akademik_id' => $t, 'kategori' => 'pendidikan',
            'uraian' => 'Mengajar MK-A', 'sks' => 6, 'status' => 'disetujui',
        ]);

        $rekap = Livewire::actingAs($this->user('superadmin'))->test(Kelola::class)->viewData('rekapProdi');

        $this->assertCount(1, $rekap);
        $this->assertSame(6, $rekap[0]['total_sks']);
        $this->assertSame(1, $rekap[0]['dosen']);
    }
}
