<?php

namespace App\Console\Commands;

use App\Mail\TracerUndanganMail;
use App\Models\Mahasiswa;
use App\Services\MailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Ide bank: undangan tracer via email untuk alumni ~6 bulan pasca-lulus
 * yang belum mengisi tracer. Proxy tanggal lulus = updated_at (kolom
 * khusus tanggal lulus belum ada — lihat DEVELOPMENT_IDEAS).
 * Tanpa SMTP: dry-run (daftar target saja), exit 0.
 */
class TracerIngatkanCommand extends Command
{
    protected $signature = 'tracer:ingatkan {--bulan=6 : Ambang bulan pasca-lulus}';

    protected $description = 'Kirim undangan tracer study ke alumni yang belum mengisi (guard konfigurasi email).';

    public function handle(): int
    {
        $bulan = max(1, (int) $this->option('bulan'));

        $targets = Mahasiswa::with('user')
            ->where('status', 'lulus')
            ->where('updated_at', '<=', now()->subMonths($bulan))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('tracer_alumni')->whereColumn('tracer_alumni.mahasiswa_id', 'mahasiswa.id'))
            ->orderBy('id')
            ->get();

        if ($targets->isEmpty()) {
            $this->info('Tidak ada alumni target.');

            return self::SUCCESS;
        }

        if (! MailService::terkonfigurasi()) {
            $this->info("Email belum dikonfigurasi (dry-run): {$targets->count()} alumni target.");

            foreach ($targets as $mhs) {
                $this->line("- {$mhs->nim} <".($mhs->user->email ?? '-').'>');
            }

            return self::SUCCESS;
        }

        $terkirim = 0;

        foreach ($targets as $mhs) {
            $email = $mhs->user->email ?? null;

            if (! $email) {
                continue;
            }

            try {
                Mail::to($email)->send(new TracerUndanganMail(
                    (string) ($mhs->user->name ?? $mhs->nim),
                    (string) $mhs->angkatan,
                ));
                $terkirim++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Undangan tracer terkirim: {$terkirim} dari {$targets->count()} target.");

        return self::SUCCESS;
    }
}
