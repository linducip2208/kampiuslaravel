<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiGuardTest extends TestCase
{
    #[DataProvider('endpointProvider')]
    public function test_endpoint_terproteksi_menolak_tanpa_token(string $method, string $uri): void
    {
        $response = $this->json($method, $uri);
        $response->assertStatus(401);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'me' => ['GET', '/api/v1/me'],
            'jadwal' => ['GET', '/api/v1/jadwal-hari-ini'],
            'krs' => ['GET', '/api/v1/krs-aktif'],
            'nilai' => ['GET', '/api/v1/nilai-ringkas'],
            'absensi scan' => ['POST', '/api/v1/absensi/scan'],
            'tugas' => ['GET', '/api/v1/tugas'],
            'tagihan' => ['GET', '/api/v1/tagihan'],
            'bimbingan' => ['GET', '/api/v1/bimbingan'],
            'pengumuman' => ['GET', '/api/v1/pengumuman'],
        ];
    }
}
