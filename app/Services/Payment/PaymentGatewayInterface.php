<?php

namespace App\Services\Payment;

use App\Models\Tagihan;

interface PaymentGatewayInterface
{
    /** Buat transaksi bayar, return ['reference' => ..., 'va_number' => ..., 'expires_at' => ...]. */
    public function createTransaction(Tagihan $tagihan, array $customer): array;

    /** Verifikasi signature callback, return reference bila valid & lunas, null bila tidak. */
    public function verifyCallback(array $payload): ?string;

    /** Cek status ke gateway: 'pending' | 'verified' | 'failed'. */
    public function checkStatus(string $reference): string;

    /**
     * Daftar setting wajib driver ini: ['pay.server_key' => 'Label untuk admin'].
     * Dipakai PaymentService untuk menolak transaksi bila admin belum mengisi.
     *
     * @return array<string, string>
     */
    public function requiredSettings(): array;
}
