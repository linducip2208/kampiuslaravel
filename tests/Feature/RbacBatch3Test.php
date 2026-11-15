<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserData;
use App\Livewire\Sidang\NilaiPenguji;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Sidang;
use App\Models\SidangPenguji;
use App\Models\SkripsiJudul;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * RBAC batch 3: jalur examiner owner-scope, halaman HR, UI matriks,
 * audit penugasan peran. Delegasi/ekspirasi + scoping prodi = batch 4.
 */
class RbacBatch3Test extends TestCase
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

    private function dosenUser(string $role = 'penguji'): array
    {
        $u = $this->user($role);
        $d = Dosen::factory()->create(['user_id' => $u->id]);

        return [$u, $d->id];
    }

    private function sidangDenganPenguji(int $mhs, int $dosenId, string $namaMhs): int
    {
        $judul = SkripsiJudul::create(['mahasiswa_id' => $mhs, 'judul' => 'TA '.$namaMhs, 'status' => 'disetujui']);
        $sidang = Sidang::create(['judul_id' => $judul->id, 'jenis' => 'tutup', 'tanggal' => now()->addDay(), 'status' => 'terdaftar']);

        return SidangPenguji::create(['sidang_id' => $sidang->id, 'dosen_id' => $dosenId, 'peran' => 'penguji'])->id;
    }

    private function mahasiswa(string $nim): int
    {
        $fak = DB::table('fakultas')->insertGetId(['nama' => 'T', 'kode' => 'FT'.$nim]);
        $prodi = DB::table('prodi')->insertGetId(['fakultas_id' => $fak, 'nama' => 'I', 'jenjang' => 'S1', 'kode' => 'IF'.$nim, 'slug' => 'if-'.$nim]);
        $u = $this->user('mahasiswa');

        return DB::table('mahasiswa')->insertGetId([
            'user_id' => $u->id, 'nim' => $nim, 'prodi_id' => $prodi,
            'angkatan' => 2026, 'status' => 'aktif',
        ]);
    }

    /** Penguji: hanya baris sendiri yang tampil & bisa dinilai. */
    public function test_penguji_hanya_milik_sendiri(): void
    {
        $mhsA = $this->mahasiswa('202099A1');
        $mhsB = $this->mahasiswa('202099B2');
        [$uA] = $this->dosenUser('penguji');
        [$uB] = $this->dosenUser('dosen');
        $rowA = $this->sidangDenganPenguji($mhsA, Dosen::where('user_id', $uA->id)->value('id'), 'A');
        $this->sidangDenganPenguji($mhsB, Dosen::where('user_id', $uB->id)->value('id'), 'B');

        $this->actingAs($uA)->get(route('sidang.nilai.milikku'))->assertOk();

        $html = Livewire::actingAs($uA)->test(NilaiPenguji::class)->html();
        $this->assertStringContainsString('TA A', $html);
        $this->assertStringNotContainsString('TA B', $html);

        Livewire::actingAs($uA)->test(NilaiPenguji::class)
            ->set('nilai.'.$rowA, 85)
            ->call('simpan', $rowA)
            ->assertHasNoErrors();

        $this->assertSame(85.0, (float) SidangPenguji::find($rowA)->nilai);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sidang.nilai-penguji', 'model_id' => $rowA]);

        // Baris orang lain → 404 internal, nilai TAK berubah (bukan tertimpa).
        Livewire::actingAs($uB)->test(NilaiPenguji::class)
            ->set('nilai.'.$rowA, 10)
            ->call('simpan', $rowA)
            ->assertNotFound();
        $this->assertSame(85.0, (float) SidangPenguji::find($rowA)->nilai);
    }

    /** HRD: halaman payroll + rekrutmen boleh; keuangan inti tetap tutup. */
    public function test_hrd_halaman_sdm_terbatas(): void
    {
        $hrd = $this->user('hrd');
        $this->actingAs($hrd)->get(route('hrd.payroll'))->assertOk();
        $this->actingAs($hrd)->get(route('hrd.rekrutmen'))->assertOk();
        $this->actingAs($hrd)->get(route('keuangan.verifikasi'))->assertForbidden();
        $this->actingAs($hrd)->get(route('keuangan.gl'))->assertForbidden();
        $this->actingAs($hrd)->get(route('dosen.krs.persetujuan'))->assertForbidden();
    }

    /** Matriks RBAC: admin boleh lihat; mahasiswa ditolak. */
    public function test_matriks_rbac(): void
    {
        $adm = $this->user('admin_prodi');
        $this->actingAs($adm)->get(route('admin.matriks-rbac'))->assertOk()->assertSee('kaprodi');
        $this->actingAs($this->user('mahasiswa'))->get(route('admin.matriks-rbac'))->assertForbidden();
    }

    /** Penugasan peran dicatat di audit dengan diff dari→ke. */
    public function test_penugasan_peran_diaudit(): void
    {
        $adm = $this->user('superadmin');
        $target = $this->user('mahasiswa');

        Livewire::actingAs($adm)->test(UserData::class)
            ->call('edit', $target->id)
            ->set('form.role', 'dosen')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.role.diubah', 'model_id' => $target->id]);
        $row = AuditLog::where('action', 'user.role.diubah')->latest('id')->firstOrFail();
        $this->assertSame(['mahasiswa'], $row->diff_json['dari'] ?? null);
        $this->assertSame(['dosen'], $row->diff_json['ke'] ?? null);
    }
}
