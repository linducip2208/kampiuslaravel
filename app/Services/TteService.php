<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien layanan TTE BSrE. Kredensial dari halaman admin Integrasi
 * (tte.base_url, tte.username, tte.password).
 */
class TteService
{
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->username() !== '' && $this->password() !== '';
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public function status(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'Belum dikonfigurasi'];
        }

        try {
            $res = Http::timeout(10)->get($this->baseUrl().'/status');

            if ($res->successful()) {
                return ['ok' => true, 'message' => 'OK — layanan TTE merespons.'];
            }

            return ['ok' => false, 'message' => 'Layanan TTE balas HTTP '.$res->status().' — cek URL BSrE.'];
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal menghubungi layanan TTE: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array{ok:bool, data:mixed}
     */
    public function sign(string $hashHex, string $nomor): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('TTE belum dikonfigurasi');
        }

        try {
            $res = Http::withBasicAuth($this->username(), $this->password())
                ->timeout(20)
                ->post($this->baseUrl().'/sign', ['hash' => $hashHex, 'nomor' => $nomor]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal menghubungi layanan TTE: '.$e->getMessage(), 0, $e);
        }

        if ($res->successful()) {
            return ['ok' => true, 'data' => $res->json()];
        }

        $pesan = $res->json('message') ?? $res->json('error_desc') ?? 'HTTP '.$res->status();

        throw new RuntimeException('Gagal menandatangani dokumen: '.$pesan);
    }

    private function baseUrl(): string
    {
        return rtrim((string) Setting::get('tte.base_url', ''), '/');
    }

    private function username(): string
    {
        return (string) Setting::get('tte.username', '');
    }

    private function password(): string
    {
        return (string) Setting::get('tte.password', '');
    }
}
