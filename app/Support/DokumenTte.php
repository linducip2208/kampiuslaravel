<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Cap "tanda tangan elektronik" untuk dokumen cetak (KHS, transkrip, KTM, surat).
 *
 * Selama layanan BSrE belum dikonfigurasi (tte.base_url kosong), dokumen
 * memakai TTE internal: hash HMAC + penandatangan dari pengaturan. Label di
 * view selalu jujur menyebut statusnya agar tidak dikira sertifikat BSrE.
 */
class DokumenTte
{
    /**
     * @return array{kode:string, hash:string, waktu:string, penandatangan:string, terkonfigurasi:bool, status:string}
     */
    public static function stamp(string $kodeDokumen): array
    {
        $waktu = now();
        $hash = strtoupper(substr(hash_hmac('sha256', $kodeDokumen, (string) config('app.key')), 0, 12));
        $penandatangan = (string) (Setting::get('tte.username', '') ?: 'Pejabat Akademik');
        $terkonfigurasi = (string) Setting::get('tte.base_url', '') !== '';

        return [
            'kode' => $kodeDokumen,
            'hash' => implode('-', str_split($hash, 4)),
            'waktu' => $waktu->translatedFormat('d F Y H:i').' WIB',
            'penandatangan' => $penandatangan,
            'terkonfigurasi' => $terkonfigurasi,
            'status' => $terkonfigurasi
                ? 'Ditandatangani secara elektronik (TTE tersambung)'
                : 'Ditandatangani secara elektronik internal — layanan BSrE belum dikonfigurasi',
        ];
    }

    /**
     * Tanda tangan URL verifikasi (parameter `s`) agar tidak bisa dipalsukan.
     */
    public static function tanda(string $payload): string
    {
        return substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 16);
    }

    public static function valid(string $payload, string $tanda): bool
    {
        if ($tanda === '') {
            return false;
        }

        return hash_equals(self::tanda($payload), $tanda);
    }
}
