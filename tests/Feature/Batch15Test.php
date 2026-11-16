<?php

namespace Tests\Feature;

use App\Livewire\Aset\Opname;
use App\Models\Aset;
use App\Models\AsetOpname;
use App\Models\Dosen;
use App\Models\SkMengajar;
use App\Models\User;
use App\Support\DokumenTte;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 15 ide bank: dokumen SK mengajar, opname aset, log query lambat.
 */
class Batch15Test extends TestCase
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

    /** SK mengajar tercetak + terverifikasi via QR bertanda. */
    public function test_sk_mengajar_cetak_dan_verifikasi(): void
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FTE']);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'K', 'jenjang' => 'S1', 'kode' => 'KE', 'slug' => 'ke']);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2027', 'semester' => 'ganjil', 'aktif' => true]);
        $dosen = Dosen::create(['user_id' => $this->user('dosen')->id, 'nidn' => 'NIDNE', 'prodi_id' => $prodi]);

        $this->actingAs($this->user('superadmin'))
            ->get(route('sk-mengajar.cetak', [$dosen->id, $t]))
            ->assertOk()
            ->assertSee('SK/MENGAJAR');

        $sk = SkMengajar::where('dosen_id', $dosen->id)->firstOrFail();

        $this->get(route('sk-mengajar.verify', [
            'nomor' => $sk->nomor, 's' => DokumenTte::tanda('sk-mengajar:'.$sk->nomor),
        ]))->assertOk()->assertSee('VALID');

        // Tanda palsu ditolak.
        $this->get(route('sk-mengajar.verify', ['nomor' => $sk->nomor, 's' => 'palsu']))->assertForbidden();
    }

    /** Opname mencatat selisih + menerapkan kondisi fisik ke data aset. */
    public function test_opname_aset_menerapkan_selisih(): void
    {
        $aset = Aset::create(['kode' => 'AST-15', 'nama' => 'Proyektor', 'kondisi' => 'baik']);

        $html = Livewire::actingAs($this->user('superadmin'))->test(Opname::class)
            ->set('asetId', $aset->id)
            ->set('kondisiFisik', 'hilang')
            ->set('catatan', 'Tidak ada di gudang')
            ->call('simpan')
            ->assertHasNoErrors()
            ->html();

        $this->assertSame('hilang', $aset->fresh()->kondisi);
        $op = AsetOpname::where('aset_id', $aset->id)->firstOrFail();
        $this->assertSame('baik', $op->kondisi_buku);
        $this->assertFalse($op->cocok());
        $this->assertStringContainsString('selisih', $html);
    }

    /** Ambang tinggi: query normal tidak memicu warning log lambat. */
    public function test_query_normal_tidak_memicu_log_lambat(): void
    {
        config(['app.slow_query_ms' => 999999]);
        Log::spy();

        DB::table('prodi')->count();
        DB::table('tahun_akademik')->count();

        Log::shouldNotHaveReceived('warning');
    }
}
