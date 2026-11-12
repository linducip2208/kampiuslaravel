<?php

namespace Tests\Feature;

use App\Livewire\Admin\ImporCsv;
use App\Livewire\Beasiswa\Kelola as BeasiswaKelola;
use App\Livewire\Helpdesk\Tiket as HelpdeskTiket;
use App\Livewire\Kalender\Kelola as KalenderKelola;
use App\Livewire\Skripsi\Yudisium;
use App\Models\Beasiswa;
use App\Models\Mahasiswa;
use App\Models\MahasiswaBeasiswa;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use App\Models\Tiket;
use App\Models\User;
use App\Services\KeuanganService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class FiturBaruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('superadmin');

        return $u;
    }

    public function test_beasiswa_persen_memotong_tagihan_massal(): void
    {
        $tahun = TahunAkademik::factory()->create();
        $mhs = Mahasiswa::factory()->create();
        $bea = Beasiswa::create(['nama' => 'Prestasi 50%', 'jenis' => 'persen', 'nilai' => 50, 'aktif' => true]);
        MahasiswaBeasiswa::create(['mahasiswa_id' => $mhs->id, 'beasiswa_id' => $bea->id, 'tahun_akademik_id' => $tahun->id, 'status' => 'aktif']);

        $hasil = KeuanganService::generateMassal($tahun->id, 'UKT', 5000000);

        $this->assertSame(1, $hasil['created']);
        $tagihan = $mhs->tagihan()->first();
        $this->assertSame(2500000, (int) $tagihan->nominal);
        $this->assertSame(2500000, (int) $tagihan->diskon);
        $this->assertSame($bea->id, (int) $tagihan->beasiswa_id);
    }

    public function test_beasiswa_nominal_dibatasi_nominal_tagihan(): void
    {
        $this->assertSame(3000, (new Beasiswa(['jenis' => 'nominal', 'nilai' => 5000]))->hitungPotongan(3000));
    }

    public function test_admin_dapat_kelola_beasiswa(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BeasiswaKelola::class)
            ->set('nama', 'Bidikmisi')
            ->set('jenis', 'persen')
            ->set('nilai', 100)
            ->call('simpan')
            ->assertSee('Beasiswa tersimpan');

        $this->assertDatabaseHas('beasiswa', ['nama' => 'Bidikmisi']);
    }

    public function test_mahasiswa_ditolak_kelola_beasiswa(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        Livewire::actingAs($mhs)->test(BeasiswaKelola::class)->assertForbidden();
    }

    public function test_kalender_crud_dan_terbaca_semua(): void
    {
        Livewire::actingAs($this->admin())
            ->test(KalenderKelola::class)
            ->set('judul', 'UTS Ganjil')
            ->set('jenis', 'uts')
            ->set('tgl_mulai', now()->addWeek()->format('Y-m-d'))
            ->call('simpan')
            ->assertSee('Agenda tersimpan');

        $this->assertDatabaseHas('kalender_events', ['judul' => 'UTS Ganjil']);

        $biasa = User::factory()->create();
        Livewire::actingAs($biasa)->test(KalenderKelola::class)->assertSee('UTS Ganjil');
    }

    public function test_yudisium_membutuhkan_syarat_lengkap(): void
    {
        $tahun = TahunAkademik::factory()->create();
        $mhs = Mahasiswa::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Yudisium::class)
            ->call('luluskan', $mhs->id)
            ->assertHasErrors(['yudisium']);

        $this->assertSame('aktif', $mhs->fresh()->status);
    }

    public function test_helpdesk_buat_dan_balas(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        Livewire::actingAs($mhs)
            ->test(HelpdeskTiket::class)
            ->set('subjek', 'KRS error')
            ->set('isi', 'Tidak bisa simpan KRS')
            ->call('buat')
            ->assertSee('Tiket terkirim');

        $tiket = Tiket::firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(HelpdeskTiket::class)
            ->call('buka', $tiket->id)
            ->set('balasan', 'Coba lagi sekarang.')
            ->call('balas')
            ->assertSee('Balasan terkirim');

        $this->assertSame('diproses', $tiket->fresh()->status);
        $this->assertDatabaseHas('tiket_balasan', ['tiket_id' => $tiket->id]);
    }

    public function test_impor_csv_mahasiswa(): void
    {
        $prodi = Prodi::factory()->create(['kode' => 'IFX']);

        $csv = "nim;nama;email;hp;prodi_kode;angkatan\n20260099;Anak Baru;baru99@example.com;081200000099;IFX;2026\n";
        $file = UploadedFile::fake()->createWithContent('mhs.csv', $csv);

        Livewire::actingAs($this->admin())
            ->test(ImporCsv::class)
            ->set('jenis', 'mahasiswa')
            ->set('file', $file)
            ->call('impor')
            ->assertSee('1 berhasil');

        $this->assertDatabaseHas('mahasiswa', ['nim' => '20260099']);
    }

    public function test_export_laporan_butuh_role(): void
    {
        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('laporan.export.tagihan'))->assertForbidden();

        $pim = User::factory()->create();
        $pim->assignRole('pimpinan');
        $this->actingAs($pim)->get(route('laporan.export.tagihan'))->assertOk();
        $this->actingAs($pim)->get(route('laporan.export.absensi'))->assertOk();
    }
}
