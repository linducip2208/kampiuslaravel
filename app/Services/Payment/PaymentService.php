<?php

namespace App\Services\Payment;

use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Tagihan;
use App\Services\KeuanganService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

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
        return $this->createTransaction($tagihan, $customer, 'va');
    }

    /** Buat pembayaran QRIS (QR dinamis dari gateway). */
    public function createQris(Tagihan $tagihan, array $customer): Pembayaran
    {
        return $this->createTransaction($tagihan, $customer + ['method' => 'QRIS'], 'qris');
    }

    /**
     * Buat pembayaran e-wallet (gopay / ovo / dana / shopeepay / linkaja).
     * Driver yang belum mendukung wallet akan menolak dengan pesan jelas.
     */
    public function createEwallet(Tagihan $tagihan, array $customer, string $wallet): Pembayaran
    {
        return $this->createTransaction($tagihan, $customer + ['method' => $wallet], 'ewallet');
    }

    /** Kelompok kanal: va | qris | ewallet — dipakai untuk prefix metode. */
    public static function channelGroup(string $method): string
    {
        $method = strtolower($method);

        if ($method === 'qris') {
            return 'qris';
        }

        if (in_array($method, ['gopay', 'ovo', 'dana', 'shopeepay', 'linkaja'], true)) {
            return 'ewallet';
        }

        return 'va';
    }

    /** Buat transaksi via driver; reuse pembayaran pending kanal yang sama (anti dobel). */
    private function createTransaction(Tagihan $tagihan, array $customer, string $group): Pembayaran
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

        // Reuse pembayaran pending kanal yang sama (anti dobel-klik).
        // Hindari LIKE wildcard '_' (perilaku beda SQLite vs MySQL): filter di PHP.
        $existing = Pembayaran::where('tagihan_id', $tagihan->id)
            ->where('status', 'pending')
            ->latest('id')
            ->get()
            ->first(fn (Pembayaran $p) => str_starts_with((string) $p->metode, $group.'_'));

        if ($existing) {
            return $existing;
        }

        $result = $this->callDriverWithRetry(fn () => $driver->createTransaction($tagihan, $customer));

        $suffix = $group === 'ewallet' && isset($customer['method'])
            ? '-'.strtolower((string) $customer['method'])
            : '';

        return Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'reference' => $result['reference'],
            'va_number' => $result['va_number'] ?? null,
            'qr_payload' => $result['qr_payload'] ?? $result['qr_string'] ?? null,
            'expires_at' => $result['expires_at'] ?? null,
            'metode' => $group.'_'.$this->activeDriverKey().$suffix,
            'nominal' => $tagihan->sisa() > 0 ? $tagihan->sisa() : $tagihan->nominal,
            'status' => 'pending',
        ])->fresh();
    }

    /** Tandai pembayaran lunas berdasarkan reference. Idempotent, aman dari double-settle. */
    public function settleByReference(string $reference): bool
    {
        return DB::transaction(function () use ($reference) {
            $pembayaran = Pembayaran::where('reference', $reference)->lockForUpdate()->first();

            if (! $pembayaran || $pembayaran->status === 'verified') {
                return false;
            }

            $pembayaran->update(['status' => 'verified', 'verified_at' => now()]);

            $tagihan = Tagihan::whereKey($pembayaran->tagihan_id)->lockForUpdate()->firstOrFail();
            $terbayar = (int) $tagihan->terbayar + (int) $pembayaran->nominal;
            $tagihan->update([
                'terbayar' => $terbayar,
                'status' => $terbayar >= $tagihan->totalTagihan() ? 'lunas' : 'sebagian',
            ]);

            KeuanganService::postPenerimaan($pembayaran->fresh());

            return true;
        });
    }

    /** Refund lokal (reversal terbayar + jurnal). Gateway-side refund menyusul manual bila perlu. */
    public function refundByReference(string $reference, int $by, ?string $reason = null): bool
    {
        $pembayaran = Pembayaran::where('reference', $reference)->first();

        if (! $pembayaran) {
            return false;
        }

        return KeuanganService::refundPembayaran($pembayaran->id, $by, $reason);
    }

    /**
     * Panggil driver dengan retry 3x untuk kegagalan transient (network/timeout).
     * Gagal permanen setelah 3x dibungkus RuntimeException agar controller
     * merespons 422/502 yang konsisten (bukan 500).
     *
     * @template T
     *
     * @param  callable(): T  $call
     * @return T
     */
    private function callDriverWithRetry(callable $call): mixed
    {
        $attempts = 0;

        do {
            try {
                return $call();
            } catch (Throwable $e) {
                $attempts++;
                report($e);

                if ($attempts >= 3) {
                    throw new RuntimeException('Gateway pembayaran tidak dapat dihubungi setelah 3x percobaan.', 0, $e);
                }

                usleep(200000);
            }
        } while (true);
    }

    /** Sinkron status dari gateway; lunaskan bila gateway bilang verified. */
    public function syncStatus(Pembayaran $pembayaran): string
    {
        if ($pembayaran->status === 'verified') {
            return 'verified';
        }

        $status = $this->callDriverWithRetry(fn () => $this->driver()->checkStatus((string) $pembayaran->reference));

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
