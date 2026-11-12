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

    public string $comfyui_url = 'http://127.0.0.1:8188';

    public string $comfyui_model = 'wan2.1';

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

    public function mount(): void
    {
        foreach (['pay_driver', 'pay_server_key', 'pay_base_url', 'pay_bank', 'pay_callback_token', 'pay_merchant_code', 'pay_private_key', 'pay_method', 'fcm_project_id', 'fcm_service_account_path', 'pddikti_base_url', 'pddikti_username', 'pddikti_password', 'comfyui_url', 'comfyui_model'] as $key) {
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
        ]);

        foreach (['pay_driver', 'pay_server_key', 'pay_base_url', 'pay_bank', 'pay_callback_token', 'pay_merchant_code', 'pay_private_key', 'pay_method', 'fcm_project_id', 'fcm_service_account_path', 'pddikti_base_url', 'pddikti_username', 'pddikti_password', 'comfyui_url', 'comfyui_model'] as $key) {
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
            str_starts_with($prop, 'comfyui_') => 'comfyui.'.substr($prop, 8),
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
