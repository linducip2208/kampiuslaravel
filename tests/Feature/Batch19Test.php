<?php

namespace Tests\Feature;

use App\Livewire\Asrama\Kelola;
use App\Models\AsramaHuni;
use App\Models\AsramaKamar;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 19 ide bank: alokasi kamar asrama + iuran (tagihan jenis asrama).
 */
class Batch19Test extends TestCase
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
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTJ']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PJ', 'slug' => 'pj']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    private function mhs(string $nim, int $prodi): int
    {
        return (int) DB::table('mahasiswa')->insertGetId([
            'user_id' => $this->user('mahasiswa')->id, 'nim' => $nim,
            'prodi_id' => $prodi, 'angkatan' => 2024, 'status' => 'aktif',
        ]);
    }

    /** Hunian: 1 mhs 1 kamar; kamar penuh menolak. */
    public function test_alokasi_menegakkan_satu_bed_satu_penghuni(): void
    {
        [$prodi] = $this->struktur();
        $adm = $this->user('superadmin');
        $this->mhs('202499A1', $prodi);
        $this->mhs('202499A2', $prodi);

        $ui = Livewire::actingAs($adm)->test(Kelola::class)
            ->set('gedung', 'Gedung A')->set('kode', 'A-101')->set('kapasitas', 1)->set('iuran', 750000)
            ->call('simpanKamar')
            ->assertHasNoErrors();

        $kamar = AsramaKamar::where('kode', 'A-101')->firstOrFail();

        $ui->set('nimHuni', '202499A1')->set('kamarHuniId', $kamar->id)->call('hunikan')->assertHasNoErrors();
        $this->assertSame(1, AsramaHuni::count());

        // Mahasiswa sama di kamar lain → ditolak.
        $ui->set('nimHuni', '202499A1')->set('kamarHuniId', $kamar->id)->call('hunikan');
        $ui->assertHasErrors(['nimHuni']);

        // Kamar kapasitas 1 sudah penuh → penghuni kedua ditolak.
        $ui->set('nimHuni', '202499A2')->set('kamarHuniId', $kamar->id)->call('hunikan');
        $ui->assertHasErrors(['nimHuni']);
        $this->assertSame(1, AsramaHuni::count());
    }

    /** Tarik iuran: tagihan jenis asrama per penghuni, idempoten. */
    public function test_tarik_iuran_membuat_tagihan_asrama(): void
    {
        [$prodi, $t] = $this->struktur();
        $adm = $this->user('superadmin');
        $this->mhs('202499B1', $prodi);

        $ui = Livewire::actingAs($adm)->test(Kelola::class)
            ->set('gedung', 'Gedung B')->set('kode', 'B-201')->set('kapasitas', 2)->set('iuran', 750000)
            ->call('simpanKamar')
            ->assertHasNoErrors();

        $kamar = AsramaKamar::where('kode', 'B-201')->firstOrFail();

        $ui->set('nimHuni', '202499B1')->set('kamarHuniId', $kamar->id)->call('hunikan')->assertHasNoErrors();
        $ui->call('tarikIuran')->assertHasNoErrors();

        $this->assertDatabaseHas('tagihan', [
            'tahun_akademik_id' => $t, 'jenis' => 'asrama', 'nominal' => 750000, 'status' => 'belum_bayar',
        ]);

        // Idempoten: tarik ulang tidak menggandakan.
        $ui->call('tarikIuran')->assertHasNoErrors();
        $this->assertSame(1, Tagihan::where('jenis', 'asrama')->count());
    }

    /** Halaman asrama terbuka untuk admin. */
    public function test_halaman_asrama_untuk_admin(): void
    {
        $this->actingAs($this->user('superadmin'))->get(route('asrama.kelola'))
            ->assertOk()
            ->assertSee('Alokasi Hunian');
    }
}
