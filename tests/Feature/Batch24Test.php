<?php

namespace Tests\Feature;

use App\Livewire\Aset\Opname;
use App\Livewire\Asrama\Kelola as AsramaKelola;
use App\Livewire\Jadwal\KalenderRuang;
use App\Livewire\Klinik\Kunjungan;
use App\Livewire\Rekrutmen\Kelola as RekrutmenKelola;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batch 24 ide bank: empty-state informatif + asosiasi label
 * pada halaman batch 6–23.
 */
class Batch24Test extends TestCase
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

    /** Halaman kosong menampilkan pesan informatif, bukan tabel kosong. */
    public function test_empty_state_informatif(): void
    {
        $adm = $this->user('superadmin');

        $this->assertStringContainsString(
            'Belum ada opname',
            Livewire::actingAs($adm)->test(Opname::class)->html()
        );

        $this->assertStringContainsString(
            'Belum ada kunjungan',
            Livewire::actingAs($this->user('petugas_klinik'))->test(Kunjungan::class)->html()
        );

        $asrama = Livewire::actingAs($adm)->test(AsramaKelola::class)->html();
        $this->assertStringContainsString('Belum ada kamar', $asrama);
        $this->assertStringContainsString('Belum ada penghuni', $asrama);

        $this->assertStringContainsString(
            'Belum ada data ruangan',
            Livewire::actingAs($adm)->test(KalenderRuang::class)->html()
        );

        $this->assertStringContainsString(
            'Belum ada lowongan',
            Livewire::actingAs($adm)->test(RekrutmenKelola::class)->html()
        );
    }

    /** Checkbox onboarding terasosiasi label (aksesibilitas keyboard/screen-reader). */
    public function test_checkbox_onboarding_berlabel(): void
    {
        $blade = (string) file_get_contents(resource_path('views/livewire/rekrutmen/kelola.blade.php'));

        $this->assertStringContainsString('for="onb-', $blade);
        $this->assertStringContainsString('id="onb-', $blade);
    }
}
