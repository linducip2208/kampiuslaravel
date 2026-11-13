<?php

namespace Tests\Feature;

use App\Livewire\Admin\DosenData;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DosenDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function prodi(): Prodi
    {
        return Prodi::factory()->create(['fakultas_id' => Fakultas::factory()->create()->id]);
    }

    public function test_rute_dosen_butuh_role_admin(): void
    {
        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('admin.dosen'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('admin.dosen'))->assertOk();
    }

    public function test_admin_bisa_tambah_dosen(): void
    {
        $prodi = $this->prodi();

        Livewire::actingAs($this->admin())
            ->test(DosenData::class)
            ->set('form.nama', 'Dosen Baru')
            ->set('form.email', 'dosen.baru@kampus.test')
            ->set('form.nidn', '0412345678')
            ->set('form.prodi_id', $prodi->id)
            ->set('form.status', 'aktif')
            ->call('simpan')
            ->assertSee('Data dosen tersimpan.');

        $dosen = Dosen::where('nidn', '0412345678')->firstOrFail();
        $this->assertTrue($dosen->prodi->is($prodi));
        $this->assertSame('aktif', $dosen->status);
        $this->assertTrue($dosen->user->hasRole('dosen'));
    }

    public function test_nidn_unique_divalidasi(): void
    {
        Dosen::factory()->create(['nidn' => '0400112233']);

        Livewire::actingAs($this->admin())
            ->test(DosenData::class)
            ->set('form.nama', 'Kembar')
            ->set('form.email', 'kembar@kampus.test')
            ->set('form.nidn', '0400112233')
            ->set('form.prodi_id', $this->prodi()->id)
            ->set('form.status', 'aktif')
            ->call('simpan')
            ->assertHasErrors(['form.nidn' => 'unique']);
    }

    public function test_status_dosen_hanya_aktif_atau_nonaktif(): void
    {
        Livewire::actingAs($this->admin())
            ->test(DosenData::class)
            ->set('form.nama', 'Status Aneh')
            ->set('form.email', 'aneh@kampus.test')
            ->set('form.nidn', '0499887766')
            ->set('form.prodi_id', $this->prodi()->id)
            ->set('form.status', 'cuti')
            ->call('simpan')
            ->assertHasErrors(['form.status' => 'in']);
    }

    public function test_admin_bisa_tambah_tendik(): void
    {
        Livewire::actingAs($this->admin())
            ->test(DosenData::class)
            ->call('gantiTab', 'tendik')
            ->set('form.nama', 'Tendik Baru')
            ->set('form.email', 'tendik@kampus.test')
            ->set('form.role', 'keuangan')
            ->call('simpan')
            ->assertSee('Data tendik tersimpan.');

        $this->assertTrue(User::where('email', 'tendik@kampus.test')->firstOrFail()->hasRole('keuangan'));
    }

    public function test_mahasiswa_ditolak_komponen_dosen(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        Livewire::actingAs($mhs)->test(DosenData::class)->assertForbidden();
    }

    public function test_admin_tidak_bisa_hapus_akun_sendiri(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(DosenData::class)
            ->call('gantiTab', 'tendik')
            ->call('hapus', $admin->id)
            ->assertHasErrors(['form.hapus']);

        $this->assertNotNull(User::find($admin->id));
    }
}
