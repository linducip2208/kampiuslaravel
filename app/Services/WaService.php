<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\PmbPendaftar;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CRM follow-up WA pendaftar PMB.
 * Tanpa gateway terkonfigurasi: pesan dicatat (simulasi) agar alur CRM tetap jalan.
 */
class WaService
{
    /** True bila gateway WA belum dikonfigurasi (pengiriman hanya simulasi). */
    public static function isSimulated(): bool
    {
        $url = trim((string) (Setting::get('wa.gateway_url', '') ?? ''));
        $token = trim((string) (Setting::get('wa.token', '') ?? ''));

        return $url === '' || $token === '';
    }

    public function send(string $hp, string $pesan): bool
    {
        if (self::isSimulated()) {
            Log::info('WA simulated (gateway belum dikonfigurasi)', ['hp' => $hp, 'pesan' => $pesan]);

            return true;
        }

        $url = (string) (Setting::get('wa.gateway_url', '') ?? '');
        $token = (string) (Setting::get('wa.token', '') ?? '');

        try {
            $res = Http::withToken($token)->timeout(15)->post($url, [
                'to' => $hp,
                'message' => $pesan,
            ]);

            $ok = $res->successful();

            $this->catatAuditKirim($hp, $ok, $ok ? null : 'status '.$res->status());

            return $ok;
        } catch (Throwable $e) {
            Log::warning('WA gateway gagal', ['hp' => $hp, 'error' => $e->getMessage()]);

            $this->catatAuditKirim($hp, false, $e->getMessage());

            return false;
        }
    }

    private function catatAuditKirim(string $hp, bool $hasil, ?string $keterangan = null): void
    {
        try {
            $digits = preg_replace('/\D/', '', $hp) ?? '';

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'wa.kirim',
                'model' => null,
                'model_id' => null,
                'diff_json' => ['hp' => '****'.substr($digits, -4), 'hasil' => $hasil ? 'terkirim' : 'gagal', 'keterangan' => $keterangan],
                'created_at' => now(),
            ]);
        } catch (Throwable) {
        }
    }

    public function welcome(PmbPendaftar $pendaftar): bool
    {
        $pesan = "Halo {$pendaftar->nama}, pendaftaran PMB Anda {$pendaftar->no_daftar} diterima. ".
            'VA biaya pendaftaran: '.($pendaftar->va_number ?? '-').'. '.
            'Cek status: '.route('pmb.cek', ['no_daftar' => $pendaftar->no_daftar]);

        return $this->send((string) $pendaftar->hp, $pesan);
    }

    public function followUp(PmbPendaftar $pendaftar): bool
    {
        $pesan = "Halo {$pendaftar->nama}, pengingat PMB {$pendaftar->no_daftar} (status: {$pendaftar->status}). ";

        if (($pendaftar->bayar_status ?? 'belum_bayar') !== 'lunas') {
            $pesan .= 'Segera lunasi VA '.($pendaftar->va_number ?? '-').' agar berkas diproses. ';
        }

        $pesan .= 'Butuh bantuan? Balas pesan ini.';

        $ok = $this->send((string) $pendaftar->hp, $pesan);

        if ($ok && ! self::isSimulated()) {
            $pendaftar->update(['last_follow_up_at' => now()]);
        }

        return $ok;
    }

    /** Kirim follow-up ke pendaftar yang belum difollow-up. Return jumlah terkirim. */
    public function broadcastFollowUp(int $limit = 100): int
    {
        $count = 0;

        PmbPendaftar::query()
            ->whereIn('status', [PmbPendaftar::STATUS_DIAJUKAN, PmbPendaftar::STATUS_DIVERIFIKASI])
            ->where(function ($q): void {
                $q->whereNull('last_follow_up_at')->orWhere('last_follow_up_at', '<', now()->subDays(3));
            })
            ->orderBy('id')
            ->limit($limit)
            ->chunk(50, function ($items) use (&$count): void {
                foreach ($items as $pendaftar) {
                    if ($this->followUp($pendaftar)) {
                        $count++;
                    }
                }
            });

        return $count;
    }
}
