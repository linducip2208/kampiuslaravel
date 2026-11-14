<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Semua nama rute literal yang dipakai di Blade/app harus terdaftar.
 * Menangkap typo seperti 'publik.pengumumanDetail' sebelum sampai ke browser.
 */
class RouteReferenceTest extends TestCase
{
    public function test_semua_nama_rute_literal_terdaftar(): void
    {
        $names = [];
        $roots = [base_path('resources/views'), base_path('app')];
        foreach ($roots as $dir) {
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($iter as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $c = file_get_contents($file->getPathname());
                if (preg_match_all("/route\(\s*['\"]([^'\"]+)['\"]/", $c, $m)) {
                    foreach ($m[1] as $n) {
                        $names[$n] = $file->getPathname();
                    }
                }
            }
        }

        $missing = [];
        foreach (array_keys($names) as $n) {
            if (! Route::has($n)) {
                $missing[$n] = $names[$n];
            }
        }

        $this->assertSame([], $missing, 'Rute tak terdaftar: '.json_encode($missing));
    }
}
