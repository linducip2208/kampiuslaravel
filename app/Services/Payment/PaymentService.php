<?php

namespace App\Services\Payment;

use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Tagihan;
use RuntimeException;

class PaymentService
{
    /** Resolve driver aktif dari config + settings. Tambah gateway = 1 baris di config/payment.php. */
    public function driver(): PaymentGatewayInterface
    {
        $key = (string) (Setting::get('pay.driver') ?: config('payment.default_driver', 'simulated'));
        $drivers = (array) config('payment.drivers', []);

        if (! isset($drivers[$key]['class'])) {
            throw new RuntimeException(
                "Driver payment [{$key}] tidak terdaftar. Daftarkan di config/payment.php 'drivers'."
            );
        }

        $adapter = app($drivers[$key]['class']);

        if (! $adapter instanceof PaymentGatewayInterface) {
            throw new RuntimeException("Class [{$drivers[$key]['class']}] harus implement PaymentGatewayInterface.");
        }

        return $adapter;
    }

    /** Daftar driver untuk dropdown admin: [key => label]. */
    public static function availableDrivers(): array
    {
        $out = [];

        foreach ((array) config('payment.drivers', []) as $key => $def) {
            $out[$key] = $def['label'] ?? $key;
        }

        return $out;
    }

    public function activeDriverKey(): string
    {
        return (string) (Setting::get('pay.driver') ?: config('payment.default_driver', 'simulated'));
    }

    /** Buat pembayaran VA untuk tagihan, return Pembayaran. */
    public function createVa(Tagihan $tagihan, array $customer): Pembayaran
    {
        $driver = $this->driver();
        $missing = [];

        foreach ($driver->requiredSettings() as $key => $label) {
            if (trim((string) Setting::get($key, '')) === '') {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Kredensial belum lengkap ('.implode(', ', $missing).'). '.
                'Wajib diisi admin di halaman Admin → Integrasi.'
            );
        }

        $result = $driver->createTransaction($tagihan, $customer);

        return Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'reference' => $result['reference'],
            'va_number' => $result['va_number'],
            'metode' => 'va_'.$this->activeDriverKey(),
            'nominal' => $tagihan->nominal,
            'status' => 'pending',
        ])->fresh();
    }

    /** Tandai pembayaran lunas berdasarkan reference, return true bila cocok. */
    public function settleByReference(string $reference): bool
    {
        $pembayaran = Pembayaran::where('reference', $reference)->first();

        if (! $pembayaran || $pembayaran->status === 'verified') {
            return false;
        }

        $pembayaran->update(['status' => 'verified', 'verified_at' => now()]);

        $tagihan = $pembayaran->tagihan;
        $terbayar = (int) $tagihan->terbayar + (int) $pembayaran->nominal;
        $tagihan->update([
            'terbayar' => $terbayar,
            'status' => $terbayar >= (int) $tagihan->nominal ? 'lunas' : 'sebagian',
        ]);

        return true;
    }

    /** Sinkron status dari gateway; lunaskan bila gateway bilang verified. */
    public function syncStatus(Pembayaran $pembayaran): string
    {
        if ($pembayaran->status === 'verified') {
            return 'verified';
        }

        $status = $this->driver()->checkStatus((string) $pembayaran->reference);

        if ($status === 'verified') {
            $this->settleByReference((string) $pembayaran->reference);

            return 'verified';
        }

        if ($status === 'failed') {
            $pembayaran->update(['status' => 'rejected']);
        }

        return $status;
    }
}
