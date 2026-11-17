<?php

namespace App\Services\Payment;

use App\Models\AuditLog;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $channel = strtoupper((string) ($customer['method'] ?? 'VA'));
        $externalId = 'simak-'.$tagihan->id.'-'.Str::lower(Str::random(6));
        $amount = (int) $tagihan->nominal;

        // QRIS: QR dinamis via /qr_codes.
        if ($channel === 'QRIS') {
            $response = Http::withBasicAuth($apiKey, '')->timeout(30)->post($base.'/qr_codes', [
                'external_id' => $externalId,
                'type' => 'DYNAMIC',
                'callback_url' => (string) config('app.url').'/payment/callback',
                'amount' => $amount,
            ])->throw()->json();

            return [
                'reference' => $response['external_id'] ?? $externalId,
                'va_number' => $response['id'] ?? null,
                'qr_payload' => $response['qr_string'] ?? null,
                'expires_at' => $response['expires_at'] ?? null,
                'raw' => $response,
            ];
        }

        // E-wallet OVO/DANA/GoPay/ShopeePay/LinkAja via /ewallets/charges.
        if (in_array($channel, ['OVO', 'DANA', 'GOPAY', 'SHOPEEPAY', 'LINKAJA'], true)) {
            $channelCode = $channel === 'GOPAY' ? 'ID_GOPAY' : 'ID_'.$channel;
            $response = Http::withBasicAuth($apiKey, '')->timeout(30)->post($base.'/ewallets/charges', [
                'reference_id' => $externalId,
                'currency' => 'IDR',
                'amount' => $amount,
                'checkout_method' => 'ONE_TIME_PAYMENT',
                'channel_code' => $channelCode,
                'channel_properties' => [
                    'success_redirect_url' => (string) config('app.url').'/keuangan/bayar',
                ],
                'customer_id' => $customer['email'] ?? null,
            ])->throw()->json();

            $actions = collect($response['actions'] ?? [])->keyBy('action');

            return [
                'reference' => $response['reference_id'] ?? $externalId,
                'va_number' => $actions->get('mobile_deeplink_checkout')['url'] ?? $response['id'] ?? null,
                'qr_payload' => $actions->get('qr_checkout_string')['url'] ?? null,
                'expires_at' => null,
                'raw' => $response,
            ];
        }

        $bank = strtoupper((string) Setting::get('pay.bank', (string) config('payment.defaults.bank')));

        $response = Http::withBasicAuth($apiKey, '')->timeout(30)->post($base.'/callback_virtual_accounts', [
            'external_id' => $externalId,
            'bank_code' => $bank,
            'name' => $customer['nama'] ?? 'Mahasiswa',
            'expected_amount' => $amount,
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

        // Hanya status lunas yang diterima: active = VA dibuat/aktif, bukan pembayaran.
        if (! in_array($status, ['paid', 'completed', 'settlement', 'success'], true)) {
            return null;
        }

        return $payload['external_id'] ?? $payload['data']['external_id'] ?? $payload['reference_id'] ?? null;
    }

    public function checkStatus(string $reference): string
    {
        $apiKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.xendit_base_url')), '/');

        try {
            // Pilih endpoint dari kanal pembayaran tersimpan (metode: qris_xendit,
            // ewallet_xendit-*, va_xendit). Tanpa baris Pembayaran, fallback ke VA.
            $metode = strtolower((string) (Pembayaran::where('reference', $reference)->value('metode') ?? ''));

            // QRIS: QR dinamis dibuat via /qr_codes dengan external_id = reference.
            if (str_starts_with($metode, 'qris_')) {
                $res = Http::withBasicAuth($apiKey, '')->timeout(15)->get($base.'/qr_codes/'.$reference);

                if (! $res->successful()) {
                    return 'pending';
                }

                return match (strtolower((string) $res->json('status'))) {
                    'completed', 'paid' => 'verified',
                    'expired' => 'failed',
                    default => 'pending',
                };
            }

            // E-wallet: status charge via /ewallets/charges/{charge_id}.
            // createTransaction menyimpan charge id Xendit di va_number
            // (atau URL action bila gateway mengembalikan deeplink).
            if (str_starts_with($metode, 'ewallet_')) {
                $chargeId = (string) (Pembayaran::where('reference', $reference)->value('va_number') ?? '');

                if ($chargeId === '' || str_contains($chargeId, '://')) {
                    $chargeId = $reference;
                }

                $res = Http::withBasicAuth($apiKey, '')->timeout(15)->get($base.'/ewallets/charges/'.$chargeId);

                if (! $res->successful()) {
                    return 'pending';
                }

                return match (strtoupper((string) $res->json('status'))) {
                    'SUCCEEDED', 'COMPLETED', 'PAID' => 'verified',
                    'FAILED', 'EXPIRED', 'VOIDED', 'REFUNDED' => 'failed',
                    default => 'pending',
                };
            }
        } catch (\Throwable) {
            return 'pending';
        }

        // Fallback lama: Virtual Account via /callback_virtual_accounts/{external_id}.
        try {
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
        } catch (\Throwable) {
            return 'pending';
        }
    }

    /**
     * Refund sisi gateway Xendit (POST /refunds).
     * Return false bila gateway menolak / tak terjangkau — pemanggil
     * (PaymentService::refundByReference) tetap menangani reversal lokal.
     */
    public function refund(string $reference, int $nominal): bool
    {
        $apiKey = (string) Setting::get('pay.server_key', '');
        $base = rtrim((string) Setting::get('pay.base_url', (string) config('payment.defaults.xendit_base_url')), '/');

        try {
            $res = Http::withBasicAuth($apiKey, '')->timeout(30)->post($base.'/refunds', [
                'reference_id' => $reference,
                'amount' => $nominal,
                'reason' => 'REQUESTED_BY_CUSTOMER',
            ]);

            if (! $res->successful()) {
                Log::warning('Refund Xendit ditolak gateway.', ['reference' => $reference, 'status' => $res->status()]);

                $this->catatAuditRefund($reference, $nominal, false, 'status '.$res->status());

                return false;
            }

            $this->catatAuditRefund($reference, $nominal, true);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Refund Xendit gagal: '.$e->getMessage(), ['reference' => $reference]);

            $this->catatAuditRefund($reference, $nominal, false, $e->getMessage());

            return false;
        }
    }

    private function catatAuditRefund(string $reference, int $nominal, bool $hasil, ?string $keterangan = null): void
    {
        try {
            $pembayaranId = null;

            try {
                $pembayaranId = Pembayaran::where('reference', $reference)->value('id');
            } catch (\Throwable) {
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'gateway.refund',
                'model' => $pembayaranId ? Pembayaran::class : self::class,
                'model_id' => $pembayaranId ? (int) $pembayaranId : null,
                'diff_json' => ['reference' => $reference, 'nominal' => $nominal, 'hasil' => $hasil ? 'sukses' : 'gagal', 'gateway' => 'xendit', 'keterangan' => $keterangan],
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }

    public function requiredSettings(): array
    {
        return ['pay.server_key' => 'API Key Xendit'];
    }
}
