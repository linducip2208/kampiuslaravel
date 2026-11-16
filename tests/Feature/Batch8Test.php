<?php

namespace Tests\Feature;

use App\Livewire\Admin\Tracer;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Pertemuan;
use App\Models\TracerAlumni;
use App\Models\User;
use App\Services\ScormService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/**
 * Batch 8 ide bank: paket materi massal per kelas, tren serapan tracer,
 * verifikasi review permission tahunan (command existing, dikunci tes).
 */
class Batch8Test extends TestCase
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
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT8']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'K8', 'slug' => 'k8']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Paket ZIP per kelas memuat materi semua pertemuan + metadata. */
    public function test_paket_kelas_memuat_semua_pertemuan(): void
    {
        [$prodi, $t] = $this->struktur();
        Storage::fake('public');
        Storage::disk('public')->put('materi/a.pdf', 'ISI-A');

        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'MK-8', 'nama' => 'MK8', 'sks' => 3, 'semester' => 1]);
        $kelas = Kelas::create(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        foreach ([1, 2] as $ke) {
            $pt = Pertemuan::create(['kelas_id' => $kelas->id, 'ke' => $ke, 'qr_token' => 'QR8-'.$ke.'-'.uniqid()]);
            Materi::create([
                'pertemuan_id' => $pt->id, 'judul' => "Materi P{$ke}",
                'file_path' => $ke === 1 ? 'materi/a.pdf' : null,
                'deskripsi' => "Deskripsi P{$ke}",
            ]);
        }

        $path = ScormService::paketKelas($kelas->id);

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $meta = json_decode((string) $zip->getFromName('metadata.json'), true);
            $this->assertSame(2, $meta['jumlah_materi']);
            $this->assertSame(2, $meta['jumlah_pertemuan']);
            $this->assertNotFalse($zip->getFromName('imsmanifest.xml'));
            $this->assertSame('ISI-A', $zip->getFromName('pertemuan-1/materi-1.pdf') ?: $zip->getFromName($zip->getNameIndex(0)));
            $zip->close();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        // Rute unduhan massal merespons file ZIP bagi user login.
        $u = $this->user('mahasiswa');
        $this->actingAs($u)->get(route('lms.paket.kelas', $kelas->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    /** Tren serapan: responden vs terserap per tahun lulus. */
    public function test_tren_serapan_per_tahun_lulus(): void
    {
        [$prodi] = $this->struktur();

        $mhs = function (string $nim, string $status, int $tahun) use ($prodi): void {
            $id = DB::table('mahasiswa')->insertGetId([
                'user_id' => $this->user('mahasiswa')->id, 'nim' => $nim,
                'prodi_id' => $prodi, 'angkatan' => 2020, 'status' => 'lulus',
            ]);
            TracerAlumni::create(['mahasiswa_id' => $id, 'tahun_lulus' => $tahun, 'status_kerja' => $status]);
        };

        $mhs('20209961', 'bekerja', 2024);
        $mhs('20209962', 'mencari_kerja', 2024);
        $mhs('20209963', 'wirausaha', 2025);

        $tren = Livewire::actingAs($this->user('superadmin'))->test(Tracer::class)->viewData('tren');
        $byTahun = collect($tren)->keyBy('tahun');

        $this->assertSame(2, $byTahun[2024]['responden']);
        $this->assertSame(1, $byTahun[2024]['terserap']);
        $this->assertSame(50.0, $byTahun[2024]['persen']);
        $this->assertSame(1, $byTahun[2025]['terserap']);
    }

    /** Review permission tahunan: command existing berjalan sukses (dikunci). */
    public function test_permission_review_berjalan_sukses(): void
    {
        $this->artisan('permission:review')->assertExitCode(0);
    }
}
