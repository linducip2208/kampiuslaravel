<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserData;
use App\Livewire\Profil;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserDataTest extends TestCase
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

    public function test_admin_users_butuh_role_admin(): void
    {
        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('admin.users'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('admin.users'))->assertOk();
        $this->actingAs($this->admin())->get(route('profil'))->assertOk();
    }

    public function test_admin_bisa_buat_user_dengan_role(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UserData::class)
            ->set('form.name', 'Dosen Baru')
            ->set('form.email', 'dosen.baru@kampus.test')
            ->set('form.password', 'password123')
            ->set('form.role', 'dosen')
            ->call('simpan')
            ->assertSee('User tersimpan.');

        $user = User::where('email', 'dosen.baru@kampus.test')->firstOrFail();
        $this->assertTrue($user->hasRole('dosen'));
    }

    public function test_email_unique_divalidasi(): void
    {
        User::factory()->create(['email' => 'ganda@kampus.test']);

        Livewire::actingAs($this->admin())
            ->test(UserData::class)
            ->set('form.name', 'Ganda')
            ->set('form.email', 'ganda@kampus.test')
            ->set('form.password', 'password123')
            ->set('form.role', 'mahasiswa')
            ->call('simpan')
            ->assertHasErrors(['form.email' => 'unique']);
    }

    public function test_admin_bisa_reset_password(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(UserData::class)
            ->call('resetPassword', $target->id)
            ->assertSee('Password direset');

        $this->assertTrue(Hash::check('Password123', $target->fresh()->password));
    }

    public function test_profil_update_dan_ganti_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('lama-lama123')]);
        $user->assignRole('mahasiswa');

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('name', 'Nama Baru')
            ->set('email', 'baru@kampus.test')
            ->call('simpanProfil')
            ->assertSee('Profil tersimpan.');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'email' => 'baru@kampus.test']);

        Livewire::actingAs($user->fresh())
            ->test(Profil::class)
            ->set('passwordSaatIni', 'lama-lama123')
            ->set('passwordBaru', 'baru-baru123')
            ->set('passwordBaruConfirmation', 'baru-baru123')
            ->call('gantiPassword')
            ->assertSee('Password berhasil diganti.');

        $this->assertTrue(Hash::check('baru-baru123', $user->fresh()->password));
    }

    public function test_profil_menampilkan_linimasa_milik_sendiri(): void
    {
        $user = User::factory()->create();
        $user->assignRole('mahasiswa');
        $mhs = Mahasiswa::factory()->create(['user_id' => $user->id]);
        Tagihan::create([
            'mahasiswa_id' => $mhs->id, 'jenis' => 'UKT',
            'nominal' => 750000, 'terbayar' => 0, 'status' => 'belum_bayar',
        ]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->assertSee('Linimasa Saya')
            ->assertSee('Tagihan UKT belum_bayar');
    }

    public function test_profil_non_mahasiswa_tanpa_linimasa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('dosen');

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->assertDontSee('Linimasa Saya');
    }
}
