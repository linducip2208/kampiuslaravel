<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Setting;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Integrasi extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $pay_driver = '';

    public string $pay_server_key = '';

    public string $pay_base_url = '';

    public string $pay_bank = 'bni';

    public string $pay_callback_token = '';

    public string $pay_merchant_code = '';

    public string $pay_private_key = '';

    public string $pay_method = 'BRIVA';

    public string $fcm_project_id = '';

    public string $fcm_service_account_path = '';

    public string $pddikti_base_url = '';

    public string $pddikti_username = '';

    public string $pddikti_password = '';

    public string $pddikti_live_enabled = '0';

    public string $sso_google_client_id = '';

    public string $sso_google_client_secret = '';

    public string $sso_microsoft_client_id = '';

    public string $sso_microsoft_client_secret = '';

    public string $sinta_base_url = '';

    public string $sinta_api_key = '';

    public string $garuda_base_url = '';

    public string $garuda_api_key = '';

    public string $tte_base_url = '';

    public string $tte_username = '';

    public string $tte_password = '';

    public string $storage_cdn_url = '';

    public string $storage_r2_endpoint = '';

    public string $storage_r2_bucket = '';

    public string $webhook_url = '';

    public string $webhook_secret = '';

    public string $tenant_mode = 'single';

    public string $comfyui_url = 'http://127.0.0.1:8188';

    public string $comfyui_model = 'wan2.1';

    public string $comfyui_workflow_file = '';

    public string $ai_driver = 'simulated';

    public string $ai_base_url = '';

    public string $ai_api_key = '';

    public string $ai_model = '';

    public string $wa_gateway_url = '';

    public string $wa_token = '';

    public string $pajak_npwp = '';

    public string $pajak_ppn_persen = '0';

    public string $message = '';

    /** @var array<string, string> */
    public array $tes = [];

    public function test(string $layanan): void
    {
        $this->tes[$layanan] = match ($layanan) {
            'comfyui' => $this->tesComfy(),
            'tripay' => $this->tesTripay(),
            'pddikti' => $this->tesFeeder(),
            'fcm' => $this->tesFcm(),
            'sso_google' => $this->tesSso('google'),
            'sso_microsoft' => $this->tesSso('microsoft'),
            'sinta' => $this->tesRiset('sinta'),
            'garuda' => $this->tesRiset('garuda'),
            'tte' => $this->tesTte(),
            'storage' => $this->tesStorage(),
            'webhook' => $this->tesWebhook(),
            'ai' => $this->tesAi(),
            default => 'Tidak dikenal.',
        };
    }

    private function tesComfy(): string
    {
        try {
            $url = $this->comfyui_url !== '' ? $this->comfyui_url : (string) config('comfyui.url');
            $res = Http::timeout(10)->get(rtrim($url, '/').'/system_stats');

            return $res->successful() ? 'OK — ComfyUI merespons.' : 'Gagal (HTTP '.$res->status().'). Cek URL & server ComfyUI.';
        } catch (\Throwable $e) {
            return 'Gagal: '.$e->getMessage();
        }
    }

    private function tesTripay(): string
    {
        try {
            $base = $this->pay_base_url !== '' ? $this->pay_base_url : (string) config('payment.defaults.tripay_base_url');
            $res = Http::withToken($this->pay_server_key)->timeout(15)->get(rtrim($base, '/').'/merchant/payment-channel');

            if ($res->successful() && ($res->json('success') ?? false)) {
                return 'OK — '.count($res->json('data') ?? []).' channel Tripay terbaca.';
            }

            return 'Gagal: '.($res->json('message') ?? 'HTTP '.$res->status()).' — cek API key & base URL.';
        } catch (\Throwable $e) {
            return 'Gagal: '.$e->getMessage();
        }
    }

    private function tesFeeder(): string
    {
        try {
            $res = Http::timeout(15)->post($this->pddikti_base_url, [
                'act' => 'GetToken',
                'username' => $this->pddikti_username,
                'password' => $this->pddikti_password,
            ]);

            return $res->successful() && $res->json('error') == 0
                ? 'OK — token Feeder didapat.'
                : 'Gagal: '.($res->json('error_desc') ?? 'cek URL & kredensial.');
        } catch (\Throwable $e) {
            return 'Gagal: '.$e->getMessage();
        }
    }

    private function tesFcm(): string
    {
        if ($this->fcm_project_id === '' || $this->fcm_service_account_path === '' || ! is_file($this->fcm_service_account_path)) {
            return 'Belum dikonfigurasi (isi Project ID + path JSON service-account). Notifikasi tetap masuk inbox.';
        }

        $json = json_decode((string) @file_get_contents($this->fcm_service_account_path), true);

        return is_array($json) && isset($json['client_email'])
            ? 'OK — file service-account valid ('.$json['client_email'].').'
            : 'File JSON tidak valid.';
    }

    private function tesSso(string $provider): string
    {
        $id = $provider === 'google' ? $this->sso_google_client_id : $this->sso_microsoft_client_id;
        $secret = $provider === 'google' ? $this->sso_google_client_secret : $this->sso_microsoft_client_secret;

        if ($id === '' || $secret === '') {
            return 'Belum dikonfigurasi (isi Client ID + Client Secret '.$provider.'). Login password tetap berfungsi.';
        }

        $redirect = rtrim((string) config('app.url'), '/').'/auth/'.$provider.'/callback';

        return 'OK — kredensial terisi. Daftarkan redirect URI ini di konsol '.$provider.': '.$redirect;
    }

    private function tesRiset(string $sumber): string
    {
        $base = $sumber === 'sinta' ? $this->sinta_base_url : $this->garuda_base_url;

        if ($base === '') {
            return 'Belum dikonfigurasi (isi base URL '.strtoupper($sumber).' di bawah).';
        }

        try {
            $res = Http::timeout(10)->get(rtrim($base, '/').'/status');

            return $res->successful() ? 'OK — endpoint '.strtoupper($sumber).' merespons.' : 'Gagal (HTTP '.$res->status().'). Cek URL & API key.';
        } catch (\Throwable $e) {
            return 'Gagal: '.$e->getMessage();
        }
    }

    private function tesTte(): string
    {
        if ($this->tte_base_url === '' || $this->tte_username === '') {
            return 'Belum dikonfigurasi (isi base URL + username BSrE). Dokumen belum bisa ditandatangani.';
        }

        try {
            $res = Http::timeout(10)->get(rtrim($this->tte_base_url, '/').'/status');

            return $res->successful() ? 'OK — layanan TTE merespons.' : 'Kredensial terisi; layanan balas HTTP '.$res->status().' (cek URL BSrE).';
        } catch (\Throwable $e) {
            return 'Gagal: '.$e->getMessage();
        }
    }

    private function tesStorage(): string
    {
        if ($this->storage_r2_endpoint !== '' && $this->storage_r2_bucket !== '') {
            return 'OK — disk R2 terkonfigurasi (bucket '.$this->storage_r2_bucket.'). Set FILESYSTEM_DISK=r2 untuk memakai.';
        }

        if ($this->storage_cdn_url !== '') {
            return 'OK — CDN URL terisi. Disk lokal tetap dipakai, aset disajikan via CDN.';
        }

        return 'Belum dikonfigurasi (isi endpoint+bucket R2 atau CDN URL). Penyimpanan lokal tetap dipakai.';
    }

    private function tesWebhook(): string
    {
        if ($this->webhook_secret !== '' && strlen($this->webhook_secret) >= 16) {
            return 'OK — secret webhook valid (min. 16 karakter). Endpoint: POST /api/v2/webhook/terima.';
        }

        return 'Belum dikonfigurasi (isi secret min. 16 karakter). Webhook masuk ditolak 503 sampai diisi.';
    }

    private function tesAi(): string
    {
        if ($this->ai_driver === '' || $this->ai_driver === 'simulated') {
            return 'Mode simulasi aktif (tanpa API). Isi driver OpenAI-compatible + base URL + API key untuk jawaban cerdas.';
        }

        if ($this->ai_base_url === '' || $this->ai_api_key === '') {
            return 'Belum lengkap (isi base URL + API key). Chatbot tetap jalan via simulasi.';
        }

        try {
            $res = Http::withToken($this->ai_api_key)->timeout(15)->post(rtrim($this->ai_base_url, '/').'/chat/completions', [
                'model' => $this->ai_model !== '' ? $this->ai_model : 'test',
                'messages' => [['role' => 'user', 'content' => 'ping']],
                'max_tokens' => 5,
            ]);

            return $res->successful() || $res->status() === 400
                ? 'OK — endpoint AI merespons.'
                : 'Gagal (HTTP '.$res->status().'). Cek base URL & API key.';
        } catch (\Throwable $e) {
            return 'Gagal: '.$e->getMessage();
        }
    }

    private const SETTING_KEYS = [
        'pay_driver', 'pay_server_key', 'pay_base_url', 'pay_bank', 'pay_callback_token',
        'pay_merchant_code', 'pay_private_key', 'pay_method',
        'fcm_project_id', 'fcm_service_account_path',
        'pddikti_base_url', 'pddikti_username', 'pddikti_password', 'pddikti_live_enabled',
        'sso_google_client_id', 'sso_google_client_secret',
        'sso_microsoft_client_id', 'sso_microsoft_client_secret',
        'sinta_base_url', 'sinta_api_key', 'garuda_base_url', 'garuda_api_key',
        'tte_base_url', 'tte_username', 'tte_password',
        'storage_cdn_url', 'storage_r2_endpoint', 'storage_r2_bucket',
        'webhook_url', 'webhook_secret', 'tenant_mode',
        'comfyui_url', 'comfyui_model', 'comfyui_workflow_file',
        'ai_driver', 'ai_base_url', 'ai_api_key', 'ai_model',
        'wa_gateway_url', 'wa_token',
        'pajak_npwp', 'pajak_ppn_persen',
    ];

    public function mount(): void
    {
        foreach (self::SETTING_KEYS as $key) {
            $this->{$key} = (string) Setting::get($this->envKey($key), $this->{$key});
        }

        if ($this->pay_driver === '') {
            $this->pay_driver = (string) config('payment.default_driver', 'simulated');
        }
    }

    public function save(): void
    {
        $this->validate([
            'pay_driver' => ['required', Rule::in(array_keys(PaymentService::availableDrivers()))],
            'pay_base_url' => 'nullable|url|max:255',
            'pddikti_base_url' => 'nullable|url|max:255',
            'sinta_base_url' => 'nullable|url|max:255',
            'garuda_base_url' => 'nullable|url|max:255',
            'tte_base_url' => 'nullable|url|max:255',
            'storage_cdn_url' => 'nullable|url|max:255',
            'storage_r2_endpoint' => 'nullable|url|max:255',
            'webhook_url' => 'nullable|url|max:255',
            'tenant_mode' => ['required', Rule::in(['single', 'sketsa_multi'])],
            'pajak_ppn_persen' => 'nullable|numeric|min:0|max:100',
            'wa_gateway_url' => 'nullable|url|max:255',
        ]);

        foreach (self::SETTING_KEYS as $key) {
            Setting::set($this->envKey($key), $this->{$key} !== '' ? $this->{$key} : null);
        }

        $this->message = 'Pengaturan integrasi tersimpan.';
    }

    private function envKey(string $prop): string
    {
        return match (true) {
            str_starts_with($prop, 'pay_') => 'pay.'.substr($prop, 4),
            str_starts_with($prop, 'fcm_') => 'fcm.'.substr($prop, 4),
            str_starts_with($prop, 'pddikti_') => 'pddikti.'.substr($prop, 8),
            str_starts_with($prop, 'sso_google_') => 'sso.google_'.substr($prop, 11),
            str_starts_with($prop, 'sso_microsoft_') => 'sso.microsoft_'.substr($prop, 14),
            str_starts_with($prop, 'sinta_') => 'sinta.'.substr($prop, 6),
            str_starts_with($prop, 'garuda_') => 'garuda.'.substr($prop, 7),
            str_starts_with($prop, 'tte_') => 'tte.'.substr($prop, 4),
            str_starts_with($prop, 'storage_') => 'storage.'.substr($prop, 8),
            str_starts_with($prop, 'webhook_') => 'webhook.'.substr($prop, 8),
            str_starts_with($prop, 'tenant_') => 'tenant.'.substr($prop, 7),
            str_starts_with($prop, 'comfyui_') => 'comfyui.'.substr($prop, 8),
            str_starts_with($prop, 'ai_') => 'ai.'.substr($prop, 3),
            str_starts_with($prop, 'wa_') => 'wa.'.substr($prop, 3),
            str_starts_with($prop, 'pajak_') => 'pajak.'.substr($prop, 6),
            default => $prop,
        };
    }

    public function render()
    {
        return view('livewire.admin.integrasi', [
            'drivers' => PaymentService::availableDrivers(),
        ]);
    }
}
