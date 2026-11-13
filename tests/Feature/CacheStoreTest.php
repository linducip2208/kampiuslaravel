<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Default keamanan serialisasi cache (config cache.serializable_classes=false):
 * objek Eloquent TIDAK boleh pulih dari store (anti object-injection).
 * Karena itu agregat landing TIDAK di-cache sebagai objek — dihitung langsung.
 */
class CacheStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_skalar_dan_array_bisa_roundtrip_cache(): void
    {
        config(['cache.default' => 'database']);

        Cache::remember('probe:scalar', 300, fn () => ['total' => 7, 'label' => 'x']);

        $this->assertSame(['total' => 7, 'label' => 'x'], Cache::get('probe:scalar'));
    }

    public function test_objek_eloquent_tidak_dipulihkan_dari_cache(): void
    {
        config(['cache.default' => 'database']);

        $mhs = Mahasiswa::factory()->create();

        Cache::remember('probe:model', 300, fn () => $mhs->fresh());

        // Default aman: unserialize menolak class -> bukan instance Mahasiswa.
        $this->assertNotInstanceOf(Mahasiswa::class, Cache::get('probe:model'));
    }
}
