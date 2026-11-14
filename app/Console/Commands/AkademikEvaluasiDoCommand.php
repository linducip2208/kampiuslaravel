<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Services\AkademikService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * M-023: evaluasi DO otomatis (aturan konservatif, terdokumentasi).
 * Tanpa --terapkan: hanya daftar kandidat. Dengan --terapkan: ubah
 * status aktif→do + AuditLog per mahasiswa (idempoten: hanya status aktif).
 */
class AkademikEvaluasiDoCommand extends Command
{
    protected $signature = 'akademik:evaluasi-do
        {--min-semester=4 : Minimal semester tempuh (nilai terkunci)}
        {--batas-ipk=2.0 : IPK di bawah ini = kandidat}
        {--terapkan : Terapkan status do + audit log}';

    protected $description = 'Evaluasi kandidat DO (kering by default; --terapkan untuk eksekusi).';

    public function handle(): int
    {
        $kandidat = AkademikService::kandidatDo((int) $this->option('min-semester'), (float) $this->option('batas-ipk'));

        if ($kandidat->isEmpty()) {
            $this->info('Tidak ada kandidat DO.');

            return self::SUCCESS;
        }

        $this->table(['mahasiswa_id', 'nim', 'ipk', 'semester'], $kandidat->all());

        if (! $this->option('terapkan')) {
            $this->comment('Mode kering: tambah --terapkan untuk mengubah status.');

            return self::SUCCESS;
        }

        $diterapkan = 0;

        foreach ($kandidat as $k) {
            $affected = DB::transaction(function () use ($k): int {
                $mhs = Mahasiswa::whereKey($k['mahasiswa_id'])->lockForUpdate()->first();

                if (! $mhs || $mhs->status !== Mahasiswa::STATUS_AKTIF) {
                    return 0;
                }

                $mhs->update(['status' => Mahasiswa::STATUS_DO]);

                AuditLog::create([
                    'user_id' => null,
                    'action' => 'mahasiswa.status.do',
                    'model' => Mahasiswa::class,
                    'model_id' => $mhs->id,
                    'diff_json' => ['ipk' => $k['ipk'], 'semester' => $k['semester'], 'via' => 'akademik:evaluasi-do'],
                    'created_at' => now(),
                ]);

                return 1;
            });

            $diterapkan += $affected;
        }

        $this->info("Diterapkan: {$diterapkan} mahasiswa berstatus do.");

        return self::SUCCESS;
    }
}
