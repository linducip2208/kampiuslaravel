<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\DendaRuleKelola;
use App\Models\DendaRule;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DendaRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function keuanganUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('keuangan');

        return $user;
    }

    public function test_keuangan_bisa_membuat_aturan_denda(): void
    {
        Livewire::actingAs($this->keuanganUser())
            ->test(DendaRuleKelola::class)
            ->set('jenis', 'UKT')
            ->set('terlambat_hari_min', 7)
            ->set('mode', 'persen')
            ->set('nilai', 5)
            ->set('aktif', true)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('denda_rules', [
            'jenis' => 'UKT',
            'terlambat_hari_min' => 7,
            'mode' => 'persen',
            'nilai' => 5,
        ]);
    }

    public function test_persen_lebih_dari_100_ditolak(): void
    {
        Livewire::actingAs($this->keuanganUser())
            ->test(DendaRuleKelola::class)
            ->set('jenis', 'SPP')
            ->set('terlambat_hari_min', 1)
            ->set('mode', 'persen')
            ->set('nilai', 150)
            ->call('simpan')
            ->assertHasErrors(['nilai']);

        $this->assertDatabaseMissing('denda_rules', ['jenis' => 'SPP', 'nilai' => 150]);
    }

    public function test_mahasiswa_tidak_bisa_akses_halaman_denda(): void
    {
        $mhs = User::factory()->create();
        $mhs->assignRole('mahasiswa');

        $this->actingAs($mhs)->get(route('keuangan.denda'))->assertForbidden();
    }

    public function test_keuangan_bisa_menghapus_aturan(): void
    {
        $rule = DendaRule::create([
            'jenis' => 'lain',
            'terlambat_hari_min' => 3,
            'mode' => 'nominal',
            'nilai' => 25000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->keuanganUser())
            ->test(DendaRuleKelola::class)
            ->call('hapus', $rule->id);

        $this->assertDatabaseMissing('denda_rules', ['id' => $rule->id]);
    }
}
