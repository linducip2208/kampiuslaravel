<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\SkripsiJudul;
use Illuminate\Support\Facades\Http;

class PlagiarismeService
{
    public const AMBANG_BATAS = 30.0;

    /**
     * @return array{skor: float, status: string, laporan: string}
     */
    public static function check(string $judul, ?string $abstrak = null): array
    {
        $endpoint = (string) config('services.plagiarisme.endpoint', '');
        $apiKey = (string) config('services.plagiarisme.key', '');

        if ($endpoint !== '') {
            try {
                $response = Http::timeout(10)
                    ->when($apiKey !== '', fn ($req) => $req->withToken($apiKey))
                    ->post($endpoint, ['judul' => $judul, 'abstrak' => $abstrak]);

                if ($response->successful()) {
                    $data = $response->json();
                    $skor = (float) ($data['similarity'] ?? $data['skor'] ?? 0);
                    $status = $skor <= self::AMBANG_BATAS ? 'lolos' : 'ditolak';

                    return [
                        'skor' => round($skor, 2),
                        'status' => (string) ($data['status'] ?? $status),
                        'laporan' => (string) ($data['laporan'] ?? $data['report'] ?? 'Hasil pemeriksaan API eksternal.'),
                    ];
                }
            } catch (\Throwable) {
                // Fallback ke pemeriksaan lokal bila API tidak terjangkau.
            }
        }

        return self::checkLokal($judul, $abstrak);
    }

    /**
     * @return array{skor: float, status: string, laporan: string}
     */
    public static function checkLokal(string $judul, ?string $abstrak = null): array
    {
        $maks = 0.0;
        $pembanding = [];

        foreach (SkripsiJudul::query()->select(['judul'])->latest('id')->take(500)->pluck('judul') as $lain) {
            similar_text(mb_strtolower($judul), mb_strtolower((string) $lain), $persen);
            $maks = max($maks, (float) $persen);
            if ($maks >= 99.9) {
                break;
            }
        }

        foreach (Repository::query()->select(['judul'])->latest('id')->take(500)->pluck('judul') as $lain) {
            similar_text(mb_strtolower($judul), mb_strtolower((string) $lain), $persen);
            $maks = max($maks, (float) $persen);
        }

        if ($abstrak) {
            $pembanding[] = substr($abstrak, 0, 120);
        }

        $skor = round($maks, 2);
        $status = $skor <= self::AMBANG_BATAS ? 'lolos' : 'ditolak';

        return [
            'skor' => $skor,
            'status' => $status,
            'laporan' => "Pemeriksaan lokal terhadap judul skripsi dan repositori. Kemiripan maksimum {$skor}% (ambang ".self::AMBANG_BATAS.'%).',
        ];
    }

    /**
     * @return array{skor: float, status: string, laporan: string}
     */
    public static function checkJudul(SkripsiJudul $pengajuan): array
    {
        $hasil = self::check($pengajuan->judul, $pengajuan->deskripsi);

        $pengajuan->update([
            'similarity' => $hasil['skor'],
            'plagiarisme_status' => $hasil['status'],
            'plagiarisme_laporan' => $hasil['laporan'],
        ]);

        return $hasil;
    }
}
