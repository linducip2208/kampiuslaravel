<?php

namespace Tests\Feature;

use App\Livewire\Krs\Pengajuan;
use App\Livewire\Lms\BankSoal;
use App\Livewire\Lms\MateriCrud;
use App\Models\BankSoal as BankSoalModel;
use App\Models\KrsRevisi;
use App\Models\Materi;
use App\Models\Pertemuan;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 9 ide bank: riwayat revisi KRS, pratinjau PDF sebaris,
 * klon bank soal lintas MK.
 */
class Batch9Test extends TestCase
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
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT9']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'K9', 'slug' => 'k9']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);

        return [$prodi, $t];
    }

    /** Submit ulang KRS menyisakan snapshot revisi berisi state lama. */
    public function test_revisi_krs_menyimpan_snapshot(): void
    {
        [$prodi, $t] = $this->struktur();
        $u = $this->user('mahasiswa');
        DB::table('mahasiswa')->insert(['user_id' => $u->id, 'nim' => '20209971', 'prodi_id' => $prodi, 'angkatan' => 2026, 'status' => 'aktif']);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'KR-1', 'nama' => 'KR', 'sks' => 3, 'semester' => 1]);
        $kelasA = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        $kelasB = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'B', 'kuota' => 40]);

        $ui = Livewire::actingAs($u)->test(Pengajuan::class, ['tahunId' => $t])
            ->set('kelasDipilih', [$kelasA])
            ->call('ajukan')
            ->assertHasNoErrors();
        $this->assertSame(0, KrsRevisi::count());

        $ui->set('kelasDipilih', [$kelasB])->call('ajukan')->assertHasNoErrors();

        $this->assertSame(1, KrsRevisi::count());
        $rev = KrsRevisi::first();
        $this->assertSame(3, (int) $rev->total_sks);
        $this->assertSame($kelasA, (int) $rev->rincian[0]['kelas_id']);
        $this->assertStringContainsString('Riwayat Revisi', $ui->html());
    }

    /** Pratinjau PDF tampil sebaris tanpa unduh. */
    public function test_pratinjau_pdf_sebaris(): void
    {
        [$prodi, $t] = $this->struktur();
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'LM-1', 'nama' => 'LM', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);
        $pt = Pertemuan::create(['kelas_id' => $kelas, 'ke' => 1, 'qr_token' => 'QR9-'.uniqid()]);
        $m = Materi::create(['pertemuan_id' => $pt->id, 'judul' => 'Modul 1', 'file_path' => 'materi/modul1.pdf']);

        $html = Livewire::actingAs($this->user('superadmin'))->test(MateriCrud::class, ['pertemuanId' => $pt->id])
            ->call('pratinjau', $m->id)
            ->html();

        $this->assertStringContainsString('<embed', $html);
        $this->assertStringContainsString('modul1.pdf', $html);
    }

    /** Klon soal ke MK lain menyalin isi + kunci. */
    public function test_klon_soal_lintas_mk(): void
    {
        [$prodi] = $this->struktur();
        $mk1 = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'BK-1', 'nama' => 'BK1', 'sks' => 3, 'semester' => 1]);
        $mk2 = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'BK-2', 'nama' => 'BK2', 'sks' => 3, 'semester' => 1]);

        $soal = BankSoalModel::create([
            'matakuliah_id' => $mk1, 'jenis' => 'pg', 'pertanyaan' => '2+2=?',
            'opsi' => ['3', '4'], 'kunci' => 'B', 'bobot' => 5, 'aktif' => true,
        ]);

        Livewire::actingAs($this->user('superadmin'))->test(BankSoal::class)
            ->set('klonTujuanMk', $mk2)
            ->call('klon', $soal->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bank_soals', [
            'matakuliah_id' => $mk2, 'pertanyaan' => '2+2=?', 'kunci' => 'B', 'bobot' => 5,
        ]);
        $this->assertSame(2, BankSoalModel::count());
    }
}
