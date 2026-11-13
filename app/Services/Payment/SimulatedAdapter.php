<?php

namespace App\Services\Payment;

use App\Models\Tagihan;
use Illuminate\Support\Str;

/** Adapter simulasi untuk development/testing — tanpa API eksternal. */
class SimulatedAdapter implements PaymentGatewayInterface
{
    public function createTransaction(Tagihan $tagihan, array $customer): array
    {
        $prefix = (string) config('payment.defaults.va_prefix', '8888');
        $channel = strtolower((string) ($customer['method'] ?? 'va'));
        $group = PaymentService::channelGroup($channel);
        $suffix = Str::upper(Str::random(6));

        if ($group === 'qris') {
            return [
                'reference' => 'SIM-QR-'.$tagihan->id.'-'.$suffix,
                'va_number' => 'QRIS-SIM-'.$tagihan->id,
                'qr_payload' => 'QRIS.SIM.'.$tagihan->id.'.'.$suffix.'.'.(int) $tagihan->nominal,
                'expires_at' => now()->addHours((int) config('payment.defaults.expiry_hours', 24))->toDateTimeString(),
                'raw' => ['simulated' => true, 'channel' => 'qris'],
            ];
        }

        if ($group === 'ewallet') {
            return [
                'reference' => 'SIM-EW-'.$tagihan->id.'-'.$suffix,
                'va_number' => $channel.'://pay/simak/'.$tagihan->id,
                'qr_payload' => $channel.'://pay/simak/'.$tagihan->id.'?amount='.(int) $tagihan->nominal,
                'expires_at' => now()->addHours((int) config('payment.defaults.expiry_hours', 24))->toDateTimeString(),
                'raw' => ['simulated' => true, 'channel' => $channel],
            ];
        }

        return [
            'reference' => 'SIM-'.$tagihan->id.'-'.Str::upper(Str::random(6)),
            'va_number' => $prefix.str_pad((string) $tagihan->id, 8, '0', STR_PAD_LEFT),
            'expires_at' => now()->addHours((int) config('payment.defaults.expiry_hours', 24))->toDateTimeString(),
            'raw' => ['simulated' => true],
        ];
    }

    public function verifyCallback(array $payload): ?string
    {
        return ($payload['status'] ?? '') === 'paid' ? ($payload['reference'] ?? null) : null;
    }

    public function checkStatus(string $reference): string
    {
        return 'pending';
    }

    public function requiredSettings(): array
    {
        return [];
    }
}
