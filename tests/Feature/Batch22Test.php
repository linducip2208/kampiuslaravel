<?php

namespace Tests\Feature;

use App\Livewire\Admin\Akreditasi;
use App\Models\SpmiSiklus;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 22 ide bank: siklus PPEMPMTI + stylesheet cetak rapor.
 */
class Batch22Test extends TestCase
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

    /** Tahap siklus tersimpan idempoten + tampil di daftar. */
    public function test_siklus_ppempmt_i_tersimpan(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTR']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PR', 'slug' => 'pr']);

        $html = Livewire::actingAs($this->user('superadmin'))->test(Akreditasi::class)
            ->set('siklusProdiId', $prodi)
            ->set('siklusTahun', 2027)
            ->set('siklusTahap', 'penetapan')
            ->set('siklusStatus', 'selesai')
            ->call('simpanSiklus')
            ->assertHasNoErrors()
            ->html();

        $this->assertTrue(SpmiSiklus::where('prodi_id', $prodi)->where('tahun', 2027)->where('tahap', 'penetapan')->exists());
        $this->assertStringContainsString('Penetapan', $html);

        // Idempoten: simpan ulang tahap sama tidak menggandakan baris.
        Livewire::actingAs($this->user('superadmin'))->test(Akreditasi::class)
            ->set('siklusProdiId', $prodi)
            ->set('siklusTahun', 2027)
            ->set('siklusTahap', 'penetapan')
            ->set('siklusStatus', 'selesai')
            ->call('simpanSiklus')
            ->assertHasNoErrors();

        $this->assertSame(1, SpmiSiklus::where('prodi_id', $prodi)->where('tahun', 2027)->count());
    }

    /** Dokumen cetak memuat stylesheet print + CSS berisi aturan kertas. */
    public function test_stylesheet_cetak_terpasang(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTS']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'PS', 'slug' => 'ps']);
        $u = $this->user('mahasiswa');
        $mhsId = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => '202499C1', 'prodi_id' => $prodi,
            'angkatan' => 2024, 'status' => 'aktif',
        ]);

        $this->actingAs($u)->get(route('khs.show', $mhsId))->assertOk()->assertSee('css/cetak.css', false);
        $this->actingAs($u)->get(route('transkrip.cetak', $mhsId))->assertOk()->assertSee('css/cetak.css', false);

        $css = (string) file_get_contents(public_path('css/cetak.css'));
        $this->assertStringContainsString('@media print', $css);
        $this->assertStringContainsString('table-header-group', $css);
        $this->assertStringContainsString('page-break-inside', $css);
    }
}
