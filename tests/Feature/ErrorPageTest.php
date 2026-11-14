<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_menampilkan_halaman_ramah(): void
    {
        $this->seed(RolesPermissionsSeeder::class);

        $this->get('/rute-yang-tidak-ada-xyz')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan', false);
    }

    public function test_403_menampilkan_halaman_ramah(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('keuangan.verifikasi'))
            ->assertForbidden()
            ->assertSee('Akses ditolak', false);
    }
}
