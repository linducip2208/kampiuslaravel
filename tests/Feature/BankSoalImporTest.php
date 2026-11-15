<?php

namespace Tests\Feature;

use App\Livewire\Lms\BankSoalImpor;
use App\Models\BankSoal as BankSoalModel;
use App\Models\Matakuliah;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class BankSoalImporTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function dosen(): User
    {
        $u = User::factory()->create();
        $u->assignRole('dosen');

        return $u;
    }

    public function test_csv_valid_tiga_baris_masuk_db(): void
    {
        $mk = Matakuliah::factory()->create();

        $csv = implode("\n", [
            'pertanyaan,jenis,opsi,kunci,bobot',
            'Ibukota Indonesia?,pg,Jakarta|Bandung|Surabaya,A,2',
            'Dua tambah dua?,pg,3|4|5,B,1',
            'Jelaskan normalisasi database.,esai,,,3',
        ]);
        $file = UploadedFile::fake()->createWithContent('soal.csv', $csv);

        Livewire::actingAs($this->dosen())
            ->test(BankSoalImpor::class, ['matakuliahId' => $mk->id])
            ->set('fileCsv', $file)
            ->call('impor')
            ->assertHasNoErrors()
            ->assertSee('3 masuk, 0 gagal');

        $this->assertDatabaseCount('bank_soals', 3);
        $this->assertDatabaseHas('bank_soals', [
            'matakuliah_id' => $mk->id, 'jenis' => 'pg', 'pertanyaan' => 'Ibukota Indonesia?',
            'kunci' => 'A', 'bobot' => 2,
        ]);
        $this->assertSame(['Jakarta', 'Bandung', 'Surabaya'], BankSoalModel::where('pertanyaan', 'Ibukota Indonesia?')->first()->opsi);
        $this->assertDatabaseHas('bank_soals', ['matakuliah_id' => $mk->id, 'jenis' => 'esai', 'kunci' => null, 'bobot' => 3]);
        $this->assertNull(BankSoalModel::where('jenis', 'esai')->first()->opsi);
    }

    public function test_baris_rusak_ditolak_dengan_laporan_valid_tetap_masuk(): void
    {
        $mk = Matakuliah::factory()->create();

        $csv = implode("\n", [
            'pertanyaan;jenis;opsi;kunci;bobot',
            'Soal valid?;pg;Ya|Tidak;A;1',
            'Soal opsi satu?;pg;Hanya satu;A;1',
            ';pg;Ya|Tidak;A;1',
        ]);
        $file = UploadedFile::fake()->createWithContent('soal.csv', $csv);

        $component = Livewire::actingAs($this->dosen())
            ->test(BankSoalImpor::class, ['matakuliahId' => $mk->id])
            ->set('fileCsv', $file)
            ->call('impor')
            ->assertHasNoErrors()
            ->assertSee('1 masuk, 2 gagal');

        $this->assertDatabaseCount('bank_soals', 1);
        $this->assertDatabaseHas('bank_soals', ['pertanyaan' => 'Soal valid?']);

        $hasil = $component->get('hasil');
        $this->assertSame(1, $hasil['masuk']);
        $this->assertSame(2, $hasil['gagal']);
        $this->assertCount(2, $hasil['errors']);
    }

    public function test_mahasiswa_ditolak_akses_impor(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        Livewire::actingAs($mhs)->test(BankSoalImpor::class)->assertForbidden();
    }
}
