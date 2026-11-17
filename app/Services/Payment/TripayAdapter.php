<?php

namespace App\Services\Payment;

use App\Models\Setting;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Adapter Tripay (closed transaction VA / gerai / QRIS).
 * Kredensial + URL dari settings (halaman admin Integrasi),
 * default/fallback dari config/payment.php — tidak ada hardcode.
 *
 * Setting yang dipakai:
 *  pay.server_key    = API Key Tripay (header Bearer)
 *  pay.private_key   = Private Key (HMAC signature)
 *  pay.merchant_code = Kode merchant Tripay
 *  pay.base_url      = https://tripay.co.id/api-sandbox/ | https://tripay.co.id/api/
 *  pay.method        = channel default, mis. BRIVA (lihat daftar channel Tripay)
 */
class TripayAdapter implements PaymentGatewayInterface
{
    public function createTransaction(Tagihan $tagihan, array $customer): array
    {
        $apiKey = (string) Setting::get('pay.server_key', '');
        $privateKey = (string) Setting::get('pay.private_key', '');
        $merchantCode = (string) Setting::get('pay.merchant_code', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.tripay_base_url')), '/');
        $method = strtoupper((string) ($customer['method'] ?? Setting::get('pay.method', 'BRIVA')));
        $merchantRef = 'SIMAK-'.$tagihan->id.'-'.Str::upper(Str::random(6));
        $amount = (int) $tagihan->nominal;

        $response = Http::withToken($apiKey)->timeout(30)->post($base.'/transaction/create', [
            'method' => $method,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $customer['nama'] ?? 'Mahasiswa',
            'customer_email' => $customer['email'] ?? 'mahasiswa@kampus.ac.id',
            'customer_phone' => $customer['hp'] ?? '081234567890',
            'order_items' => [
                ['sku' => $tagihan->jenis, 'name' => 'Tagihan '.$tagihan->jenis, 'price' => $amount, 'quantity' => 1],
            ],
            'expired_time' => now()->addHours((int) config('payment.defaults.expiry_hours', 24))->timestamp,
            'signature' => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey),
        ])->throw()->json();

        if (empty($response['success'])) {
            throw new \RuntimeException('Tripay: '.($response['message'] ?? 'gagal membuat transaksi.'));
        }

        $data = $response['data'];

        return [
            'reference' => $data['merchant_ref'] ?? $merchantRef,
            'va_number' => $data['pay_code'] ?? $data['qr_url'] ?? null,
            'expires_at' => isset($data['expired_time'])
                ? date('Y-m-d H:i:s', (int) $data['expired_time'])
                : now()->addHours((int) config('payment.defaults.expiry_hours', 24))->toDateTimeString(),
            'raw' => $data,
        ];
    }

    public function verifyCallback(array $payload): ?string
    {
        $privateKey = (string) Setting::get('pay.private_key', '');
        $signature = $payload['_signature'] ?? null;
        $raw = $payload['_raw'] ?? null;

        if ($privateKey === '' || ! $signature || ! $raw) {
            return null;
        }

        if (! hash_equals(hash_hmac('sha256', $raw, $privateKey), (string) $signature)) {
            return null;
        }

        if (($payload['_event'] ?? '') !== 'payment_status') {
            return null;
        }

        return ($payload['status'] ?? '') === 'PAID' ? ($payload['merchant_ref'] ?? null) : null;
    }

    public function checkStatus(string $reference): string
    {
        $apiKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.tripay_base_url')), '/');

        $res = Http::withToken($apiKey)->timeout(15)->get($base.'/transaction/merchant-detail', [
            'merchant_ref' => $reference,
        ]);

        if (! $res->successful() || empty($res->json('success'))) {
            return 'pending';
        }

        return match (strtoupper((string) $res->json('data.status'))) {
            'PAID' => 'verified',
            'EXPIRED', 'FAILED', 'REFUND' => 'failed',
            default => 'pending',
        };
    }

    public function requiredSettings(): array
    {
        return [
            'pay.merchant_code' => 'Kode merchant Tripay',
            'pay.server_key' => 'API Key Tripay',
            'pay.private_key' => 'Private Key Tripay',
        ];
    }

    /**
     * Tripay TIDAK menyediakan endpoint refund publik — refund dilakukan
     * manual via panel Tripay. Selalu false + dicatat agar pemanggil tahu
     * reversal lokal tetap jalan tapi dana dikembalikan manual.
     */
    public function refund(string $reference, int $nominal): bool
    {
        Log::info('Refund Tripay manual via panel (tidak ada API refund publik).', [
            'reference' => $reference,
            'nominal' => $nominal,
        ]);

        return false;
    }
}
