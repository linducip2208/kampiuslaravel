<?php

namespace App\Console\Commands;

use App\Services\Ai\RagService;
use Illuminate\Console\Command;

/**
 * Ide bank: evaluasi RAG berkala — skor kecocokan chunk vs jawaban emas
 * (deterministik, tanpa panggil AI). Pasangan emas dari file JSON:
 * [{"tanya": "...", "harap": "..."}].
 */
class RagEvaluasiCommand extends Command
{
    protected $signature = 'rag:evaluasi {--file= : Path file JSON pasangan emas} {--prodi= : Filter prodi_id}';

    protected $description = 'Evaluasi kecocokan retrieval RAG terhadap pasangan Q/A emas.';

    public function handle(): int
    {
        $file = (string) $this->option('file');

        if ($file === '' || ! is_file($file)) {
            $this->error('Berikan --file=path/emas.json berisi [{"tanya":"...","harap":"..."}].');

            return self::FAILURE;
        }

        $pasangan = json_decode((string) file_get_contents($file), true);

        if (! is_array($pasangan)) {
            $this->error('File JSON tidak valid.');

            return self::FAILURE;
        }

        $prodi = $this->option('prodi') !== null ? (int) $this->option('prodi') : null;
        $hasil = RagService::evaluasi($pasangan, $prodi);

        foreach ($hasil['detail'] as $d) {
            $this->line(sprintf('%.3f | %s | %s', $d['skor'], $d['tanya'], implode(',', $d['sumber'])));
        }

        $this->info(sprintf('Rata-rata: %.3f dari %d pasangan.', $hasil['skor'], count($hasil['detail'])));

        return self::SUCCESS;
    }
}
