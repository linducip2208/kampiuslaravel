<?php

namespace App\Console\Commands;

use App\Models\Aset;
use App\Services\AsetService;
use Illuminate\Console\Command;

/**
 * M-036: susutkan semua aset 1 bulan berjalan (idempoten per bulan).
 * Disarankan terjadwal tiap awal bulan.
 */
class AsetSusutkanCommand extends Command
{
    protected $signature = 'aset:susutkan';

    protected $description = 'Bukukan penyusutan bulan berjalan untuk semua aset (idempoten).';

    public function handle(): int
    {
        $total = 0;
        $dihitung = 0;

        foreach (Aset::orderBy('id')->cursor() as $aset) {
            $susut = AsetService::susutkan($aset);

            if ($susut > 0) {
                $dihitung++;
                $total += $susut;
            }
        }

        $this->info("Penyusutan dibukukan: {$dihitung} aset, total Rp{$total}.");

        return self::SUCCESS;
    }
}
