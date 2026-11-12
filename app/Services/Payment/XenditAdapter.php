<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Adapter Xendit Virtual Account.
 * Kredensial + URL dari settings, default dari config/payment.php.
 */
class XenditAdapter implements PaymentGatewayInterface
{
    public function createTransaction(Tagihan $tagihan, array $customer): array
    {
        $apiKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.xendit_base_url')), '/');
        $bank = strtoupper((string) Setting::get('pay.bank', (string) config('payment.defaults.bank')));
        $externalId = 'simak-'.$tagihan->id.'-'.Str::lower(Str::random(6));

        $response = Http::withBasicAuth($apiKey, '')->timeout(30)->post($base.'/callback_virtual_accounts', [
            'external_id' => $externalId,
            'bank_code' => $bank,
            'name' => $customer['nama'] ?? 'Mahasiswa',
            'expected_amount' => (int) $tagihan->nominal,
            'expiration_date' => now()->addHours((int) config('payment.defaults.expiry_hours', 24))->toIso8601String(),
        ])->throw()->json();

        return [
            'reference' => $response['external_id'] ?? $externalId,
            'va_number' => $response['account_number'] ?? null,
            'expires_at' => $response['expiration_date'] ?? null,
            'raw' => $response,
        ];
    }

    public function verifyCallback(array $payload): ?string
    {
        $callbackToken = (string) Setting::get('pay.callback_token', '');
        $given = $payload['callback_token'] ?? $payload['data']['callback_token'] ?? null;

        if ($callbackToken === '' || ! hash_equals($callbackToken, (string) $given)) {
            return null;
        }

        $status = strtolower($payload['status'] ?? $payload['data']['status'] ?? '');

        if (! in_array($status, ['active', 'paid', 'completed'], true)) {
            return null;
        }

        return $payload['external_id'] ?? $payload['data']['external_id'] ?? null;
    }

    public function checkStatus(string $reference): string
    {
        $apiKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.xendit_base_url')), '/');

        $res = Http::withBasicAuth($apiKey, '')->timeout(15)->get($base.'/callback_virtual_accounts/'.$reference);

        if (! $res->successful()) {
            return 'pending';
        }

        return match (strtolower((string) $res->json('status'))) {
            'active', 'pending' => 'pending',
            'expired', 'inactive' => 'failed',
            'completed', 'paid' => 'verified',
            default => 'pending',
        };
    }

    public function requiredSettings(): array
    {
        return ['pay.server_key' => 'API Key Xendit'];
    }
}
