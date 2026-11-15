<?php

namespace Tests\Feature;

use App\Livewire\Analitik\Eksekutif;
use App\Livewire\Bkd\Kelola;
use App\Livewire\Dosen\WaliList;
use App\Livewire\Keuangan\VerifikasiBayar;
use App\Livewire\Krs\PersetujuanPa;
use App\Models\BebanDosen;
use App\Models\Dosen;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RBAC batch 2: wiring peran baru ke grup rute + kepemilikan tetap menang.
 * - Peran BOLEH: kaprodi/kajur→grup dosen; kasir/akuntansi→grup keuangan;
 *   rektor/warek/dekan/wadek→grup pimpinan.
 * - IDOR: peran BARU tidak mewarisi akses data orang lain; owner-scope
 *   dicek di controller (kaprodi/rektor = staf akademik; kasir/dekan = bukan).
 */
class RbacWiringTest extends TestCase
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

    private function mahasiswaDenganUser(string $nim, string $role = 'mahasiswa'): array
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT'.$nim]);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'I', 'jenjang' => 'S1', 'kode' => 'IF'.$nim, 'slug' => 'if-'.$nim]);
        $u = $this->user($role);
        $id = DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);

        return [$u, $id];
    }

    public function test_kaprodi_masuk_grup_dosen(): void
    {
        $this->actingAs($this->user('kaprodi'))->get(route('dosen.krs.persetujuan'))->assertOk();
        $this->actingAs($this->user('kajur'))->get(route('lms.bank'))->assertOk();
        $this->actingAs($this->user('dosen'))->get(route('dosen.krs.persetujuan'))->assertOk();
    }

    public function test_kasir_akuntansi_masuk_grup_keuangan(): void
    {
        $this->actingAs($this->user('kasir'))->get(route('keuangan.verifikasi'))->assertOk();
        $this->actingAs($this->user('akuntansi'))->get(route('keuangan.gl'))->assertOk();
        $this->actingAs($this->user('keuangan'))->get(route('keuangan.verifikasi'))->assertOk();
    }

    public function test_rektor_masuk_grup_pimpinan(): void
    {
        $this->actingAs($this->user('rektor'))->get(route('dashboard.pimpinan'))->assertOk();
        $this->actingAs($this->user('dekan'))->get(route('laporan.index'))->assertOk();
        $this->actingAs($this->user('pimpinan'))->get(route('dashboard.pimpinan'))->assertOk();
    }

    public function test_penolakan_lintas_modul_tetap_berlaku(): void
    {
        $this->actingAs($this->user('kaprodi'))->get(route('keuangan.verifikasi'))->assertForbidden();
        $this->actingAs($this->user('kasir'))->get(route('dosen.krs.persetujuan'))->assertForbidden();
        $this->actingAs($this->user('rektor'))->get(route('keuangan.verifikasi'))->assertForbidden();
        $this->actingAs($this->user('mahasiswa'))->get(route('dosen.krs.persetujuan'))->assertForbidden();
        $this->actingAs($this->user('auditor'))->get(route('dosen.krs.persetujuan'))->assertForbidden();
        $this->actingAs($this->user('penguji'))->get(route('keuangan.verifikasi'))->assertForbidden();
    }

    public function test_idor_kepemilikan_tetap_menang_atas_peran(): void
    {
        [, $mhs] = $this->mahasiswaDenganUser('20209950');

        // Staf akademik (termasuk kaprodi/rektor baru): boleh.
        $this->actingAs($this->user('kaprodi'))->get(route('khs.show', $mhs))->assertOk();
        $this->actingAs($this->user('rektor'))->get(route('khs.show', $mhs))->assertOk();

        // Bukan staf akademik: ditolak walau rolenya "keuangan"/pimpinan-fakultas.
        $this->actingAs($this->user('kasir'))->get(route('khs.show', $mhs))->assertForbidden();
        $this->actingAs($this->user('dekan'))->get(route('khs.show', $mhs))->assertForbidden();

        // Mahasiswa lain: ditolak.
        [$lain] = $this->mahasiswaDenganUser('20209951');
        $this->actingAs($lain)->get(route('khs.show', $mhs))->assertForbidden();
    }

    public function test_push_pddikti_menulis_audit_milik_user(): void
    {
        Http::fake(['*' => Http::response(['error' => 0, 'data' => ['token' => 'tok']])]);
        $pim = $this->user('pimpinan');

        $this->actingAs($pim)->postJson(route('pddikti.push'), ['act' => 'InsertMhs', 'record' => ['nim' => 'X1']])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $pim->id, 'action' => 'pddikti.push', 'model' => 'pddikti',
        ]);
    }

    public function test_komponen_mengikuti_peran_baru(): void
    {
        $kaprodi = $this->user('kaprodi');
        Livewire::actingAs($kaprodi)->test(PersetujuanPa::class)->assertOk();
        Livewire::actingAs($kaprodi)->test(WaliList::class)->assertOk();

        $kasir = $this->user('kasir');
        Livewire::actingAs($kasir)->test(VerifikasiBayar::class)->assertOk();

        $rektor = $this->user('rektor');
        Livewire::actingAs($rektor)->test(Eksekutif::class)->assertOk();

        // Penolakan di lapis komponen (bukan cuma rute).
        Livewire::actingAs($this->user('penguji'))->test(PersetujuanPa::class)->assertForbidden();
        Livewire::actingAs($this->user('auditor'))->test(VerifikasiBayar::class)->assertForbidden();
        Livewire::actingAs($this->user('dekan'))->test(VerifikasiBayar::class)->assertForbidden();
    }

    /** BKD: kaprodi struktural (tanpa baris dosen) tidak mewarisi daftar beban. */
    public function test_bkd_tanpa_baris_dosen_kosong(): void
    {
        $dosenUser = $this->user('dosen');
        Dosen::factory()->create(['user_id' => $dosenUser->id]);
        $t = DB::table('tahun_akademik')->insertGetId(['tahun' => '2026', 'semester' => 'ganjil', 'aktif' => true]);
        BebanDosen::create([
            'dosen_id' => Dosen::where('user_id', $dosenUser->id)->value('id'),
            'tahun_akademik_id' => $t, 'kategori' => 'penelitian',
            'uraian' => 'Rahasia BKD-X', 'sks' => 2, 'status' => 'diajukan',
        ]);

        $kaprodi = $this->user('kaprodi');
        $html = Livewire::actingAs($kaprodi)->test(Kelola::class)->assertOk()->html();
        $this->assertStringNotContainsString('Rahasia BKD-X', $html);
    }
}
