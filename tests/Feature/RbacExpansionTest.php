<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserData;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ekspansi RBAC batch 1 (aditif): peran baru di-seed, 6 peran lama utuh,
 * daftar peran UI dinamis, gate permission langsung berfungsi.
 */
class RbacExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    /** 13 peran baru ada dengan permission sesuai matriks. */
    public function test_peran_baru_tersedia_dengan_izin_benar(): void
    {
        $this->assertTrue(Role::where('name', 'rektor')->exists());
        $this->assertTrue(Role::where('name', 'kaprodi')->first()->hasPermissionTo('krs.approve'));
        $this->assertTrue(Role::where('name', 'penguji')->first()->hasPermissionTo('nilai.manage'));
        $this->assertFalse(Role::where('name', 'penguji')->first()->hasPermissionTo('keuangan.manage'));
        $this->assertTrue(Role::where('name', 'kasir')->first()->hasPermissionTo('keuangan.manage'));
        $this->assertTrue(Role::where('name', 'auditor')->first()->hasPermissionTo('laporan.view'));
        $this->assertSame([], Role::where('name', 'alumni')->first()->permissions->pluck('name')->all());
    }

    /** 6 peran lama TIDAK berubah (kontrak existing). */
    public function test_enam_peran_lama_utuh(): void
    {
        $ekspektasi = [
            'superadmin' => 15,
            'admin_prodi' => 9,
            'keuangan' => 2,
            'dosen' => 3,
            'mahasiswa' => 1,
            'pimpinan' => 1,
        ];

        foreach ($ekspektasi as $nama => $jumlah) {
            $this->assertCount($jumlah, Role::where('name', $nama)->first()->permissions, "Peran {$nama} berubah!");
        }

        $this->assertTrue(Role::where('name', 'dosen')->first()->hasPermissionTo('lms.manage'));
        $this->assertFalse(Role::where('name', 'mahasiswa')->first()->hasPermissionTo('lms.manage'));
    }

    /** Admin bisa menetapkan peran baru via UI (daftar dinamis, validasi sinkron). */
    public function test_admin_bisa_tetapkan_peran_baru(): void
    {
        $this->assertContains('kaprodi', UserData::daftarRole());
        $this->assertContains('rektor', UserData::daftarRole());

        $adm = User::factory()->create();
        $adm->assignRole('superadmin');

        $target = User::factory()->create(['name' => 'Calon Kaprodi', 'email' => 'kaprodi@kampus.ac.id']);

        Livewire::actingAs($adm)->test(UserData::class)
            ->set('form.name', 'Calon Kaprodi')
            ->set('form.email', 'kaprodi@kampus.ac.id')
            ->set('form.password', '')
            ->set('form.role', 'kaprodi')
            ->call('edit', $target->id)
            ->set('form.role', 'kaprodi')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertTrue($target->fresh()->hasRole('kaprodi'));
        $this->assertTrue($target->fresh()->can('krs.approve'));
    }

    /** Gate permission langsung berfungsi: auditor boleh audit, rektor tidak boleh keuangan. */
    public function test_gate_permission_berlaku_untuk_peran_baru(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');
        $this->actingAs($auditor)->get(route('admin.audit'))->assertOk();

        $rektor = User::factory()->create();
        $rektor->assignRole('rektor');
        $this->actingAs($rektor)->get(route('keuangan.verifikasi'))->assertForbidden();
    }
}
