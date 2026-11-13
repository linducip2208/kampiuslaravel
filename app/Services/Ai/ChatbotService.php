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
 * Chatbot akademik: grounding dari data SIAKAD (IPK, tagihan, tugas, kehadiran)
 * + LLM opsional untuk merangkai jawaban. Tanya-jawab umum tetap dijawab LLM.
 */
class ChatbotService
{
    public function ask(?int $userId, string $pesan): string
    {
        $pesan = trim($pesan);
        $konteks = $this->konteksMahasiswa($userId);
        $lower = mb_strtolower($pesan);

        $grounded = $this->jawabDariData($lower, $konteks);

        if ($grounded !== null) {
            $this->log($userId, 'chat', $pesan, $grounded);

            return $grounded;
        }

        $messages = [
            ['role' => 'system', 'content' => 'Kamu asisten akademik kampus SIMAK. Jawab singkat, sopan, Bahasa Indonesia. Data: '.($konteks['ringkas'] ?? 'tamu')],
            ['role' => 'user', 'content' => $pesan],
        ];
        $jawaban = AiProvider::chat($messages);
        $this->log($userId, 'chat', $pesan, $jawaban);

        return $jawaban;
    }

    /** @return array{ringkas: string, ipk: float, tagihan: int, tugas_terlambat: int, hadir_pct: float} */
    private function konteksMahasiswa(?int $userId): array
    {
        $ctx = ['ringkas' => 'pengguna tamu', 'ipk' => 0.0, 'tagihan' => 0, 'tugas_terlambat' => 0, 'hadir_pct' => 0.0];

        if (! $userId) {
            return $ctx;
        }

        try {
            $mhs = Mahasiswa::where('user_id', $userId)->first();
            if (! $mhs) {
                return $ctx;
            }

            $ipk = 0.0;
            if (Schema::hasTable('nilai')) {
                $ipk = AkademikService::ringkasan($mhs->id)['ipk'] ?? 0.0;
            }

            $tagihan = Schema::hasTable('tagihan')
                ? (int) Tagihan::where('mahasiswa_id', $mhs->id)->whereIn('status', ['belum_bayar', 'sebagian'])->sum('nominal')
                : 0;

            $terlambat = 0;
            if (Schema::hasTable('tugas_kumpul')) {
                $terlambat = (int) TugasKumpul::where('mahasiswa_id', $mhs->id)->where('is_late', true)->count();
            }

            $hadirPct = 0.0;
            if (Schema::hasTable('absensi')) {
                $total = Absensi::where('mahasiswa_id', $mhs->id)->count();
                $hadir = Absensi::where('mahasiswa_id', $mhs->id)->where('status', 'hadir')->count();
                $hadirPct = $total > 0 ? round($hadir / $total * 100, 1) : 0.0;
            }

            $ctx = [
                'ringkas' => "mhs {$mhs->nim}, IPK {$ipk}, tunggakan Rp{$tagihan}, tugas terlambat {$terlambat}, hadir {$hadirPct}%",
                'ipk' => $ipk, 'tagihan' => $tagihan, 'tugas_terlambat' => $terlambat, 'hadir_pct' => $hadirPct,
            ];
        } catch (\Throwable) {
            // Konteks opsional — jangan gagalkan chat.
        }

        return $ctx;
    }

    private function jawabDariData(string $lower, array $ctx): ?string
    {
        if (str_contains($lower, 'ipk') || str_contains($lower, 'nilai') || str_contains($lower, 'khs')) {
            return "IPK kamu saat ini {$ctx['ipk']}. Detail nilai bisa dilihat di menu KHS/Transkrip.";
        }
        if (str_contains($lower, 'tagihan') || str_contains($lower, 'bayar') || str_contains($lower, 'ukt') || str_contains($lower, 'tunggakan')) {
            return $ctx['tagihan'] > 0
                ? 'Total tunggakan kamu Rp'.number_format($ctx['tagihan'], 0, ',', '.').'. Bayar via menu Keuangan > Bayar.'
                : 'Tidak ada tunggakan aktif. Bagus, pertahankan!';
        }
        if (str_contains($lower, 'tugas') || str_contains($lower, 'terlambat') || str_contains($lower, 'deadline')) {
            return "Tugas terlambat: {$ctx['tugas_terlambat']}. Kehadiran: {$ctx['hadir_pct']}%. Segera kumpulkan via menu LMS.";
        }
        if (str_contains($lower, 'hadir') || str_contains($lower, 'absen')) {
            return "Persentase kehadiran kamu {$ctx['hadir_pct']}%. Minimal 75% untuk ikut UAS.";
        }
        if (str_contains($lower, 'jadwal') || str_contains($lower, 'kelas hari')) {
            return 'Jadwal hari ini bisa dilihat di menu Jadwal / API /jadwal-hari-ini pada aplikasi mobile.';
        }

        return null;
    }

    private function log(?int $userId, string $jenis, string $input, string $output): void
    {
        try {
            AiJob::create([
                'user_id' => $userId, 'jenis' => $jenis,
                'input' => mb_substr($input, 0, 2000), 'output' => mb_substr($output, 0, 8000),
                'status' => 'selesai', 'provider' => AiProvider::driverName(),
            ]);
        } catch (\Throwable) {
        }
    }
}
