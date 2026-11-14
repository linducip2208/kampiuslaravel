<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * M-047: verifikasi backup — manifest terbaca, setiap tabel gunzip +
 * JSON valid, jumlah baris cocok dengan database saat ini.
 */
class BackupVerifyCommand extends Command
{
    protected $signature = 'backup:verify {stamp? : Stamp backup (default: isi backups/latest.txt)} {--disk=local}';

    protected $description = 'Verifikasi integritas backup terakhir (atau stamp tertentu).';

    public function handle(): int
    {
        $disk = Storage::disk((string) $this->option('disk'));
        $stamp = $this->argument('stamp') ? (string) $this->argument('stamp') : trim((string) $disk->get('backups/latest.txt'));
        $dir = "backups/{$stamp}";

        if (! $disk->exists("{$dir}/manifest.json")) {
            $this->error("Manifest tidak ditemukan: {$dir}/manifest.json.");

            return self::FAILURE;
        }

        $manifest = json_decode((string) $disk->get("{$dir}/manifest.json"), true);

        if (! is_array($manifest) || ! isset($manifest['tables']) || ! is_array($manifest['tables'])) {
            $this->error('Manifest korup (bukan JSON tabel).');

            return self::FAILURE;
        }

        $errors = 0;

        foreach ($manifest['tables'] as $table => $expected) {
            $path = "{$dir}/{$table}.json.gz";

            if (! $disk->exists($path)) {
                $this->error("Berkas hilang: {$path}.");
                $errors++;

                continue;
            }

            $raw = gzdecode((string) $disk->get($path));

            if ($raw === false) {
                $this->error("Gunzip gagal: {$path}.");
                $errors++;

                continue;
            }

            $rows = json_decode($raw, true);

            if (! is_array($rows)) {
                $this->error("JSON korup: {$path}.");
                $errors++;

                continue;
            }

            $actual = DB::table($table)->count();

            if (count($rows) !== (int) $expected || $actual !== (int) $expected) {
                $this->error("Jumlah baris {$table} tak cocok (backup=".count($rows).", manifest={$expected}, db={$actual}).");
                $errors++;

                continue;
            }

            $this->line("OK {$table} ({$actual} baris).");
        }

        if ($errors > 0) {
            $this->error("Verifikasi GAGAL: {$errors} tabel bermasalah.");

            return self::FAILURE;
        }

        $this->info("Backup {$stamp} VALID (".count($manifest['tables']).' tabel).');

        return self::SUCCESS;
    }
}
