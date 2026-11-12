<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi keamanan: IDOR + role middleware + owner-scope.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function mahasiswaUser(string $role = 'mahasiswa'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Mahasiswa::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_mahasiswa_tidak_bisa_lihat_khs_milik_orang_lain(): void
    {
        $a = $this->mahasiswaUser();
        $b = $this->mahasiswaUser();

        $mhsB = Mahasiswa::where('user_id', $b->id)->firstOrFail();

        $this->actingAs($a)->get(route('khs.show', $mhsB->id))->assertForbidden();
        $this->actingAs($a)->get(route('transkrip.cetak', $mhsB->id))->assertForbidden();
    }

    public function test_mahasiswa_bisa_lihat_khs_milik_sendiri(): void
    {
        $a = $this->mahasiswaUser();
        $mhsA = Mahasiswa::where('user_id', $a->id)->firstOrFail();

        $this->actingAs($a)->get(route('khs.show', $mhsA->id))->assertOk();
    }

    public function test_tanpa_role_ditolak_di_rute_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.integrasi'))->assertForbidden();
        $this->actingAs($user)->get(route('keuangan.tagihan'))->assertForbidden();
        $this->actingAs($user)->get(route('pddikti.index'))->assertForbidden();
        $this->actingAs($user)->get(route('nilai.input'))->assertForbidden();
    }

    public function test_dosen_tidak_bisa_akses_keuangan(): void
    {
        $dosen = User::factory()->create();
        $dosen->assignRole('dosen');

        $this->actingAs($dosen)->get(route('keuangan.tagihan'))->assertForbidden();
        $this->actingAs($dosen)->get(route('admin.integrasi'))->assertForbidden();
    }

    public function test_keuangan_bisa_akses_tagihan_tapi_bukan_pimpinan(): void
    {
        $keu = User::factory()->create();
        $keu->assignRole('keuangan');

        $this->actingAs($keu)->get(route('keuangan.tagihan'))->assertOk();
        $this->actingAs($keu)->get(route('dashboard.pimpinan'))->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('keuangan.tagihan'))->assertRedirect(route('login'));
    }
}
