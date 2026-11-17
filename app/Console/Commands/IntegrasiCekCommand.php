<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\AiKuotaService;
use App\Services\TteService;
use Illuminate\Console\Command;

/**
 * Readiness check semua integrasi — 100% non-destruktif.
 *
 * Hanya membaca konfigurasi (tabel settings + config/*) dan memvalidasi
 * file lokal (service-account FCM). TIDAK ada HTTP keluar, TIDAK ada
 * pengiriman pesan, TIDAK ada transaksi nyata. Konektivitas aktual tetap
 * diuji lewat tombol "Tes" per layanan di halaman Admin/Integrasi.
 *
 * Exit code SELALU 0 (read-only); tiap cek dibungkus try/catch.
 * Tanpa kredensial = status 'belum dikonfigurasi', BUKAN gagal.
 */
class IntegrasiCekCommand extends Command
{
    protected $signature = 'integrasi:cek';

    protected $description = 'Cek readiness non-destruktif semua integrasi (feeder, gateway, wa, fcm, sso, tte, ai).';

    public function handle(): int
    {
        $rows = [
            $this->cekFeeder(),
            $this->cekGateway(),
            $this->cekWa(),
            $this->cekFcm(),
            $this->cekSso('google'),
            $this->cekSso('microsoft'),
            $this->cekTte(),
            $this->cekAi(),
        ];

        $this->table(['Layanan', 'Status', 'Detail'], $rows);

        return self::SUCCESS;
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekFeeder(): array
    {
        try {
            $base = trim((string) Setting::get('pddikti.base_url', ''));
            $user = trim((string) Setting::get('pddikti.username', ''));
            $pass = (string) Setting::get('pddikti.password', '');

            if ($base === '' || $user === '' || $pass === '') {
                return ['feeder', 'belum dikonfigurasi', 'Isi base URL + username + password PDDikti. Token GetToken diuji via tombol Tes di UI.'];
            }

            return ['feeder', 'OK', "terkonfigurasi ({$base}); konektivitas GetToken diuji via tombol Tes di UI."];
        } catch (\Throwable $e) {
            return ['feeder', 'gagal', 'Gagal membaca konfigurasi: '.$e->getMessage()];
        }
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekGateway(): array
    {
        try {
            $driver = (string) (Setting::get('pay.driver') ?: config('payment.default_driver', 'simulated'));
            $drivers = (array) config('payment.drivers', []);

            if (! isset($drivers[$driver])) {
                return ['gateway', 'gagal', "driver '{$driver}' tak dikenal — cek config/payment.php."];
            }

            if ($driver === 'simulated') {
                return ['gateway', 'OK', 'driver simulasi (dev, tanpa API).'];
            }

            $kurang = match ($driver) {
                'tripay' => $this->kunciKosong(['pay.merchant_code', 'pay.server_key', 'pay.private_key']),
                'midtrans', 'xendit' => $this->kunciKosong(['pay.server_key']),
                default => [],
            };

            if ($kurang !== []) {
                return ['gateway', 'belum dikonfigurasi', "driver {$driver}; belum diisi: ".implode(', ', $kurang).'.'];
            }

            return ['gateway', 'OK', "driver {$driver}; kredensial terisi (tanpa transaksi nyata)."];
        } catch (\Throwable $e) {
            return ['gateway', 'gagal', 'Gagal membaca konfigurasi: '.$e->getMessage()];
        }
    }

    /** @return list<string> */
    private function kunciKosong(array $keys): array
    {
        $kosong = [];

        foreach ($keys as $key) {
            if (trim((string) Setting::get($key, '')) === '') {
                $kosong[] = $key;
            }
        }

        return $kosong;
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekWa(): array
    {
        try {
            $url = trim((string) Setting::get('wa.gateway_url', ''));
            $token = trim((string) Setting::get('wa.token', ''));

            if ($url === '' || $token === '') {
                return ['wa', 'belum dikonfigurasi', 'Isi gateway URL + token WA. Tanpa itu pengiriman hanya simulasi.'];
            }

            return ['wa', 'OK', 'gateway + token terisi (tidak mengirim pesan tes).'];
        } catch (\Throwable $e) {
            return ['wa', 'gagal', 'Gagal membaca konfigurasi: '.$e->getMessage()];
        }
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekFcm(): array
    {
        try {
            $projectId = trim((string) Setting::get('fcm.project_id', ''));
            $path = trim((string) Setting::get('fcm.service_account_path', ''));

            if ($projectId === '' || $path === '' || ! is_file($path)) {
                return ['fcm', 'belum dikonfigurasi', 'Isi Project ID + path JSON service-account. Notifikasi tetap masuk inbox.'];
            }

            $json = json_decode((string) @file_get_contents($path), true);

            if (! is_array($json) || ! isset($json['client_email'])) {
                return ['fcm', 'gagal', 'File JSON service-account tidak valid.'];
            }

            return ['fcm', 'OK', 'file service-account valid ('.$json['client_email'].').'];
        } catch (\Throwable $e) {
            return ['fcm', 'gagal', 'Gagal membaca konfigurasi: '.$e->getMessage()];
        }
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekSso(string $provider): array
    {
        try {
            $id = trim((string) (Setting::get("sso.{$provider}_client_id", '') ?? ''));
            $secret = (string) (Setting::get("sso.{$provider}_client_secret", '') ?? '');

            if ($id === '') {
                $id = trim((string) (config("services.{$provider}.client_id") ?? ''));
            }

            if ($secret === '') {
                $secret = (string) (config("services.{$provider}.client_secret") ?? '');
            }

            if ($id === '' || $secret === '') {
                return ["sso_{$provider}", 'belum dikonfigurasi', "Isi Client ID + Client Secret {$provider}. Login password tetap berfungsi."];
            }

            $redirect = rtrim((string) config('app.url'), '/').'/auth/'.$provider.'/callback';

            return ["sso_{$provider}", 'OK', "kredensial terisi; redirect URI: {$redirect}."];
        } catch (\Throwable $e) {
            return ["sso_{$provider}", 'gagal', 'Gagal membaca konfigurasi: '.$e->getMessage()];
        }
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekTte(): array
    {
        try {
            $hasil = app(TteService::class)->status();

            if (($hasil['message'] ?? '') === 'Belum dikonfigurasi') {
                return ['tte', 'belum dikonfigurasi', 'Isi base URL + username + password BSrE. Dokumen belum bisa ditandatangani.'];
            }

            return ['tte', ! empty($hasil['ok']) ? 'OK' : 'gagal', (string) ($hasil['message'] ?? '-')];
        } catch (\Throwable $e) {
            return ['tte', 'gagal', $e->getMessage()];
        }
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function cekAi(): array
    {
        try {
            $driver = (string) (Setting::get('ai.driver') ?: config('ai.default_driver', 'simulated'));
            $drivers = (array) config('ai.drivers', []);
            $kuota = class_exists(AiKuotaService::class) ? 'kuota service tersedia' : 'kuota service TIDAK ditemukan';

            if ($driver === '' || $driver === 'simulated') {
                return ['ai', 'belum dikonfigurasi', "mode simulasi (tanpa API); {$kuota}."];
            }

            if (! isset($drivers[$driver])) {
                return ['ai', 'gagal', "driver '{$driver}' tak dikenal — cek config/ai.php; {$kuota}."];
            }

            $baseUrl = trim((string) Setting::get('ai.base_url', ''));
            $apiKey = (string) Setting::get('ai.api_key', '');

            if ($baseUrl === '' || $apiKey === '') {
                return ['ai', 'belum dikonfigurasi', "driver {$driver}; belum lengkap (isi base URL + API key); {$kuota}."];
            }

            return ['ai', 'OK', "driver {$driver} terisi (tanpa memanggil endpoint); {$kuota}."];
        } catch (\Throwable $e) {
            return ['ai', 'gagal', 'Gagal membaca konfigurasi: '.$e->getMessage()];
        }
    }
}
