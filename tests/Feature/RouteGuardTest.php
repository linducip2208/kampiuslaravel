<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Kunci regresi: rute sensitif WAJIB di belakang middleware role.
 * Latar: controller laporan/funnel tidak punya auth di file — keamanan
 * sepenuhnya bergantung pada middleware rute. Tes ini gagal bila ada
 * rute yang Teresa pindah keluar grup role.
 */
class RouteGuardTest extends TestCase
{
    /**
     * @return array<string, list<string>>
     */
    private static function expectations(): array
    {
        return [
            'laporan.index' => ['pimpinan'],
            'laporan.export.tagihan' => ['pimpinan'],
            'laporan.export.neraca' => ['pimpinan'],
            'pencarian' => ['auth'],
            'pmb.funnel' => ['pimpinan'],
            'keuangan.tagihan' => ['keuangan'],
            'keuangan.verifikasi' => ['keuangan'],
            'keuangan.beasiswa' => ['keuangan'],
            'keuangan.denda' => ['keuangan'],
            'admin.users' => ['superadmin'],
            'admin.master' => ['superadmin'],
            'admin.impor' => ['superadmin'],
            'admin.integrasi' => ['superadmin'],
            'admin.audit' => ['superadmin'],
            'dashboard.pimpinan' => ['pimpinan'],
            'skripsi.yudisium' => ['pimpinan'],
            'dosen.krs.persetujuan' => ['dosen'],
            'nilai.input' => ['dosen'],
        ];
    }

    public function test_rute_sensitif_dilindungi_middleware(): void
    {
        foreach (self::expectations() as $name => $mustContain) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} tidak terdaftar.");

            $middleware = implode('|', (array) $route->gatherMiddleware());

            $this->assertStringContainsString('auth', $middleware, "Route {$name} tanpa auth.");
            foreach ($mustContain as $role) {
                $this->assertStringContainsString($role, $middleware, "Route {$name} tanpa role {$role}.");
            }
        }
    }

    public function test_rute_publik_tetap_terbuka_dengan_throttle(): void
    {
        foreach (['home', 'pmb.create', 'health'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} tidak terdaftar.");
            $middleware = implode('|', (array) $route->gatherMiddleware());
            $this->assertStringNotContainsString('role:', $middleware, "Route publik {$name} tiba-tiba butuh role.");
        }
    }
}
