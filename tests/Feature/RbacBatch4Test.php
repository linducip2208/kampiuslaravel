<?php

namespace Tests\Feature;

use App\Livewire\Admin\Delegasi;
use App\Livewire\Admin\UserData;
use App\Livewire\Obe\Kelola;
use App\Livewire\Sidang\Pendaftaran;
use App\Models\Cpl;
use App\Models\DelegasiAkses;
use App\Models\Dosen;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\KeuanganService;
use App\Services\WisudaService;
use Carbon\Carbon;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RBAC batch 4: delegasi berbatas waktu (+ekspirasi aman), review higiene,
 * scoping OBE per prodi, audit baca dokumen, pesan denda, sidang vs kuliah,
 * syarat wisuda. Delegasi/ekspirasi manual = batch berikut (bila dibutuhkan UI).
 */
class RbacBatch4Test extends TestCase
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

    private static int $periodeSeq = 0;

    private function prodi(string $kode): int
    {
        self::$periodeSeq++;
        $sem = self::$periodeSeq % 2 === 0 ? 'genap' : 'ganjil';
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT'.$kode]);
        DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => $sem, 'aktif' => $sem === 'ganjil']);

        return [$fak, DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'I', 'jenjang' => 'S1', 'kode' => $kode, 'slug' => strtolower($kode)])][1];
    }

    /** Delegasi aktif memberi peran; kedaluwarsa mencabut; grant manual dilindungi. */
    public function test_delegasi_diberi_dan_dicabut_aman(): void
    {
        $a = $this->user('superadmin');
        $b = $this->user('dosen');

        DelegasiAkses::create([
            'dari_user_id' => $a->id, 'ke_user_id' => $b->id, 'role' => 'keuangan',
            'mulai_at' => now()->subDay()->toDateString(),
            'sampai_at' => now()->addDay()->toDateString(),
        ]);

        Artisan::call('delegasi:sinkron');
        $this->assertTrue($b->fresh()->hasRole('keuangan'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'delegasi.diterapkan']);

        // Idempoten: jalan lagi tidak duplikat/ganda.
        Artisan::call('delegasi:sinkron');
        $this->assertTrue($b->fresh()->hasRole('keuangan'));

        // Kedaluwarsa → dicabut + diaudit.
        DelegasiAkses::query()->update(['sampai_at' => now()->subDay()->toDateString()]);
        Artisan::call('delegasi:sinkron');
        $this->assertFalse($b->fresh()->hasRole('keuangan'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'delegasi.dicabut']);
    }

    /** Grant manual sesudah delegasi melindungi dari pencabutan otomatis. */
    public function test_grant_manual_melindungi_dari_pencabutan(): void
    {
        $a = $this->user('superadmin');
        $b = $this->user('dosen');

        DelegasiAkses::create([
            'dari_user_id' => $a->id, 'ke_user_id' => $b->id, 'role' => 'keuangan',
            'mulai_at' => now()->subDays(5)->toDateString(),
            'sampai_at' => now()->addDay()->toDateString(),
        ]);
        Artisan::call('delegasi:sinkron');
        $this->assertTrue($b->fresh()->hasRole('keuangan'));

        // Admin memberi peran sama secara manual (tercatat user.role.diubah).
        Livewire::actingAs($a)->test(UserData::class)
            ->call('edit', $b->id)
            ->set('form.role', 'keuangan')
            ->call('simpan');

        DelegasiAkses::query()->update(['sampai_at' => now()->subDay()->toDateString()]);
        Artisan::call('delegasi:sinkron');

        $this->assertTrue($b->fresh()->hasRole('keuangan'));
    }

    /** Review higiene jalan dan melaporkan. */
    public function test_permission_review_berjalan(): void
    {
        $this->artisan('permission:review')->assertSuccessful();
    }

    /** OBE: kaprodi terkunci prodinya; lintas prodi tak terlihat. */
    public function test_obe_terkunci_prodi_kaprodi(): void
    {
        $prodiA = $this->prodi('OBA');
        $prodiB = $this->prodi('OBB');
        $kaprodi = $this->user('kaprodi');
        Dosen::factory()->create(['user_id' => $kaprodi->id, 'prodi_id' => $prodiA]);

        Cpl::create(['prodi_id' => $prodiA, 'kode' => 'CPL-A', 'deskripsi' => 'A']);
        Cpl::create(['prodi_id' => $prodiB, 'kode' => 'CPL-B', 'deskripsi' => 'B']);

        $html = Livewire::actingAs($kaprodi)->test(Kelola::class)->assertOk()->html();
        $this->assertStringContainsString('CPL-A', $html);
        $this->assertStringNotContainsString('CPL-B', $html);

        // Rekap NIM prodi lain → null (bukan bocor silang).
        $mhsB = DB::table('mahasiswa')->insertGetId([
            'user_id' => User::factory()->create()->id, 'nim' => '20209931',
            'prodi_id' => $prodiB, 'angkatan' => 2026, 'status' => 'aktif',
        ]);
        $view = Livewire::actingAs($kaprodi)->test(Kelola::class)
            ->set('nimRekap', '20209931')->viewData('rekap');
        $this->assertNull($view);
    }

    /** Pesan pengingat merinci denda bila ada. */
    public function test_pesan_pengingat_merinci_denda(): void
    {
        $tagihan = Tagihan::create([
            'mahasiswa_id' => DB::table('mahasiswa')->insertGetId([
                'user_id' => $this->user('mahasiswa')->id, 'nim' => '20209932',
                'prodi_id' => $this->prodi('OBC'), 'angkatan' => 2026, 'status' => 'aktif',
            ]),
            'tahun_akademik_id' => DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'genap', 'aktif' => false]),
            'jenis' => 'UKT', 'nominal' => 1000000, 'terbayar' => 0, 'status' => 'belum_bayar',
            'denda' => 50000,
        ]);

        $this->assertStringContainsString('50.000', KeuanganService::pesanPengingat($tagihan->fresh(), 'h-7'));
    }

    /** Sidang ditolak bila ruang dipakai kuliah. */
    public function test_sidang_tolak_bentrok_kuliah(): void
    {
        $prodi = $this->prodi('OBD');
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => false]);
        $ruang = DB::table('ruang')->insertGetId(['gedung' => 'G', 'nama' => 'R1', 'kapasitas' => 40]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'SB-1', 'nama' => 'SB', 'sks' => 3, 'semester' => 1]);

        // Senin 07:00–09:30 dipakai kuliah.
        $senin = Carbon::now('Asia/Jakarta')->next(Carbon::MONDAY)->format('Y-m-d');
        DB::table('kelas')->insert([
            'matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A',
            'kuota' => 40, 'ruang_id' => $ruang, 'hari' => 'Senin',
            'jam_mulai' => '07:00:00', 'jam_selesai' => '09:30:00',
        ]);

        $mhsUser = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $mhsUser->id, 'nim' => '20209933', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
        $dosen = Dosen::factory()->create();
        $judul = SkripsiJudul::create(['mahasiswa_id' => $mhs, 'judul' => 'TA', 'status' => 'disetujui', 'pembimbing1_id' => $dosen->id]);

        $ui = Livewire::actingAs($mhsUser)->test(Pendaftaran::class)
            ->set('judul_id', $judul->id)
            ->set('jenis', 'proposal')
            ->set('tanggal', $senin.' 08:00')
            ->set('ruang_id', $ruang)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Pendaftaran ditolak: ruang dipakai jadwal kuliah pada jam tersebut.', $ui->get('error'));
        $this->assertSame(0, Sidang::where('judul_id', $judul->id)->count());
    }

    /** Wisuda: cek syarat memetakan kondisi nyata. */
    public function test_wisuda_cek_syarat(): void
    {
        $prodi = $this->prodi('OBE');
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2028', 'semester' => 'ganjil', 'aktif' => false]);
        $u = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20209934', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'WS-1', 'nama' => 'WS', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        DB::table('nilai')->insert(['mahasiswa_id' => $mhs, 'kelas_id' => $kelas, 'bobot' => 4.0, 'is_locked' => true]);
        $judul = SkripsiJudul::create(['mahasiswa_id' => $mhs, 'judul' => 'TA', 'status' => 'lulus']);
        Sidang::create(['judul_id' => $judul->id, 'jenis' => 'tutup', 'tanggal' => now()->addDay(), 'status' => 'lulus']);

        $hasil = WisudaService::cekSyarat($mhs);
        $this->assertTrue($hasil['lolos']);
        $this->assertSame(4, count($hasil['items']));
    }
}
