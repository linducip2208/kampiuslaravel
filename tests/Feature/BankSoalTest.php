<?php

namespace Tests\Feature;

use App\Livewire\Lms\BankSoal;
use App\Models\BankSoal as BankSoalModel;
use App\Models\Matakuliah;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankSoalTest extends TestCase
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

    public function test_dosen_bisa_simpan_soal_pg_valid(): void
    {
        $mk = Matakuliah::factory()->create();

        Livewire::actingAs($this->dosen())
            ->test(BankSoal::class)
            ->set('matakuliah_id', $mk->id)
            ->set('jenis', 'pg')
            ->set('pertanyaan', 'Ibukota Indonesia?')
            ->set('opsi', ['Jakarta', 'Bandung', 'Surabaya', 'Medan'])
            ->set('kunci', 'a')
            ->set('bobot', 2)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('Soal tersimpan');

        $this->assertDatabaseHas('bank_soals', [
            'matakuliah_id' => $mk->id, 'jenis' => 'pg', 'kunci' => 'A', 'bobot' => 2,
        ]);
    }

    public function test_pg_minimal_dua_opsi_dan_kunci_valid(): void
    {
        $mk = Matakuliah::factory()->create();

        Livewire::actingAs($this->dosen())
            ->test(BankSoal::class)
            ->set('matakuliah_id', $mk->id)
            ->set('jenis', 'pg')
            ->set('pertanyaan', 'Satu tambah satu?')
            ->set('opsi', ['Dua', '', '', ''])
            ->set('kunci', 'Z')
            ->call('simpan')
            ->assertHasErrors(['opsi', 'kunci']);

        $this->assertDatabaseCount('bank_soals', 0);
    }

    public function test_esai_tanpa_opsi_boleh_disimpan(): void
    {
        $mk = Matakuliah::factory()->create();

        Livewire::actingAs($this->dosen())
            ->test(BankSoal::class)
            ->set('matakuliah_id', $mk->id)
            ->set('jenis', 'esai')
            ->set('pertanyaan', 'Jelaskan konsep normalisasi database.')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bank_soals', ['matakuliah_id' => $mk->id, 'jenis' => 'esai']);
        $this->assertNull(BankSoalModel::first()->opsi);
    }

    public function test_mahasiswa_ditolak_akses_bank_soal(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        $this->actingAs($mhs)->get(route('lms.bank'))->assertForbidden();
        Livewire::actingAs($mhs)->test(BankSoal::class)->assertForbidden();
    }

    public function test_filter_dan_hapus_berjalan(): void
    {
        $mkA = Matakuliah::factory()->create(['kode' => 'TIF901']);
        $mkB = Matakuliah::factory()->create(['kode' => 'TIF902']);
        $a = BankSoalModel::create([
            'matakuliah_id' => $mkA->id, 'jenis' => 'pg', 'pertanyaan' => 'Soal alfa unik?',
            'opsi' => ['Ya', 'Tidak'], 'kunci' => 'A', 'bobot' => 1, 'aktif' => true,
        ]);
        BankSoalModel::create([
            'matakuliah_id' => $mkB->id, 'jenis' => 'esai', 'pertanyaan' => 'Soal beta unik?',
            'opsi' => null, 'kunci' => null, 'bobot' => 1, 'aktif' => true,
        ]);

        Livewire::actingAs($this->dosen())
            ->test(BankSoal::class)
            ->set('filterMk', $mkA->id)
            ->assertSee('Soal alfa unik?')
            ->assertDontSee('Soal beta unik?')
            ->call('hapus', $a->id)
            ->assertSee('Soal dihapus');

        $this->assertDatabaseMissing('bank_soals', ['id' => $a->id]);
    }
}
