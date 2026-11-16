<?php

namespace Tests\Feature;

use App\Livewire\Sidang\NilaiSidang;
use App\Models\AuditLog;
use App\Models\Pembayaran;
use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\PmbArsipService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/**
 * Batch 12 ide bank: arsip berkas PMB, penguji eksternal, kuitansi gabungan.
 */
class Batch12Test extends TestCase
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
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTC']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'KC', 'slug' => 'kc']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Arsip per gelombang: ZIP berisi berkas + manifest, tercatat di audit. */
    public function test_arsip_berkas_pmb_per_gelombang(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pmb/PMB-B12-1/ijazah.pdf', 'IJAZAH-1');

        $gel = PmbGelombang::create(['nama' => 'Gel B12', 'kuota' => 10, 'biaya' => 0, 'aktif' => true]);
        PmbPendaftar::create([
            'no_daftar' => 'PMB-B12-1', 'nama' => 'Arsip Satu', 'email' => 'b12a@t.id',
            'hp' => '081', 'status' => 'diajukan', 'gelombang_id' => $gel->id,
            'berkas_json' => ['ijazah' => 'pmb/PMB-B12-1/ijazah.pdf', 'kk' => 'pmb/PMB-B12-1/hilang.pdf'],
        ]);

        $path = PmbArsipService::arsipGelombang($gel->id, $this->user('superadmin')->id);

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $meta = json_decode((string) $zip->getFromName('manifest.json'), true);
            $this->assertSame(1, $meta['jumlah_pendaftar']);
            $this->assertSame('IJAZAH-1', $zip->getFromName('pmb/PMB-B12-1/ijazah.pdf'));
            $zip->close();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->assertTrue(AuditLog::where('action', 'pmb.arsip')->where('model_id', $gel->id)->exists());

        // Rute unduhan arsip untuk admin.
        $this->actingAs($this->user('superadmin'))->get(route('admin.pmb.arsip', $gel->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    /** Penguji eksternal bisa dinilai dan masuk rata-rata sidang. */
    public function test_penguji_eksternal_masuk_rata_rata(): void
    {
        [$prodi] = $this->struktur();
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $this->user('mahasiswa')->id, 'nim' => '20249971',
            'prodi_id' => $prodi, 'angkatan' => 2021, 'status' => 'aktif',
        ]);
        $judul = SkripsiJudul::create(['mahasiswa_id' => $mhsId, 'judul' => 'Judul B12', 'status' => 'sidang']);
        $sidang = Sidang::create(['judul_id' => $judul->id, 'jenis' => 'hasil', 'status' => 'terdaftar']);

        Livewire::actingAs($this->user('superadmin'))->test(NilaiSidang::class)
            ->set('sidang_id', $sidang->id)
            ->set('namaEksternal', 'Dr. Praktisi Industri')
            ->set('nilai', 80)
            ->call('addPenguji')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sidang_penguji', [
            'sidang_id' => $sidang->id, 'nama_eksternal' => 'Dr. Praktisi Industri',
        ]);
        $this->assertSame(80.0, (float) $sidang->fresh()->nilai);
    }

    /** Kuitansi gabungan merangkum semua pembayaran terverifikasi 1 periode. */
    public function test_kuitansi_gabungan_merangkum_periode(): void
    {
        [$prodi, $t] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20249972', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);

        foreach (['UKT' => 1000000, 'SPP' => 500000] as $jenis => $nominal) {
            $tag = Tagihan::create([
                'mahasiswa_id' => $mhsId, 'tahun_akademik_id' => $t, 'jenis' => $jenis,
                'nominal' => $nominal, 'denda' => 0, 'terbayar' => $nominal, 'status' => 'lunas',
            ]);
            Pembayaran::create([
                'tagihan_id' => $tag->id, 'nominal' => $nominal,
                'metode' => 'transfer', 'status' => 'verified', 'verified_at' => now(),
            ]);
        }

        $this->actingAs($u)->get(route('kwitansi.gabungan', [
            'mahasiswa_id' => $mhsId, 'tahun_akademik_id' => $t,
        ]))->assertOk()->assertSee('1.500.000');
    }
}
