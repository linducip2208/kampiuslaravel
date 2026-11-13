<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPerfTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_muat_dengan_query_terbatas(): void
    {
        $this->seed(RolesPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // Bukti benchmark (diukur 2026-11-13, sqlite kosong): 79 query.
        $this->assertLessThan(90, $queries, "Dashboard mengeksekusi {$queries} query, melebihi ambang 90.");
    }
}
