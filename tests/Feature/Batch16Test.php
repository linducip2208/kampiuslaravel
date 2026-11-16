<?php

namespace Tests\Feature;

use App\Livewire\Obe\Kelola;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\PayrollPeriode;
use App\Models\PayrollSlip;
use App\Models\SubCpmk;
use App\Models\User;
use App\Services\Ai\RagGuardrail;
use App\Services\Ai\RagService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 16 ide bank: RAG per prodi, cetak slip gaji, sub-CPMK.
 */
class Batch16Test extends TestCase
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

    /** Dokumen RAG ber-prodi hanya relevan untuk prodi yang sama (+ global). */
    public function test_rag_menyaring_sumber_per_prodi(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTF']);
        $prodiA = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'A', 'jenjang' => 'S1', 'kode' => 'PA', 'slug' => 'pa']);
        $prodiB = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'B', 'jenjang' => 'S1', 'kode' => 'PB', 'slug' => 'pb']);

        RagService::ingest('Panduan Umum', 'Mahasiswa wajib mengisi krs setiap semester berjalan aktif.');
        RagService::ingest('Kurikulum Prodi A', 'Prodi A mewajibkan magang industri semester enam.', null, (int) $prodiA);

        $untukA = RagGuardrail::relevan('magang industri semester', 5, (int) $prodiA);
        $untukB = RagGuardrail::relevan('magang industri semester', 5, (int) $prodiB);

        $this->assertNotEmpty($untukA);
        $this->assertContains('Kurikulum Prodi A', array_column($untukA, 'judul'));

        foreach ($untukB as $r) {
            $this->assertNotSame('Kurikulum Prodi A', $r['judul']);
        }
    }

    /** Slip gaji tercetak dengan tombol dari daftar. */
    public function test_slip_gaji_tercetak(): void
    {
        $pegawai = $this->user('dosen');
        $periode = PayrollPeriode::create(['periode' => '2027-01']);
        $slip = PayrollSlip::create([
            'periode_id' => $periode->id, 'user_id' => $pegawai->id,
            'gaji_pokok' => 5000000, 'tunjangan' => 1000000, 'potongan' => 200000,
            'total_bersih' => 5800000, 'status' => 'draft',
        ]);

        $this->actingAs($this->user('superadmin'))->get(route('keuangan.slip.cetak', $slip->id))
            ->assertOk()
            ->assertSee($pegawai->name)
            ->assertSee('5.800.000');
    }

    /** Sub-CPMK tersimpan di bawah CPMK induk + tampil di daftar. */
    public function test_sub_cpmk_tersimpan(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTG']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PG', 'slug' => 'pg']);
        $cpl = Cpl::create(['prodi_id' => $prodi, 'kode' => 'CPL-1', 'deskripsi' => 'Sikap']);
        $mk = DB::table('matakuliah')->insertGetId(['prodi_id' => $prodi, 'kode' => 'OB-1', 'nama' => 'OB', 'sks' => 3, 'semester' => 1]);
        $cpmk = Cpmk::create(['matakuliah_id' => $mk, 'cpl_id' => $cpl->id, 'kode' => 'CPMK-1', 'deskripsi' => 'Mampu X', 'bobot' => 1]);

        $html = Livewire::actingAs($this->user('superadmin'))->test(Kelola::class)
            ->set('sub_cpmk_id', $cpmk->id)
            ->set('sub_kode', 'SUB-1')
            ->set('sub_deskripsi', 'Mampu X.1')
            ->set('sub_bobot', 0.5)
            ->call('simpanSubCpmk')
            ->assertHasNoErrors()
            ->html();

        $this->assertTrue(SubCpmk::where('cpmk_id', $cpmk->id)->where('kode', 'SUB-1')->exists());
        $this->assertStringContainsString('SUB-1', $html);
    }
}
