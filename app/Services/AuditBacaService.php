<?php

namespace App\Services;

use App\Models\AuditBaca;
use Illuminate\Support\Facades\Schema;

/**
 * Audit baca dokumen mahasiswa sensitif (khs, transkrip, ...):
 * siapa membaca dokumen apa dan kapan. Audit tak boleh memecah
 * request, jadi semua kegagalan ditelan.
 */
class AuditBacaService
{
    public static function catat(?int $userId, string $model, int $modelId, ?string $ip = null): void
    {
        if ($userId === null) {
            return;
        }

        if (! Schema::hasTable('audit_baca')) {
            return;
        }

        try {
            AuditBaca::create([
                'user_id' => $userId,
                'model' => $model,
                'model_id' => $modelId,
                'dibaca_at' => now(),
                'ip' => $ip !== null ? substr($ip, 0, 45) : null,
            ]);
        } catch (\Throwable) {
        }

        try {
            if (random_int(1, 100) === 1) {
                self::retensi();
            }
        } catch (\Throwable) {
        }
    }

    /** Hapus entri lebih tua dari 365 hari. */
    public static function retensi(): void
    {
        try {
            AuditBaca::where('dibaca_at', '<', now()->subDays(365))->delete();
        } catch (\Throwable) {
        }
    }
}
