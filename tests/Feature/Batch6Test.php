<?php

namespace Tests\Feature;

use App\Livewire\Krs\Pengajuan;
use App\Livewire\Skripsi\Yudisium;
use App\Models\Krs;
use App\Models\PeminjamanRuang;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\WisudaBatch;
use App\Models\WisudaPeserta;
use App\Services\JadwalService;
use App\Services\KeuanganService;
use Carbon\Carbon;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 6 ide bank: kalender ruang, arsip KRS, buku wisuda CSV, denda di pesan.
 */
class Batch6Test extends TestCase
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
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTK']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'KK', 'slug' => 'kk']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Kalender ruang menampilkan kuliah + booking; tamu ditolak. */
    public function test_kalender_ruang_menampilkan_penggunaan(): void
    {
        [$prodi, $t] = $this->struktur();
        $ruang = DB::table('ruang')->insertGetId(['gedung' => 'G', 'nama' => 'Rapat A', 'kapasitas' => 30]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'KL-1', 'nama' => 'KL', 'sks' => 3, 'semester' => 1]);
        $senin = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $hari = JadwalService::hariDariTanggal($senin);
        DB::table('kelas')->insert(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40, 'ruang_id' => $ruang, 'hari' => $hari, 'jam_mulai' => '07:00:00', 'jam_selesai' => '09:30:00']);

        $u = $this->user('mahasiswa');
        DB::table('mahasiswa')->insert(['user_id' => $u->id, 'nim' => '20209941', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);
        PeminjamanRuang::create([
            'ruang_id' => $ruang, 'user_id' => $u->id, 'tanggal' => $senin,
            'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'status' => 'disetujui',
        ]);

        $this->get(route('fasilitas.kalender'))->assertRedirect(route('login'));
        $html = $this->actingAs($u)->get(route('fasilitas.kalender'))->assertOk()->getContent();
        $this->assertStringContainsString('Rapat A', (string) $html);
        $this->assertStringContainsString('13:00', (string) $html);
    }

    /** Arsip KRS: setelah ajukan, riwayat tampil + tautan unduh. */
    public function test_arsip_krs_muncul_setelah_pengajuan(): void
    {
        [$prodi, $t] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId(['user_id' => $u->id, 'nim' => '20209942', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'KA-1', 'nama' => 'KA', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        $ui = Livewire::actingAs($u)->test(Pengajuan::class, ['tahunId' => $t])
            ->set('kelasDipilih', [$kelas])
            ->call('ajukan')
            ->assertHasNoErrors();

        $this->assertStringContainsString('Arsip Pengajuan Saya', $ui->html());
        $this->assertStringContainsString(route('krs.cetak', Krs::where('mahasiswa_id', $mhs)->value('id')), $ui->html());
    }

    /** Buku wisuda CSV terunduh berisi peserta. */
    public function test_buku_wisuda_csv_terunduh(): void
    {
        $adm = $this->user('superadmin');
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2028', 'semester' => 'ganjil', 'aktif' => false]);
        $batch = WisudaBatch::create(['nama' => 'W1', 'periode' => '2028', 'status' => 'buka']);
        $mhsUser = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId(['user_id' => $mhsUser->id, 'nim' => '20209943', 'prodi_id' => DB::table('prodi')->insertGetId(['fakultas_id' => DB::table('fakultas')->insertGetId(['nama' => 'F', 'kode' => 'FW']), 'nama' => 'W', 'jenjang' => 'S1', 'kode' => 'WW', 'slug' => 'ww']), 'angkatan' => 2020, 'status' => 'lulus']);
        WisudaPeserta::create(['batch_id' => $batch->id, 'mahasiswa_id' => $mhs, 'no_kursi' => 'K-001', 'no_ijazah' => 'IJZ/2028/000001', 'pin_ijazah' => 'ABCD1234', 'status_toga' => 'belum']);

        Livewire::actingAs($adm)->test(Yudisium::class)
            ->set('batchId', $batch->id)
            ->call('eksporCsv')
            ->assertFileDownloaded();
    }

    /** Pesan pengingat merinci denda bila ada. */
    public function test_pesan_pengingat_merinci_denda(): void
    {
        [$prodi, $t] = $this->struktur();
        $tagihan = Tagihan::create([
            'mahasiswa_id' => DB::table('mahasiswa')->insertGetId([
                'user_id' => $this->user('mahasiswa')->id, 'nim' => '20209944',
                'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif',
            ]),
            'tahun_akademik_id' => $t, 'jenis' => 'UKT',
            'nominal' => 1000000, 'denda' => 150000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        $pesan = KeuanganService::pesanPengingat($tagihan->fresh(), 'h-7');
        $this->assertStringContainsString('1.150.000', $pesan);
        $this->assertStringContainsString('termasuk denda Rp 150.000', $pesan);
    }
}
