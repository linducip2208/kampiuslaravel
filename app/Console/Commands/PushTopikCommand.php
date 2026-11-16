<?php

namespace App\Console\Commands;

use App\Services\NotifikasiService;
use Illuminate\Console\Command;

/**
 * Ide bank: kirim pengumuman ke topik FCM prodi (prodi_{id}).
 * Tanpa kredensial FCM: dilewati dengan pesan (exit 0).
 */
class PushTopikCommand extends Command
{
    protected $signature = 'push:topik {prodi : ID prodi} {judul : Judul} {isi : Isi}';

    protected $description = 'Kirim pengumuman ke topik FCM prodi.';

    public function handle(NotifikasiService $notif): int
    {
        $ok = $notif->broadcastTopik((int) $this->argument('prodi'), (string) $this->argument('judul'), (string) $this->argument('isi'));

        $this->info($ok ? 'Push topik terkirim.' : 'FCM belum dikonfigurasi — push dilewati.');

        return self::SUCCESS;
    }
}
