<?php

namespace App\Services;

use App\Models\PmbPendaftar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * VA biaya pendaftaran PMB (tanpa Tagihan: pendaftar belum punya NIM).
 * Nominal = biaya gelombang − potongan beasiswa PMB (bila ada).
 */
class PmbVaService
{
    /**
     * @return array{reference: string, va_number: string, nominal: int}
     */
    public function issueFor(PmbPendaftar $pendaftar): array
    {
        $pendaftar->loadMissing(['gelombang', 'beasiswa']);

        if ($pendaftar->va_reference && $pendaftar->va_number) {
            return [
                'reference' => (string) $pendaftar->va_reference,
                'va_number' => (string) $pendaftar->va_number,
                'nominal' => $this->nominal($pendaftar),
            ];
        }

        $prefix = (string) config('payment.defaults.va_prefix', '8888');
        $reference = 'PMBVA-'.$pendaftar->id.'-'.str()->upper(str()->random(6));
        $vaNumber = $prefix.str_pad((string) $pendaftar->id, 10, '0', STR_PAD_LEFT);

        $pendaftar->update([
            'va_reference' => $reference,
            'va_number' => $vaNumber,
        ]);

        return ['reference' => $reference, 'va_number' => $vaNumber, 'nominal' => $this->nominal($pendaftar)];
    }

    public function nominal(PmbPendaftar $pendaftar): int
    {
        $pendaftar->loadMissing(['gelombang', 'beasiswa']);

        $biaya = (int) ($pendaftar->gelombang->biaya ?? 0);

        if ($pendaftar->beasiswa) {
            $biaya -= $pendaftar->beasiswa->hitungPotongan($biaya);
        }

        return max(0, $biaya);
    }

    /** Lunasi VA pendaftaran berdasarkan reference (idempotent, anti double-callback). */
    public function settleByReference(string $reference): bool
    {
        return DB::transaction(function () use ($reference) {
            $pendaftar = PmbPendaftar::query()->where('va_reference', $reference)->lockForUpdate()->first();

            if (! $pendaftar || $pendaftar->bayar_status === 'lunas') {
                return false;
            }

            $pendaftar->update(['bayar_status' => 'lunas']);

            Log::info('PMB VA lunas', ['no_daftar' => $pendaftar->no_daftar, 'reference' => $reference]);

            return true;
        });
    }

    public function markPaid(PmbPendaftar $pendaftar): void
    {
        try {
            $this->settleByReference((string) $pendaftar->va_reference);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
