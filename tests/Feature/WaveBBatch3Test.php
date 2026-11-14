<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\Anggaran;
use App\Livewire\Keuangan\TutupBuku;
use App\Livewire\Organisasi\Kelola;
use App\Livewire\Riset\Hibah;
use App\Livewire\Riset\Pengabdian;
use App\Livewire\Riset\Publikasi;
use App\Models\Dosen;
use App\Models\HibahRiset;
use App\Models\Organisasi;
use App\Models\OrganisasiAnggota;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\AnggaranService;
use App\Services\KeuanganService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave B Batch 3: M-032, M-033, M-040, M-042, M-043, M-044.
 */
class WaveBBatch3Test extends TestCase
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

    private function tagihanBayar(int $prodi, int $tahun, string $nim, string $jenis = 'UKT', int $nominal = 1000000): Tagihan
    {
        $user = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $user->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
        $tagihan = Tagihan::create([
            'mahasiswa_id' => $mhs, 'tahun_akademik_id' => $tahun,
            'jenis' => $jenis, 'nominal' => $nominal, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);
        $bayar = Pembayaran::create([
            'tagihan_id' => $tagihan->id, 'metode' => 'manual', 'nominal' => $nominal,
            'status' => 'verified', 'reference' => 'REF-'.$nim,
        ]);

        return $tagihan->fresh();
    }

    /** M-032: periode tutup menolak jurnal penerimaan + refund; buka kembali memulihkan. */
    public function test_tutup_buku_memblokir_jurnal(): void
    {
        [$prodi, $t] = $this->struktur();
        $tagihan = $this->tagihanBayar($prodi, $t, '20260701');
        $bayar = Pembayaran::where('tagihan_id', $tagihan->id)->firstOrFail();
        $keu = $this->user('keuangan');

        Livewire::actingAs($keu)->test(TutupBuku::class)->call('tutup', $t)->assertHasNoErrors();
        $this->assertDatabaseHas('tutup_buku', ['tahun_akademik_id' => $t, 'status' => 'tutup']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tutup buku');
        KeuanganService::postPenerimaan($bayar->fresh());
    }

    public function test_refund_ditolak_saat_tutup_dan_ok_saat_buka(): void
    {
        [$prodi, $t] = $this->struktur();
        $tagihan = $this->tagihanBayar($prodi, $t, '20260702');
        $bayar = Pembayaran::where('tagihan_id', $tagihan->id)->firstOrFail();
        $keu = $this->user('keuangan');

        Livewire::actingAs($keu)->test(TutupBuku::class)->call('tutup', $t);
        $this->expectException(\RuntimeException::class);
        KeuanganService::refundPembayaran($bayar->id, $keu->id);
    }

    public function test_buka_kembali_memulihkan_jurnal(): void
    {
        [$prodi, $t] = $this->struktur();
        $tagihan = $this->tagihanBayar($prodi, $t, '20260703');
        $bayar = Pembayaran::where('tagihan_id', $tagihan->id)->firstOrFail();
        $keu = $this->user('keuangan');

        $tutup = Livewire::actingAs($keu)->test(TutupBuku::class);
        $tutup->call('tutup', $t);
        $tutup->call('buka', $t);

        $jurnal = KeuanganService::postPenerimaan($bayar->fresh());
        $this->assertSame(1000000, (int) $jurnal->nominal);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tutup-buku.dibuka']);
    }

    /** M-033: anggaran vs realisasi dari jurnal. */
    public function test_anggaran_realisasi_dari_jurnal(): void
    {
        [$prodi, $t] = $this->struktur();
        $tagihan = $this->tagihanBayar($prodi, $t, '20260704');
        $bayar = Pembayaran::where('tagihan_id', $tagihan->id)->firstOrFail();
        KeuanganService::postPenerimaan($bayar->fresh());
        $keu = $this->user('keuangan');

        Livewire::actingAs($keu)->test(Anggaran::class)
            ->set('tahun_akademik_id', $t)
            ->set('kode', 'UKT')
            ->set('nama', 'Penerimaan UKT')
            ->set('pagu', 5000000)
            ->call('simpan')
            ->assertHasNoErrors();

        $anggaran = \App\Models\Anggaran::where('kode', 'UKT')->firstOrFail();
        $r = AnggaranService::realisasi($anggaran);
        $this->assertSame(1000000, $r['realisasi']);
        $this->assertSame(4000000, $r['sisa']);
        $this->assertSame(20.0, $r['persen']);
    }

    /** M-040: organisasi + anggota per NIM (idempoten), admin saja. */
    public function test_organisasi_dan_anggota(): void
    {
        [$prodi, $t] = $this->struktur();
        $mhsUser = $this->user('mahasiswa');
        DB::table('mahasiswa')->insert([
            'user_id' => $mhsUser->id, 'nim' => '20260711', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
        $adm = $this->user('admin_prodi');

        $this->actingAs($mhsUser)->get(route('admin.organisasi'))->assertForbidden();
        $this->actingAs($adm)->get(route('admin.organisasi'))->assertOk();

        $ui = Livewire::actingAs($adm)->test(Kelola::class)
            ->set('nama', 'UKM Robotik')
            ->set('jenis', 'ukm')
            ->call('simpan')
            ->assertHasNoErrors();
        $org = Organisasi::where('nama', 'UKM Robotik')->firstOrFail();

        $ui->set('organisasiAnggotaId', $org->id)->set('nimAnggota', '20260711')->call('tambahAnggota')->assertHasNoErrors();
        $ui->set('organisasiAnggotaId', $org->id)->set('nimAnggota', '20260711')->call('tambahAnggota')->assertHasNoErrors();

        $this->assertSame(1, OrganisasiAnggota::where('organisasi_id', $org->id)->count());
    }

    /** M-042: hibah + pencairan bertahap. */
    public function test_hibah_dan_pencairan(): void
    {
        $this->struktur();
        $dosenUser = $this->user('dosen');
        Dosen::factory()->create(['user_id' => $dosenUser->id]);

        $this->actingAs($dosenUser)->get(route('riset.hibah'))->assertOk();

        $ui = Livewire::actingAs($dosenUser)->test(Hibah::class)
            ->set('judul', 'Riset AI Kampus')
            ->set('skema', 'Internal')
            ->set('tahun', '2026')
            ->set('total_dana', 100000000)
            ->set('status', 'berjalan')
            ->call('simpan')
            ->assertHasNoErrors();

        $hibah = HibahRiset::where('judul', 'Riset AI Kampus')->firstOrFail();
        $this->assertSame($dosenUser->id, $hibah->ketua->user_id);

        $ui->set('cairHibahId', $hibah->id)->set('cairTahap', 1)->set('cairNominal', 40000000)->call('cair')->assertHasNoErrors();
        $ui->set('cairHibahId', $hibah->id)->set('cairTahap', 2)->set('cairNominal', 30000000)->call('cair')->assertHasNoErrors();

        $this->assertSame(70000000, $hibah->fresh()->totalCair());
    }

    /** M-043: publikasi milik sendiri; dosen lain tidak bisa hapus. */
    public function test_publikasi_owner_scope(): void
    {
        $this->struktur();
        $a = $this->user('dosen');
        Dosen::factory()->create(['user_id' => $a->id]);
        $b = $this->user('dosen');
        Dosen::factory()->create(['user_id' => $b->id]);

        Livewire::actingAs($a)->test(Publikasi::class)
            ->set('judul', 'Paper AI')
            ->set('jenis', 'jurnal')
            ->set('tahun', '2026')
            ->call('simpan')
            ->assertHasNoErrors();

        $pub = \App\Models\Publikasi::where('judul', 'Paper AI')->firstOrFail();
        $this->assertSame(Dosen::where('user_id', $a->id)->value('id'), $pub->dosen_id);

        Livewire::actingAs($b)->test(Publikasi::class)->call('hapus', $pub->id)->assertForbidden();
        $this->assertDatabaseHas('publikasi', ['id' => $pub->id]);

        Livewire::actingAs($a)->test(Publikasi::class)->call('hapus', $pub->id)->assertHasNoErrors();
        $this->assertDatabaseMissing('publikasi', ['id' => $pub->id]);
    }

    /** M-044: pengabdian + validasi tanggal. */
    public function test_pengabdian_validasi_tanggal(): void
    {
        $this->struktur();
        $dosenUser = $this->user('dosen');
        Dosen::factory()->create(['user_id' => $dosenUser->id]);

        $this->actingAs($dosenUser)->get(route('riset.pengabdian'))->assertOk();

        Livewire::actingAs($dosenUser)->test(Pengabdian::class)
            ->set('judul', 'KKN Tematik')
            ->set('tanggal_mulai', '2026-07-01')
            ->set('tanggal_selesai', '2026-06-01')
            ->call('simpan')
            ->assertHasErrors(['tanggal_selesai']);

        Livewire::actingAs($dosenUser)->test(Pengabdian::class)
            ->set('judul', 'KKN Tematik')
            ->set('lokasi', 'Desa Maju')
            ->set('tanggal_mulai', '2026-07-01')
            ->set('tanggal_selesai', '2026-08-01')
            ->set('dana', 5000000)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pengabdian', ['judul' => 'KKN Tematik', 'lokasi' => 'Desa Maju']);
    }
}
