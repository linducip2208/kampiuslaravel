<?php

namespace App\Console\Commands;

use App\Models\TahunAkademik;
use Illuminate\Console\Command;

/**
 * M-021: rollover periode akademik (idempoten).
 * Tanpa argumen: lanjutkan dari periode aktif (ganjil→genap, genap→ganjil+1 thn).
 */
class AkademikRolloverCommand extends Command
{
    protected $signature = 'akademik:rollover
        {--tahun= : Tahun periode tujuan (mis. 2026)}
        {--semester= : Semester tujuan (ganjil|genap)}';

    protected $description = 'Buat/aktifkan periode akademik berikutnya (idempoten, tepat 1 aktif).';

    public function handle(): int
    {
        $tahun = $this->option('tahun') ? (string) $this->option('tahun') : null;
        $semester = $this->option('semester') ? strtolower((string) $this->option('semester')) : null;

        if ($semester !== null && ! in_array($semester, ['ganjil', 'genap'], true)) {
            $this->error('Semester harus ganjil|genap.');

            return self::FAILURE;
        }

        if (($tahun === null) !== ($semester === null)) {
            $this->error('Isi --tahun dan --semester bersamaan, atau kosongkan keduanya (lanjut dari aktif).');

            return self::FAILURE;
        }

        $periode = TahunAkademik::rollover($tahun, $semester);
        $this->info("Periode aktif: {$periode->tahun} · {$periode->semester} (id {$periode->id}).");

        return self::SUCCESS;
    }
}
