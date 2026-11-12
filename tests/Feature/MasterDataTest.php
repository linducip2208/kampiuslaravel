<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_uses_tabler_layout(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('tabler.min.css');
        $response->assertSee('SIMAK Kuliah');
    }

    public function test_dashboard_shows_statistics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Mahasiswa');
        $response->assertSee('Program Studi');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke akun');
    }

    public function test_master_data_relations(): void
    {
        $fakultas = Fakultas::factory()->create();
        $prodi = Prodi::factory()->create(['fakultas_id' => $fakultas->id]);
        $mahasiswa = Mahasiswa::factory()->create(['prodi_id' => $prodi->id]);
        $kelas = Kelas::factory()->create();

        $this->assertTrue($prodi->fakultas->is($fakultas));
        $this->assertTrue($mahasiswa->prodi->is($prodi));
        $this->assertTrue($mahasiswa->user()->exists());
        $this->assertNotNull($kelas->matakuliah);
        $this->assertNotNull($kelas->tahunAkademik);
    }

    public function test_roles_and_permissions_seeded(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        foreach (['superadmin', 'admin_prodi', 'keuangan', 'dosen', 'mahasiswa', 'pimpinan'] as $role) {
            $this->assertNotNull(Role::findByName($role, 'web'));
        }

        $this->assertTrue(Role::findByName('superadmin', 'web')->hasPermissionTo('master.manage'));
        $this->assertTrue(Role::findByName('keuangan', 'web')->hasPermissionTo('keuangan.manage'));
        $this->assertTrue(Role::findByName('pimpinan', 'web')->hasPermissionTo('laporan.view'));
    }
}
