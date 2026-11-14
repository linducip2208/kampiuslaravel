<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizJawaban;
use App\Models\QuizSoal;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * M-028: mesin kuis di atas BankSoal.
 * Attempt (timer + recovery) + autosave jawaban + nilai otomatis PG.
 * Esai dinilai manual via koreksi().
 */
class QuizService
{
    /** Mulai/resume attempt (idempoten per quiz+mhs). CBT: verifikasi token + kunci urutan acak. */
    public static function mulai(int $quizId, int $mahasiswaId, ?string $token = null): QuizAttempt
    {
        $quiz = Quiz::findOrFail($quizId);

        if ($quiz->deadline && now()->greaterThan($quiz->deadline)) {
            throw new DomainException('Deadline kuis sudah lewat.');
        }

        if ($quiz->token !== null && $quiz->token !== '' && $token !== $quiz->token) {
            throw new DomainException('Token CBT salah.');
        }

        $attempt = QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'mahasiswa_id' => $mahasiswaId],
            ['mulai_at' => now(), 'status' => QuizAttempt::STATUS_BERJALAN]
        );

        // M-051: kunci urutan soal acak saat attempt dibuat (recovery pakai urutan sama).
        if ($attempt->urutan_soal === null && $quiz->acak_soal) {
            $ids = QuizSoal::where('quiz_id', $quiz->id)->orderBy('id')->pluck('id')->all();
            shuffle($ids);
            $attempt->update(['urutan_soal' => $ids]);
        }

        return $attempt->fresh();
    }

    /** Sisa waktu detik (0 bila habis). */
    public static function sisaDetik(QuizAttempt $attempt): int
    {
        $durasi = (int) ($attempt->quiz->durasi_menit ?? 0) * 60;

        if ($durasi <= 0) {
            return PHP_INT_MAX;
        }

        return max(0, $attempt->mulai_at->addSeconds($durasi)->getTimestamp() - now()->getTimestamp());
    }

    /** Autosave 1 jawaban (hanya attempt berjalan + dalam waktu + sebelum deadline). */
    public static function simpanJawaban(int $attemptId, int $mahasiswaId, int $soalId, ?string $jawaban): QuizJawaban
    {
        return DB::transaction(function () use ($attemptId, $mahasiswaId, $soalId, $jawaban): QuizJawaban {
            $attempt = QuizAttempt::whereKey($attemptId)->lockForUpdate()->firstOrFail();
            abort_if($attempt->mahasiswa_id !== $mahasiswaId, 403);
            abort_if($attempt->status !== QuizAttempt::STATUS_BERJALAN, 422, 'Attempt sudah selesai.');

            $soal = QuizSoal::whereKey($soalId)->where('quiz_id', $attempt->quiz_id)->firstOrFail();

            if ($attempt->quiz->deadline && now()->greaterThan($attempt->quiz->deadline)) {
                throw new DomainException('Deadline kuis sudah lewat.');
            }

            if (self::sisaDetik($attempt->fresh()) <= 0 && (int) ($attempt->quiz->durasi_menit ?? 0) > 0) {
                throw new DomainException('Waktu pengerjaan habis.');
            }

            return QuizJawaban::updateOrCreate(
                ['quiz_soal_id' => $soal->id, 'mahasiswa_id' => $mahasiswaId],
                ['jawaban' => $jawaban !== null ? mb_substr(trim($jawaban), 0, 5000) : null]
            );
        });
    }

    /** Selesai + nilai otomatis PG (esai → skor null, koreksi manual). */
    public static function selesai(int $attemptId, int $mahasiswaId): QuizAttempt
    {
        return DB::transaction(function () use ($attemptId, $mahasiswaId): QuizAttempt {
            $attempt = QuizAttempt::whereKey($attemptId)->lockForUpdate()->firstOrFail();
            abort_if($attempt->mahasiswa_id !== $mahasiswaId, 403);

            if ($attempt->status === QuizAttempt::STATUS_SELESAI) {
                return $attempt;
            }

            $soal = QuizSoal::where('quiz_id', $attempt->quiz_id)->get();
            $jawaban = QuizJawaban::where('mahasiswa_id', $mahasiswaId)
                ->whereIn('quiz_soal_id', $soal->pluck('id'))
                ->get()->keyBy('quiz_soal_id');

            $total = 0.0;

            foreach ($soal as $s) {
                $j = $jawaban->get($s->id);

                if ($s->tipe !== 'pg') {
                    if ($j && $j->skor === null) {
                        continue;
                    }
                    $total += (float) ($j->skor ?? 0);

                    continue;
                }

                $benar = $s->kunci_jawaban !== null
                    && $j !== null && $j->jawaban !== null
                    && mb_strtoupper(trim((string) $j->jawaban)) === mb_strtoupper(trim((string) $s->kunci_jawaban));
                $skor = $benar ? (float) $s->bobot : 0.0;

                if ($j) {
                    $j->update(['skor' => $skor]);
                } else {
                    QuizJawaban::create([
                        'quiz_soal_id' => $s->id, 'mahasiswa_id' => $mahasiswaId,
                        'jawaban' => null, 'skor' => $skor,
                    ]);
                }

                $total += $skor;
            }

            $attempt->update([
                'status' => QuizAttempt::STATUS_SELESAI,
                'selesai_at' => now(),
                'total_skor' => round($total, 2),
            ]);

            return $attempt->fresh();
        });
    }

    /** Koreksi manual (esai / penyesuaian) oleh dosen. */
    public static function koreksi(int $soalId, int $mahasiswaId, ?float $skor): QuizJawaban
    {
        $j = QuizJawaban::where('quiz_soal_id', $soalId)->where('mahasiswa_id', $mahasiswaId)->firstOrFail();
        $j->update(['skor' => $skor]);

        $attempt = QuizAttempt::where('quiz_id', $j->soal->quiz_id)
            ->where('mahasiswa_id', $mahasiswaId)->first();

        if ($attempt && $attempt->status === QuizAttempt::STATUS_SELESAI) {
            $attempt->update(['total_skor' => round((float) QuizJawaban::where('mahasiswa_id', $mahasiswaId)
                ->whereIn('quiz_soal_id', QuizSoal::where('quiz_id', $attempt->quiz_id)->pluck('id'))
                ->sum('skor'), 2)]);
        }

        return $j->fresh();
    }

    /** Impor soal aktif dari bank soal MK ke kuis (kembalikan jumlah). */
    public static function imporDariBank(int $quizId, int $matakuliahId, int $limit = 50): int
    {
        $quiz = Quiz::findOrFail($quizId);
        $rows = BankSoal::where('matakuliah_id', $matakuliahId)
            ->where('aktif', true)
            ->orderBy('id')
            ->limit(max(1, min($limit, 200)))
            ->get();

        $count = 0;

        foreach ($rows as $b) {
            QuizSoal::create([
                'quiz_id' => $quiz->id,
                'pertanyaan' => $b->pertanyaan,
                'opsi' => $b->opsi,
                'tipe' => $b->jenis,
                'bobot' => $b->bobot,
                'kunci_jawaban' => $b->kunci,
            ]);
            $count++;
        }

        return $count;
    }

    /** M-051: segel otomatis attempt kedaluwarsa (durasi habis / deadline lewat). */
    public static function sealKedaluwarsa(int $limit = 200): int
    {
        $disegel = 0;

        $kandidat = QuizAttempt::with('quiz')
            ->where('status', QuizAttempt::STATUS_BERJALAN)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($kandidat as $attempt) {
            $quiz = $attempt->quiz;

            if (! $quiz) {
                continue;
            }

            $habisDurasi = (int) ($quiz->durasi_menit ?? 0) > 0 && self::sisaDetik($attempt) <= 0;
            $lewatDeadline = $quiz->deadline && now()->greaterThan($quiz->deadline);

            if ($habisDurasi || $lewatDeadline) {
                try {
                    self::selesai($attempt->id, $attempt->mahasiswa_id);
                    $disegel++;
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return $disegel;
    }

    public static function normalisasi(?string $jawaban): string
    {
        return mb_strtoupper(trim((string) $jawaban));
    }
}
