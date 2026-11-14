<?php

namespace Tests\Feature;

use App\Models\Pengumuman;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PencarianTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dialihkan_login(): void
    {
        $this->get(route('pencarian', ['q' => 'sistem']))->assertRedirect(route('login'));
    }

    public function test_menemukan_prodi_dan_pengumuman(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $prodi = Prodi::factory()->create(['nama' => 'Sistem Informasi Unik']);
        Pengumuman::create([
            'judul' => 'Beasiswa Sistem Informasi',
            'isi' => 'Info beasiswa terbaru.',
            'published_at' => now(),
        ]);

        $this->actingAs($user)->get(route('pencarian', ['q' => 'Sistem Informasi']))
            ->assertOk()
            ->assertSee('Sistem Informasi Unik')
            ->assertSee('Beasiswa Sistem Informasi')
            ->assertSee((string) $prodi->kode);
    }

    public function test_kata_pendek_tidak_mencari(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('pencarian', ['q' => 'a']))
            ->assertOk()
            ->assertSee('minimal 2 karakter');
    }
}
