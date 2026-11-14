<?php

namespace App\Console\Commands;

use App\Services\QuizService;
use Illuminate\Console\Command;

/**
 * M-051: segel otomatis attempt CBT yang kehabisan waktu/deadline.
 * Disarankan terjadwal tiap jam (recovery server-side bila klien putus).
 */
class KuisSealKedaluwarsaCommand extends Command
{
    protected $signature = 'kuis:seal-kedaluwarsa';

    protected $description = 'Selesaikan otomatis attempt kuis yang kehabisan waktu.';

    public function handle(): int
    {
        $this->info('Attempt disegel: '.QuizService::sealKedaluwarsa().'.');

        return self::SUCCESS;
    }
}
