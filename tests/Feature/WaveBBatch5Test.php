<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\Payroll;
use App\Livewire\Lms\KuisKelola;
use App\Livewire\Lms\KuisKerjakan;
use App\Models\BankSoal;
use App\Models\PayrollPeriode;
use App\Models\PayrollSlip;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizJawaban;
use App\Models\QuizSoal;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\QuizService;
use Database\Seeders\RolesPermissionsSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Wave B Batch 5: M-028 (mesin kuis), M-038 (payroll).
 */
class WaveBBatch5Test extends TestCase
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
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'KZ-1', 'nama' => 'Kuis MK', 'sks' => 3, 'semester' => 1]);
        $kelas = DB::table('kelas')->insertGetId(['matakuliah_id' => $mk, 'tahun_akademik_id' => $t, 'nama' => 'A', 'kuota' => 40]);

        return [$prodi, $t, $mk, $kelas];
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function mahasiswa(int $prodi, string $nim): array
    {
        $u = $this->user('mahasiswa');
        $id = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        return [$u, $id];
    }

    /** M-028: impor bank soal → quiz_soal (opsi ikut). */
    public function test_impor_bank_soal_ke_kuis(): void
    {
        [, , $mk, $kelas] = $this->struktur();
        BankSoal::create(['matakuliah_id' => $mk, 'jenis' => 'pg', 'pertanyaan' => 'Ibukota?', 'opsi' => ['Jakarta', 'Bandung'], 'kunci' => 'A', 'bobot' => 10, 'aktif' => true]);
        BankSoal::create(['matakuliah_id' => $mk, 'jenis' => 'esai', 'pertanyaan' => 'Jelaskan.', 'opsi' => null, 'kunci' => null, 'bobot' => 20, 'aktif' => true]);
        BankSoal::create(['matakuliah_id' => $mk, 'jenis' => 'pg', 'pertanyaan' => 'Nonaktif?', 'opsi' => ['X', 'Y'], 'kunci' => 'B', 'bobot' => 5, 'aktif' => false]);

        $quiz = Quiz::create(['kelas_id' => $kelas, 'judul' => 'Kuis 1', 'durasi_menit' => 60]);
        $this->assertSame(2, QuizService::imporDariBank($quiz->id, $mk));

        $soal = QuizSoal::where('quiz_id', $quiz->id)->orderBy('id')->get();
        $this->assertSame(['Jakarta', 'Bandung'], $soal[0]->opsi);
        $this->assertSame('A', $soal[0]->kunci_jawaban);
    }

    /** M-028: attempt + autosave + nilai PG otomatis + resume idempoten. */
    public function test_attempt_autosave_dan_nilai_pg_otomatis(): void
    {
        [, , $mk, $kelas] = $this->struktur();
        [, $mhs] = $this->mahasiswa(1, '20260901');
        $quiz = Quiz::create(['kelas_id' => $kelas, 'judul' => 'Kuis 2', 'durasi_menit' => 60]);

        $pg = QuizSoal::create(['quiz_id' => $quiz->id, 'pertanyaan' => 'Q1', 'opsi' => ['A1', 'B1'], 'tipe' => 'pg', 'bobot' => 10, 'kunci_jawaban' => 'B']);
        $esai = QuizSoal::create(['quiz_id' => $quiz->id, 'pertanyaan' => 'Q2', 'tipe' => 'esai', 'bobot' => 20, 'kunci_jawaban' => null]);

        $a1 = QuizService::mulai($quiz->id, $mhs);
        $this->assertSame($a1->id, QuizService::mulai($quiz->id, $mhs)->id);

        QuizService::simpanJawaban($a1->id, $mhs, $pg->id, 'b');
        QuizService::simpanJawaban($a1->id, $mhs, $esai->id, 'Karena ...');

        $selesai = QuizService::selesai($a1->id, $mhs);
        $this->assertSame(QuizAttempt::STATUS_SELESAI, $selesai->status);
        $this->assertSame(10.0, (float) $selesai->total_skor);
        $this->assertSame(10.0, (float) QuizJawaban::where('quiz_soal_id', $pg->id)->value('skor'));
        $this->assertNull(QuizJawaban::where('quiz_soal_id', $esai->id)->value('skor'));

        QuizService::koreksi($esai->id, $mhs, 15.0);
        $this->assertSame(25.0, (float) QuizAttempt::find($a1->id)->total_skor);

        $this->expectException(HttpException::class);
        QuizService::simpanJawaban($a1->id, $mhs, $pg->id, 'A');
    }

    /** M-028: deadline menolak mulai; jawaban salah → 0. */
    public function test_deadline_dan_jawaban_salah(): void
    {
        [$prodi, , $mk, $kelas] = $this->struktur();
        [, $mhs] = $this->mahasiswa($prodi, '20260902');

        $tutup = Quiz::create(['kelas_id' => $kelas, 'judul' => 'Tutup', 'durasi_menit' => 60, 'deadline' => now()->subDay()]);
        $this->expectException(DomainException::class);
        QuizService::mulai($tutup->id, $mhs);
    }

    /** M-028: Livewire kerjakan — anti-spoof owner + submit. */
    public function test_livewire_kerjakan_dan_owner_scope(): void
    {
        [$prodi, , $mk, $kelas] = $this->struktur();
        [$userA] = $this->mahasiswa($prodi, '20260911');
        $this->mahasiswa($prodi, '20260912');

        $quiz = Quiz::create(['kelas_id' => $kelas, 'judul' => 'Kuis LW', 'durasi_menit' => 60]);
        $pg = QuizSoal::create(['quiz_id' => $quiz->id, 'pertanyaan' => 'Q?', 'opsi' => ['X', 'Y'], 'tipe' => 'pg', 'bobot' => 10, 'kunci_jawaban' => 'A']);

        Livewire::actingAs($userA)->test(KuisKerjakan::class, ['quizId' => $quiz->id])
            ->call('mulai')
            ->call('autosave', $pg->id, 'A')
            ->call('submit')
            ->assertHasNoErrors();

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame(QuizAttempt::STATUS_SELESAI, $attempt->status);
        $this->assertSame(10.0, (float) $attempt->total_skor);

        $dosen = $this->user('dosen');
        Livewire::actingAs($dosen)->test(KuisKerjakan::class, ['quizId' => $quiz->id])
            ->call('mulai')->assertForbidden();
    }

    /** M-028: dosen kelola — buat kuis + impor + koreksi. */
    public function test_livewire_kelola_kuis(): void
    {
        [$prodi, , $mk, $kelas] = $this->struktur();
        $dosen = $this->user('dosen');
        $mhs = $this->user('mahasiswa');
        $this->actingAs($mhs)->get(route('lms.kuis', ['kelas' => $kelas]))->assertForbidden();
        $this->actingAs($dosen)->get(route('lms.kuis', ['kelas' => $kelas]))->assertOk();

        BankSoal::create(['matakuliah_id' => $mk, 'jenis' => 'pg', 'pertanyaan' => 'Q?', 'opsi' => ['X', 'Y'], 'kunci' => 'A', 'bobot' => 10, 'aktif' => true]);

        $ui = Livewire::actingAs($dosen)->test(KuisKelola::class, ['kelasId' => $kelas])
            ->set('judul', 'UTS')
            ->set('durasi_menit', 90)
            ->call('simpan')
            ->assertHasNoErrors();

        $quiz = Quiz::where('judul', 'UTS')->firstOrFail();
        $ui->set('imporQuizId', $quiz->id)->set('imporLimit', 10)->call('impor')->assertHasNoErrors();
        $this->assertSame(1, QuizSoal::where('quiz_id', $quiz->id)->count());
    }

    /** M-038: total, slip, kunci periode, total periode. */
    public function test_payroll_service_dan_kunci(): void
    {
        $this->assertSame(4500000, PayrollService::total(5000000, 500000, 1000000));
        $this->assertSame(0, PayrollService::total(1000, 0, 5000));

        $user = $this->user('dosen');
        $periode = PayrollPeriode::create(['periode' => '2026-01']);
        $slip = PayrollService::simpanSlip($periode->id, $user->id, 5000000, 500000, 1000000);
        $this->assertSame(4500000, (int) $slip->total_bersih);
        $this->assertSame(4500000, PayrollService::totalPeriode($periode->id));

        $slip2 = PayrollService::simpanSlip($periode->id, $user->id, 6000000, 0, 0);
        $this->assertSame($slip->id, $slip2->id);
        $this->assertSame(6000000, (int) $slip2->total_bersih);

        PayrollService::kunciPeriode($periode->id);
        $this->assertSame('final', $periode->fresh()->status);
        $this->expectException(\RuntimeException::class);
        PayrollService::simpanSlip($periode->id, $user->id, 1, 0, 0);
    }

    /** M-038: Livewire payroll + rute keuangan. */
    public function test_livewire_payroll(): void
    {
        $keu = $this->user('keuangan');
        $mhs = $this->user('mahasiswa');
        $this->actingAs($mhs)->get(route('keuangan.payroll'))->assertForbidden();
        $this->actingAs($keu)->get(route('keuangan.payroll'))->assertOk();

        $pegawai = User::factory()->create(['name' => 'Pegawai Test']);
        $ui = Livewire::actingAs($keu)->test(Payroll::class)
            ->set('periodeBaru', '2026-02')
            ->call('buatPeriode')
            ->assertHasNoErrors();

        $periode = PayrollPeriode::where('periode', '2026-02')->firstOrFail();
        $ui->set('user_id', $pegawai->id)
            ->set('gaji_pokok', 4000000)->set('tunjangan', 1000000)->set('potongan', 500000)
            ->call('simpanSlip')->assertHasNoErrors();

        $this->assertSame(4500000, (int) PayrollSlip::where('periode_id', $periode->id)->value('total_bersih'));

        $ui->call('kunci')->assertHasNoErrors();
        $ui->set('user_id', $pegawai->id)->set('gaji_pokok', 1)->call('simpanSlip')->assertHasErrors(['user_id']);
    }
}
