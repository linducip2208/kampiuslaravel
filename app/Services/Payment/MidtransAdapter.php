<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Adapter Midtrans Core API (bank transfer VA).
 * Kredensial + URL dari settings (halaman admin Integrasi),
 * default/fallback dari config/payment.php — tidak ada hardcode.
 */
class MidtransAdapter implements PaymentGatewayInterface
{
    public function createTransaction(Tagihan $tagihan, array $customer): array
    {
        $serverKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.midtrans_base_url')), '/');
        $bank = strtolower((string) Setting::get('pay.bank', (string) config('payment.defaults.bank')));
        $orderId = 'SIMAK-'.$tagihan->id.'-'.Str::upper(Str::random(6));

        $response = Http::withBasicAuth($serverKey, '')->timeout(30)->post($base.'/v2/charge', [
            'payment_type' => 'bank_transfer',
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int) $tagihan->nominal],
            'customer_details' => [
                'first_name' => $customer['nama'] ?? 'Mahasiswa',
                'email' => $customer['email'] ?? null,
                'phone' => $customer['hp'] ?? null,
            ],
            'bank_transfer' => ['bank' => $bank],
        ])->throw()->json();

        $va = $response['va_numbers'][0] ?? [];

        return [
            'reference' => $orderId,
            'va_number' => $va['va_number'] ?? null,
            'expires_at' => now()->addHours((int) config('payment.defaults.expiry_hours', 24))->toDateTimeString(),
            'raw' => $response,
        ];
    }

    public function verifyCallback(array $payload): ?string
    {
        $serverKey = (string) Setting::get('pay.server_key', '');
        $orderId = $payload['order_id'] ?? null;
        $signature = $payload['signature_key'] ?? null;

        if (! $orderId || ! $signature) {
            return null;
        }

        $expected = hash('sha512', $orderId.($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$serverKey);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        return in_array($payload['transaction_status'] ?? '', ['capture', 'settlement'], true) ? $orderId : null;
    }

    public function checkStatus(string $reference): string
    {
        $serverKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.midtrans_base_url')), '/');

        $res = Http::withBasicAuth($serverKey, '')->timeout(15)->get($base.'/v2/'.$reference.'/status');

        if (! $res->successful()) {
            return 'pending';
        }

        return match ($res->json('transaction_status')) {
            'capture', 'settlement' => 'verified',
            'deny', 'cancel', 'expire' => 'failed',
            default => 'pending',
        };
    }

    public function requiredSettings(): array
    {
        return ['pay.server_key' => 'Server Key Midtrans'];
    }
}
