<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\Gl;
use App\Livewire\Obe\Kelola;
use App\Models\Coa;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\CpmkAsesmen;
use App\Models\Quiz;
use App\Models\QuizSoal;
use App\Models\TugasKumpul;
use App\Models\TutupBuku;
use App\Models\User;
use App\Services\GlService;
use App\Services\ObeService;
use App\Services\QuizService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave C Batch 3: M-049 (OBE), M-052 (GL penuh).
 */
class WaveCBatch3Test extends TestCase
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
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'OBE-1', 'nama' => 'OBE MK', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        return [$prodi, $t, $mk, $kelas];
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    /** M-049: ketercapaian CPL dari kuis + tugas. */
    public function test_obe_ketercapaian_dari_kuis_dan_tugas(): void
    {
        [$prodi, $t, $mk, $kelas] = $this->struktur();
        $u = $this->user('mahasiswa');
        $mhs = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '20201401', 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        $cpl = Cpl::create(['prodi_id' => $prodi, 'kode' => 'CPL-1', 'deskripsi' => 'Menguasai konsep']);
        $cpmk = Cpmk::create(['matakuliah_id' => $mk, 'cpl_id' => $cpl->id, 'kode' => 'CPMK-1', 'deskripsi' => 'Memahami dasar', 'bobot' => 2]);

        $quiz = Quiz::create(['kelas_id' => $kelas, 'judul' => 'Kuis OBE', 'durasi_menit' => 60]);
        $soal = QuizSoal::create(['quiz_id' => $quiz->id, 'pertanyaan' => 'Q', 'tipe' => 'pg', 'bobot' => 10, 'kunci_jawaban' => 'A']);
        CpmkAsesmen::create(['cpmk_id' => $cpmk->id, 'sumber_tipe' => 'quiz_soal', 'sumber_id' => $soal->id, 'bobot' => 1]);

        $tugasId = DB::table('tugas')->insertGetId(['kelas_id' => $kelas, 'judul' => 'T1']);
        CpmkAsesmen::create(['cpmk_id' => $cpmk->id, 'sumber_tipe' => 'tugas', 'sumber_id' => $tugasId, 'bobot' => 1]);
        TugasKumpul::create(['tugas_id' => $tugasId, 'mahasiswa_id' => $mhs, 'nilai' => 80]);

        $attempt = QuizService::mulai($quiz->id, $mhs);
        QuizService::simpanJawaban($attempt->id, $mhs, $soal->id, 'A');
        QuizService::selesai($attempt->id, $mhs);

        $hasil = ObeService::ketercapaian($mhs, $prodi);
        // Kuis 100% + tugas 80% → CPMK 90%, CPL-1 90%, keseluruhan 90%.
        $this->assertSame(90.0, $hasil['per_cpmk']['CPMK-1']);
        $this->assertSame(90.0, $hasil['per_cpl']['CPL-1']);
        $this->assertSame(90.0, $hasil['keseluruhan']);

        // Tanpa data → nol, bukan error.
        $kosong = ObeService::ketercapaian(999999, $prodi);
        $this->assertSame(0.0, $kosong['keseluruhan']);
    }

    /** M-049: Livewire CRUD CPL/CPMK/pemetaan + guard route. */
    public function test_obe_livewire_dan_route(): void
    {
        [$prodi, , $mk] = $this->struktur();
        $adm = $this->user('admin_prodi');
        $mhs = $this->user('mahasiswa');

        $this->actingAs($mhs)->get(route('admin.obe'))->assertForbidden();
        $this->actingAs($adm)->get(route('admin.obe'))->assertOk();

        $ui = Livewire::actingAs($adm)->test(Kelola::class)
            ->set('prodi_id', $prodi)
            ->set('cpl_kode', 'CPL-9')
            ->set('cpl_deskripsi', 'Sikap profesional')
            ->call('simpanCpl')
            ->assertHasNoErrors();

        $cpl = Cpl::where('kode', 'CPL-9')->firstOrFail();

        $ui->set('mk_id', $mk)->set('cpl_id', $cpl->id)
            ->set('cpmk_kode', 'CPMK-9')->set('cpmk_deskripsi', 'Disiplin')
            ->call('simpanCpmk')->assertHasNoErrors();

        $cpmk = Cpmk::where('kode', 'CPMK-9')->firstOrFail();

        $ui->set('asesmen_cpmk_id', $cpmk->id)->set('asesmen_tipe', 'tugas')
            ->set('asesmen_sumber_id', 7)->call('petakan')->assertHasNoErrors();

        $this->assertDatabaseHas('cpmk_asesmen', ['cpmk_id' => $cpmk->id, 'sumber_tipe' => 'tugas', 'sumber_id' => 7]);
    }

    /** M-052: posting berimbang + tolak timpang + buku besar + neraca. */
    public function test_gl_posting_dan_neraca(): void
    {
        [, $t] = $this->struktur();
        $this->assertSame(8, GlService::seederDasarCoa());

        $kas = Coa::where('kode', '1000')->value('id');
        $ekuitas = Coa::where('kode', '3000')->value('id');

        $jurnal = GlService::posting($t, '2026-01-05', 'Modal awal', [
            ['coa_id' => $kas, 'debet' => 10000000, 'kredit' => 0],
            ['coa_id' => $ekuitas, 'debet' => 0, 'kredit' => 10000000],
        ], 'MODAL-001');

        $this->assertSame(10000000, $jurnal->totalDebet());
        $this->assertSame(10000000, $jurnal->totalKredit());

        $buku = GlService::bukuBesar($kas, $t);
        $this->assertSame(10000000, $buku['saldo_akhir']);
        $this->assertCount(1, $buku['rows']);

        $neraca = GlService::neracaSaldo($t);
        $this->assertTrue($neraca['seimbang']);
        $this->assertSame(10000000, $neraca['total_debet']);
        $this->assertSame(10000000, $neraca['total_kredit']);

        // Timpang ditolak.
        try {
            GlService::posting($t, '2026-01-06', 'Timpang', [
                ['coa_id' => $kas, 'debet' => 100, 'kredit' => 0],
                ['coa_id' => $ekuitas, 'debet' => 0, 'kredit' => 90],
            ]);
            $this->fail('Jurnal timpang harus ditolak.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('seimbang', $e->getMessage());
        }

        // Dua sisi dalam 1 baris ditolak.
        try {
            GlService::posting($t, '2026-01-06', 'Dobel', [
                ['coa_id' => $kas, 'debet' => 100, 'kredit' => 100],
                ['coa_id' => $ekuitas, 'debet' => 0, 'kredit' => 0],
            ]);
            $this->fail('Baris dua sisi harus ditolak.');
        } catch (\InvalidArgumentException) {
        }

        // Periode tutup menolak posting.
        TutupBuku::create(['tahun_akademik_id' => $t, 'status' => 'tutup']);
        $this->expectException(\RuntimeException::class);
        GlService::posting($t, '2026-01-07', 'Tutup', [
            ['coa_id' => $kas, 'debet' => 50, 'kredit' => 0],
            ['coa_id' => $ekuitas, 'debet' => 0, 'kredit' => 50],
        ]);
    }

    /** M-052: Livewire GL + guard route keuangan. */
    public function test_gl_livewire_dan_route(): void
    {
        [, $t] = $this->struktur();
        $keu = $this->user('keuangan');
        $mhs = $this->user('mahasiswa');

        $this->actingAs($mhs)->get(route('keuangan.gl'))->assertForbidden();
        $this->actingAs($keu)->get(route('keuangan.gl'))->assertOk();

        GlService::seederDasarCoa();
        $kas = Coa::where('kode', '1000')->value('id');
        $beban = Coa::where('kode', '5000')->value('id');

        Livewire::actingAs($keu)->test(Gl::class)
            ->set('tahunId', $t)
            ->set('tanggal', '2026-02-01')
            ->set('keterangan', 'Bayar listrik')
            ->set('baris', [
                ['coa_id' => $beban, 'debet' => 500000, 'kredit' => 0],
                ['coa_id' => $kas, 'debet' => 0, 'kredit' => 500000],
            ])
            ->call('posting')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jurnal_umum', ['keterangan' => 'Bayar listrik']);
        $this->assertTrue(GlService::neracaSaldo($t)['seimbang']);
    }
}
