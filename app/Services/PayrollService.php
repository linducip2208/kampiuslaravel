<?php

namespace App\Services;

use App\Models\PayrollPeriode;
use App\Models\PayrollSlip;
use Illuminate\Support\Facades\DB;

/**
 * M-038: penggajian — slip per pegawai per periode, periode final terkunci.
 * Total = pokok + tunjangan − potongan (floor 0).
 */
class PayrollService
{
    public static function total(int $pokok, int $tunjangan, int $potongan): int
    {
        return max(0, $pokok + $tunjangan - $potongan);
    }

    public static function simpanSlip(int $periodeId, int $userId, int $pokok, int $tunjangan, int $potongan): PayrollSlip
    {
        return DB::transaction(function () use ($periodeId, $userId, $pokok, $tunjangan, $potongan): PayrollSlip {
            $periode = PayrollPeriode::whereKey($periodeId)->lockForUpdate()->firstOrFail();

            if ($periode->terkunci()) {
                throw new \RuntimeException('Periode payroll sudah final; slip terkunci.');
            }

            if ($pokok < 0 || $tunjangan < 0 || $potongan < 0) {
                throw new \InvalidArgumentException('Komponen gaji tidak boleh negatif.');
            }

            return PayrollSlip::updateOrCreate(
                ['periode_id' => $periode->id, 'user_id' => $userId],
                [
                    'gaji_pokok' => $pokok,
                    'tunjangan' => $tunjangan,
                    'potongan' => $potongan,
                    'total_bersih' => self::total($pokok, $tunjangan, $potongan),
                    'status' => PayrollSlip::where('periode_id', $periode->id)->where('user_id', $userId)->value('status') ?? 'draft',
                ]
            );
        });
    }

    public static function kunciPeriode(int $periodeId): PayrollPeriode
    {
        return DB::transaction(function () use ($periodeId): PayrollPeriode {
            $periode = PayrollPeriode::whereKey($periodeId)->lockForUpdate()->firstOrFail();
            $periode->update(['status' => PayrollPeriode::STATUS_FINAL]);
            PayrollSlip::where('periode_id', $periode->id)->update(['status' => PayrollPeriode::STATUS_FINAL]);

            return $periode->fresh();
        });
    }

    public static function totalPeriode(int $periodeId): int
    {
        try {
            return (int) PayrollSlip::where('periode_id', $periodeId)->sum('total_bersih');
        } catch (\Throwable) {
            return 0;
        }
    }
}
