<?php

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Services\KeuanganService;
use App\Services\WaService;
use Illuminate\Console\Command;

/**
 * Kirim pengingat tagihan via WA + inbox notifikasi.
 * H-7: jatuh tempo 7 hari lagi. H+7: lewat 7 hari & masih menunggak.
 * Idempoten via kolom notif_h7_at / notif_hp7_at.
 */
class TagihanReminderCommand extends Command
{
    protected $signature = 'tagihan:ingatkan {--arah=semua : h-7, hp7, atau semua}';

    protected $description = 'Kirim pengingat WA H-7 dan H+7 untuk tagihan mendekati/lewat jatuh tempo.';

    public function handle(WaService $wa): int
    {
        $arah = (string) $this->option('arah');
        $daftar = $arah === 'hp7' ? ['hp7'] : ($arah === 'h-7' ? ['h-7'] : ['h-7', 'hp7']);

        $terkirim = 0;
        $tanpaWa = 0;

        foreach ($daftar as $a) {
            foreach (KeuanganService::tagihanUntukPengingat($a) as $tagihan) {
                $pesan = KeuanganService::pesanPengingat($tagihan, $a);
                $user = $tagihan->mahasiswa->user ?? null;
                $noWa = $user?->no_wa;

                $ok = $noWa ? $wa->send($noWa, $pesan) : false;

                if ($user) {
                    Notifikasi::create([
                        'user_id' => $user->id,
                        'judul' => $a === 'h-7' ? 'Tagihan jatuh tempo 7 hari lagi' : 'Tagihan lewat jatuh tempo',
                        'isi' => $pesan,
                    ]);
                }

                KeuanganService::tandaiPengingatTerkirim($tagihan, $a);

                $ok ? $terkirim++ : $tanpaWa++;
            }
        }

        $this->info("Pengingat terkirim via WA: {$terkirim}; inbox saja (tanpa no WA / gateway off): {$tanpaWa}.");

        return self::SUCCESS;
    }
}
