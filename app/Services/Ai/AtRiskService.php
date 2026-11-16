<?php

namespace App\Services\Ai;

use App\Models\Absensi;
use App\Models\AiJob;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use App\Models\TugasKumpul;
use App\Services\AkademikService;
use Illuminate\Support\Facades\Schema;

/**
 * Prediktor at-risk: skor 0-100 dari IPK, kehadiran, tunggakan, keterlambatan.
 * Deterministik + dapat dijelaskan (tanpa model ML eksternal).
 */
class AtRiskService
{
    /** @return array{skor: float, level: string, faktor: list<string>, rekomendasi: list<string>, ipk: float} */
    public function prediksi(int $mahasiswaId): array
    {
        $ipk = Schema::hasTable('nilai') ? (float) (AkademikService::ringkasan($mahasiswaId)['ipk'] ?? 0.0) : 0.0;

        $hadirPct = 100.0;
        if (Schema::hasTable('absensi')) {
            $total = Absensi::where('mahasiswa_id', $mahasiswaId)->count();
            $hadir = Absensi::where('mahasiswa_id', $mahasiswaId)->where('status', 'hadir')->count();
            $hadirPct = $total > 0 ? round($hadir / $total * 100, 1) : 100.0;
        }

        $tunggakan = Schema::hasTable('tagihan')
            ? (int) Tagihan::where('mahasiswa_id', $mahasiswaId)->whereIn('status', ['belum_bayar', 'sebagian'])->count()
            : 0;

        $terlambat = Schema::hasTable('tugas_kumpul')
            ? (int) TugasKumpul::where('mahasiswa_id', $mahasiswaId)->where('is_late', true)->count()
            : 0;

        $skor = 0.0;
        $faktor = [];

        if ($ipk < 2.0) {
            $skor += 40;
            $faktor[] = "IPK rendah ({$ipk})";
        } elseif ($ipk < 2.75) {
            $skor += 20;
            $faktor[] = "IPK di bawah 2.75 ({$ipk})";
        }

        if ($hadirPct < 75) {
            $skor += 30;
            $faktor[] = "Kehadiran {$hadirPct}% (< 75%)";
        } elseif ($hadirPct < 85) {
            $skor += 10;
            $faktor[] = "Kehadiran {$hadirPct}%";
        }

        if ($tunggakan > 0) {
            $skor += min(15, $tunggakan * 5);
            $faktor[] = "{$tunggakan} tagihan menunggak";
        }

        if ($terlambat > 0) {
            $skor += min(15, $terlambat * 3);
            $faktor[] = "{$terlambat} tugas terlambat";
        }

        $skor = min(100, round($skor, 1));
        $level = $skor >= 60 ? 'tinggi' : ($skor >= 35 ? 'sedang' : 'rendah');

        $rekomendasi = match ($level) {
            'tinggi' => ['Konseling akademik segera', 'Remedial MK nilai D/E', 'Rencana cicilan tunggakan ke keuangan'],
            'sedang' => ['Pantau kehadiran 2 minggu ke depan', 'Ikut tutoring/kelompok belajar'],
            default => ['Pertahankan pola belajar saat ini'],
        };

        $hasil = ['skor' => $skor, 'level' => $level, 'faktor' => $faktor, 'rekomendasi' => $rekomendasi, 'ipk' => $ipk];

        try {
            AiJob::create([
                'user_id' => Mahasiswa::where('id', $mahasiswaId)->value('user_id'),
                'jenis' => 'at_risk', 'input' => "mahasiswa_id:{$mahasiswaId}",
                'output' => json_encode($hasil), 'status' => 'selesai', 'provider' => 'aturan',
            ]);
        } catch (\Throwable) {
        }

        return $hasil;
    }

    /** @return list<array{mahasiswa_id: int, nim: string, nama: string, skor: float, level: string}> */
    public function daftarBerisiko(int $limit = 20): array
    {
        $out = [];

        try {
            // Catatan: tabel mahasiswa TIDAK punya kolom nama (nama ada di users).
            foreach (Mahasiswa::with('user')->where('status', 'aktif')->take(200)->get(['id', 'nim', 'user_id']) as $mhs) {
                $p = $this->prediksi($mhs->id);
                if ($p['level'] !== 'rendah') {
                    $out[] = ['mahasiswa_id' => $mhs->id, 'nim' => (string) $mhs->nim, 'nama' => (string) ($mhs->user->name ?? $mhs->nim), 'skor' => $p['skor'], 'level' => $p['level']];
                }
            }
        } catch (\Throwable) {
        }

        usort($out, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        return array_slice($out, 0, $limit);
    }
}
