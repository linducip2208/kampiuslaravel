<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\KeuanganService;
use Illuminate\Console\Command;

/**
 * Terapkan denda otomatis ke tagihan menunggak (idempoten: nilai ditimpa, bukan diakumulasi).
 * Dijadwalkan harian; aman dijalankan ulang manual.
 */
class TerapkanDendaCommand extends Command
{
    protected $signature = 'denda:terapkan {--tahun= : ID tahun akademik (opsional, default semua)}';

    protected $description = 'Terapkan denda otomatis ke tagihan lewat jatuh tempo sesuai aturan aktif.';

    public function handle(): int
    {
        $tahunId = $this->option('tahun') !== null ? (int) $this->option('tahun') : null;

        $result = KeuanganService::applyDendaOtomatis($tahunId);

        AuditLog::create([
            'user_id' => null,
            'action' => 'tagihan.terapkan_denda_otomatis',
            'model' => null,
            'model_id' => null,
            'diff_json' => $result,
            'created_at' => now(),
        ]);

        $this->info("Denda diterapkan ke {$result['updated']} tagihan (total Rp ".number_format($result['total_denda'], 0, ',', '.').').');

        return self::SUCCESS;
    }
}
