<?php

namespace Tests\Feature;

use App\Livewire\Dosen\WaliList;
use App\Livewire\Keuangan\Hutang;
use App\Livewire\Lms\Forum;
use App\Livewire\Rekrutmen\Kelola;
use App\Livewire\Rekrutmen\Lamar;
use App\Models\Aset;
use App\Models\Dosen;
use App\Models\ForumThread;
use App\Models\HutangDagang;
use App\Models\LamaranKerja;
use App\Models\LowonganKerja;
use App\Models\Pemasok;
use App\Models\User;
use App\Services\AsetService;
use App\Services\HutangService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave B Batch 4: M-025/P4, M-029, M-034, M-036, M-039.
 */
class WaveBBatch4Test extends TestCase
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

    /** M-025/P4: status KRS terbaru per mahasiswa tetap benar via subquery. */
    public function test_wali_krs_status_terbaru(): void
    {
        [$prodi, $t] = $this->struktur();
        $tLalu = DB::table('tahun_akademik')->insertGetId(['tahun' => '2025', 'semester' => 'genap', 'aktif' => false]);
        $dosenUser = $this->user('dosen');
        $dosen = Dosen::factory()->create(['user_id' => $dosenUser->id]);
        $mhsUser = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $mhsUser->id, 'nim' => '20260801', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif', 'dosen_pa_id' => $dosen->id,
        ]);

        DB::table('krs')->insert(['mahasiswa_id' => $mhs, 'tahun_akademik_id' => $tLalu, 'status' => 'diajukan']);
        DB::table('krs')->insert(['mahasiswa_id' => $mhs, 'tahun_akademik_id' => $t, 'status' => 'disetujui']);

        $view = Livewire::actingAs($dosenUser)->test(WaliList::class)->viewData('krsStatus');
        $this->assertSame('disetujui', $view[$mhs]);
    }

    /** M-029: thread + balas + kunci dosen memblokir balasan. */
    public function test_forum_thread_balasan_dan_kunci(): void
    {
        [$prodi, $t] = $this->struktur();
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'FR-1', 'nama' => 'Forum MK', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        $mhs = $this->user('mahasiswa');
        $dosen = $this->user('dosen');

        $this->actingAs($mhs)->get(route('lms.forum', ['kelas' => $kelas]))->assertOk();

        $ui = Livewire::actingAs($mhs)->test(Forum::class, ['kelasId' => $kelas])
            ->set('judul', 'Tanya UTS')
            ->set('isi', 'Kapan UTS dimulai?')
            ->call('buatThread')
            ->assertHasNoErrors();
        $threadId = ForumThread::where('judul', 'Tanya UTS')->value('id');
        $this->assertNotNull($threadId);

        $ui->set('threadId', $threadId)->set('balasan', 'Minggu depan.')->call('balas')->assertHasNoErrors();
        $this->assertDatabaseHas('forum_balasan', ['thread_id' => $threadId]);

        Livewire::actingAs($dosen)->test(Forum::class, ['kelasId' => $kelas])->call('kunci', $threadId)->assertHasNoErrors();

        Livewire::actingAs($mhs)->test(Forum::class, ['kelasId' => $kelas])
            ->set('threadId', $threadId)->set('balasan', 'Terima kasih.')
            ->call('balas')->assertHasErrors(['balasan']);

        Livewire::actingAs($mhs)->test(Forum::class, ['kelasId' => $kelas])->call('kunci', $threadId)->assertForbidden();
    }

    /** M-034: bayar utang bertahap + overpay dipangkas + lunas idempoten. */
    public function test_hutang_bayar_bertahap_dan_cap(): void
    {
        $this->struktur();
        $keu = $this->user('keuangan');
        $pem = Pemasok::create(['nama' => 'PT Kertas']);

        $ui = Livewire::actingAs($keu)->test(Hutang::class)
            ->set('pemasok_id', $pem->id)
            ->set('nomor', 'HUT-001')
            ->set('uraian', 'Belanja ATK')
            ->set('nominal', 1000000)
            ->call('simpan')
            ->assertHasNoErrors();
        $hutang = HutangDagang::where('nomor', 'HUT-001')->firstOrFail();

        $r = HutangService::bayar($hutang->id, 400000, $keu->id);
        $this->assertSame(['terbayar' => 400000, 'status' => 'sebagian', 'dibayar' => 400000], $r);

        $r = HutangService::bayar($hutang->id, 9999999, $keu->id);
        $this->assertSame(1000000, $r['terbayar']);
        $this->assertSame('lunas', $r['status']);
        $this->assertSame(600000, $r['dibayar']);

        $r = HutangService::bayar($hutang->id, 1000, $keu->id);
        $this->assertSame(0, $r['dibayar']);

        $ui->set('bayarNominal', 100)->call('bayar', $hutang->id)->assertHasNoErrors();
        $this->assertDatabaseHas('audit_logs', ['action' => 'hutang.dibayar', 'model_id' => $hutang->id]);
    }

    /** M-036: penyusutan garis lurus + idempoten bulanan + nilai buku ≥ 0. */
    public function test_penyusutan_garis_lurus_idempoten(): void
    {
        $aset = Aset::create([
            'kode' => 'AST-001', 'nama' => 'Laptop', 'kategori' => 'elektronik',
            'lokasi' => 'Lab 1', 'kondisi' => 'baik', 'tgl_beli' => '2026-01-05',
            'nilai' => 12000000, 'umur_manfaat_bulan' => 12,
        ]);

        $this->assertSame(1000000, AsetService::susutBulanan($aset));
        $this->assertSame(12000000, AsetService::nilaiBuku($aset));

        $this->assertSame(1000000, AsetService::susutkan($aset));
        $this->assertSame(0, AsetService::susutkan($aset->fresh()));
        $this->assertSame(11000000, AsetService::nilaiBuku($aset->fresh()));

        $this->artisan('aset:susutkan')->assertSuccessful();
        $this->assertSame(11000000, AsetService::nilaiBuku($aset->fresh()));
    }

    /** M-039: lowongan + lamaran publik idempoten + tutup menolak. */
    public function test_rekrutmen_lamaran_publik(): void
    {
        $adm = $this->user('admin_prodi');

        $this->get(route('publik.karier'))->assertOk();

        Livewire::actingAs($adm)->test(Kelola::class)
            ->set('judul', 'Dosen Informatika')
            ->set('jenis', 'dosen')
            ->set('kuota', 2)
            ->set('status', 'buka')
            ->call('simpan')
            ->assertHasNoErrors();

        $low = LowonganKerja::where('judul', 'Dosen Informatika')->firstOrFail();
        $this->assertTrue($low->masihBuka());

        $ui = Livewire::test(Lamar::class)
            ->set('nama', 'Pelamar Satu')
            ->set('email', 'satu@contoh.id')
            ->set('hp', '08123456789')
            ->call('kirim', $low->id)
            ->assertHasNoErrors();

        $ui->set('nama', 'Pelamar Satu')->set('email', 'satu@contoh.id')->set('hp', '08123456789')
            ->call('kirim', $low->id)->assertHasNoErrors();
        $this->assertSame(1, LamaranKerja::where('lowongan_id', $low->id)->count());

        $low->update(['status' => 'tutup']);
        Livewire::test(Lamar::class)
            ->set('nama', 'Pelamar Dua')->set('email', 'dua@contoh.id')->set('hp', '08123456780')
            ->call('kirim', $low->id)->assertHasErrors(['lowongan_id']);
    }
}
