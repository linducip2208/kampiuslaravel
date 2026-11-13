<?php

namespace App\Services;

use App\Models\KalenderEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class KalenderService
{
    /**
     * Peta kunci penguncian => jenis event kalender yang mengendalikannya.
     *
     * @var array<string, list<string>>
     */
    public const KUNCI_JENIS = [
        'krs' => ['krs'],
        'pembayaran' => ['pembayaran'],
        'sidang' => ['sidang'],
    ];

    /**
     * @return list<string>
     */
    public static function kunciTersedia(): array
    {
        return array_keys(self::KUNCI_JENIS);
    }

    /**
     * True bila tidak ada jadwal pembatas (= terbuka, kompatibel mundur),
     * atau hari ini berada di dalam salah satu rentang event terkait.
     */
    public static function isOpen(string $kunci, ?Carbon $tanggal = null): bool
    {
        if (! Schema::hasTable('kalender_events')) {
            return true;
        }

        $jenis = self::KUNCI_JENIS[$kunci] ?? [];

        if ($jenis === []) {
            return true;
        }

        try {
            $events = KalenderEvent::whereIn('jenis', $jenis)->get(['tgl_mulai', 'tgl_selesai']);
        } catch (\Throwable) {
            return true;
        }

        if ($events->isEmpty()) {
            return true;
        }

        $hari = ($tanggal ?? today())->startOfDay();

        return $events->contains(function (KalenderEvent $e) use ($hari): bool {
            $mulai = Carbon::parse($e->tgl_mulai)->startOfDay();
            $selesai = $e->tgl_selesai ? Carbon::parse($e->tgl_selesai)->endOfDay() : $mulai->copy()->endOfDay();

            return $hari->gte($mulai->startOfDay()) && $hari->lte($selesai);
        });
    }

    /**
     * @return array<string, bool> peta kunci => status terbuka
     */
    public static function locks(): array
    {
        $out = [];

        foreach (self::kunciTersedia() as $kunci) {
            $out[$kunci] = self::isOpen($kunci);
        }

        return $out;
    }

    public static function closedMessage(string $kunci): string
    {
        return match ($kunci) {
            'krs' => 'Pengajuan KRS ditutup: di luar jadwal kalender akademik.',
            'pembayaran' => 'Pembayaran ditutup: di luar jadwal kalender akademik.',
            'sidang' => 'Pendaftaran sidang ditutup: di luar jadwal kalender akademik.',
            default => 'Layanan ditutup: di luar jadwal kalender akademik.',
        };
    }
}
