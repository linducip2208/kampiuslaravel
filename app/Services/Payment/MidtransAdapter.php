<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $orderId = 'SIMAK-'.$tagihan->id.'-'.Str::upper(Str::random(6));
        $gross = (int) $tagihan->nominal;
        $channel = strtolower((string) ($customer['method'] ?? 'va'));

        $body = [
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $gross],
            'customer_details' => [
                'first_name' => $customer['nama'] ?? 'Mahasiswa',
                'email' => $customer['email'] ?? null,
                'phone' => $customer['hp'] ?? null,
            ],
        ];

        if ($channel === 'qris') {
            // QRIS statis/dinamis: respons berisi actions[] (generate-qr-code / deeplink).
            $body['payment_type'] = 'qris';
        } elseif (in_array($channel, ['gopay', 'shopeepay'], true)) {
            $body['payment_type'] = $channel;
            $body[$channel] = ['enable_callback' => true, 'callback_url' => (string) config('app.url').'/payment/callback'];
        } elseif ($channel === 'va' || str_starts_with($channel, 'bri') || str_starts_with($channel, 'bni') || str_starts_with($channel, 'bca') || str_starts_with($channel, 'mandiri') || str_starts_with($channel, 'permata')) {
            $bank = strtolower((string) Setting::get('pay.bank', (string) config('payment.defaults.bank')));
            $body['payment_type'] = 'bank_transfer';
            $body['bank_transfer'] = ['bank' => $bank];
        } else {
            throw new \RuntimeException("Kanal [{$channel}] belum didukung driver Midtrans — gunakan QRIS, GoPay/ShopeePay, VA, atau driver Tripay/Xendit.");
        }

        $response = Http::withBasicAuth($serverKey, '')->timeout(30)->post($base.'/v2/charge', $body)->throw()->json();

        $va = $response['va_numbers'][0] ?? [];
        $actions = collect($response['actions'] ?? [])->keyBy('name');

        return [
            'reference' => $orderId,
            'va_number' => $va['va_number'] ?? $actions->get('generate-qr-code')['url'] ?? $actions->get('deeplink-redirect')['url'] ?? null,
            'qr_payload' => $actions->get('generate-qr-code')['url'] ?? $actions->get('qr-code')['url'] ?? null,
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

    /**
     * Refund sisi gateway Midtrans (POST /v2/{order_id}/refund).
     * Return false bila gateway menolak / tak terjangkau — pemanggil
     * (PaymentService::refundByReference) tetap menangani reversal lokal.
     */
    public function refund(string $reference, int $nominal): bool
    {
        $serverKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.midtrans_base_url')), '/');

        try {
            $res = Http::withBasicAuth($serverKey, '')->timeout(30)->post($base.'/v2/'.$reference.'/refund', [
                'amount' => $nominal,
                'reason' => 'Customer refund request',
            ]);

            if (! $res->successful()) {
                Log::warning('Refund Midtrans ditolak gateway.', ['reference' => $reference, 'status' => $res->status()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Refund Midtrans gagal: '.$e->getMessage(), ['reference' => $reference]);

            return false;
        }
    }
}
