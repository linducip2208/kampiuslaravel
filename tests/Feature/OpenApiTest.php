<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiTest extends TestCase
{
    public function test_openapi_json_memuat_kontrak_kunci(): void
    {
        $response = $this->getJson('/api/openapi.json');

        $response->assertOk();
        $json = $response->json();

        $this->assertSame('3.1.0', $json['openapi'] ?? null);
        $this->assertArrayHasKey('info', $json);
        $this->assertArrayHasKey('paths', $json);
        $this->assertIsArray($json['paths']);

        $paths = $json['paths'];
        $flat = implode("\n", array_keys($paths));

        $this->assertStringContainsString('/v1/wali/anak', $flat);
        $this->assertStringContainsString('/v1/token/refresh', $flat);
        $this->assertStringContainsString('/v1/tugas', $flat);

        // Wali: daftar anak + nilai/tagihan per anak.
        $this->assertArrayHasKey('/api/v1/wali/anak', $paths);
        $this->assertArrayHasKey('/api/v1/wali/anak/{mahasiswa}/nilai', $paths);
        $this->assertArrayHasKey('/api/v1/wali/anak/{mahasiswa}/tagihan', $paths);
        $this->assertArrayHasKey('/api/v1/token/refresh', $paths);

        foreach ($paths as $path => $ops) {
            $this->assertIsArray($ops, "Path {$path} harus berupa array operasi.");
            $verbs = array_intersect(array_keys($ops), ['get', 'post', 'put', 'patch', 'delete']);
            $this->assertNotEmpty($verbs, "Path {$path} minimal punya satu verb get/post/put/patch/delete.");

            foreach ($verbs as $verb) {
                $this->assertArrayHasKey('responses', $ops[$verb], "Operasi {$verb} {$path} wajib punya responses.");
            }
        }
    }

    public function test_openapi_koleksi_paging_punya_parameter_dan_skema_meta(): void
    {
        $json = $this->getJson('/api/openapi.json')->assertOk()->json();

        foreach (['/api/v1/tugas', '/api/v1/tagihan', '/api/v1/pengumuman'] as $path) {
            $this->assertArrayHasKey($path, $json['paths'], "Path {$path} wajib ada.");
            $params = collect($json['paths'][$path]['get']['parameters'] ?? [])->pluck('name')->all();

            $this->assertContains('q', $params, "{$path} wajib mendokumentasikan ?q=.");
            $this->assertContains('sort', $params, "{$path} wajib mendokumentasikan ?sort=.");
            $this->assertContains('per_page', $params, "{$path} wajib mendokumentasikan ?per_page=.");
        }

        $meta = $json['components']['schemas']['Meta'] ?? null;
        $this->assertIsArray($meta, 'Skema components.schemas.Meta wajib ada.');
        $this->assertSame('object', $meta['type'] ?? null);
    }
}
