<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * M-047: backup portabel (JSON gzip per tabel, chunked) + manifest.
 * Untuk MySQL produksi tetap pakai mysqldump (lihat BACKUP_RESTORE.md);
 * command ini memberi backup teruji lintas-driver (termasuk SQLite/CI).
 */
class BackupCreateCommand extends Command
{
    protected $signature = 'backup:create {--disk=local : Disk storage tujuan (subdir backups)}';

    protected $description = 'Buat backup JSON-gzip semua tabel + manifest jumlah baris.';

    public function handle(): int
    {
        $disk = Storage::disk((string) $this->option('disk'));
        $stamp = now()->format('Ymd-His');
        $dir = "backups/{$stamp}";
        $manifest = ['created_at' => now()->toIso8601String(), 'tables' => []];
        $tables = $this->tables();

        foreach ($tables as $table) {
            $count = 0;
            $rows = [];
            DB::table($table)->orderByRaw('1')->chunk(500, function ($chunk) use (&$count, &$rows): void {
                foreach ($chunk as $row) {
                    $rows[] = (array) $row;
                    $count++;
                }
            });
            $disk->put("{$dir}/{$table}.json.gz", gzencode((string) json_encode($rows), 6) ?: '');
            $manifest['tables'][$table] = $count;
        }

        $disk->put("{$dir}/manifest.json", (string) json_encode($manifest, JSON_PRETTY_PRINT));
        $disk->put('backups/latest.txt', $stamp);

        $this->info('Backup tersimpan: '.$dir.' ('.count($tables).' tabel).');

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function tables(): array
    {
        $conn = DB::connection();
        $tables = match ($conn->getDriverName()) {
            'sqlite' => collect($conn->select("select name from sqlite_master where type='table' and name not like 'sqlite_%'"))->pluck('name')->all(),
            'mysql' => collect($conn->select('show full tables where Table_type = ?', ['BASE TABLE']))->map(fn ($r) => array_values((array) $r)[0])->all(),
            'pgsql' => collect($conn->select("select tablename from pg_tables where schemaname='public'"))->pluck('tablename')->all(),
            default => [],
        };

        return array_values(array_filter($tables, fn ($t) => $t !== 'migrations' && Schema::hasTable($t)));
    }
}
