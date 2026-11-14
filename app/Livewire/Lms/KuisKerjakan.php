<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Mahasiswa;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizJawaban;
use App\Services\QuizService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * M-028: kerjakan kuis (mahasiswa) — mulai/resume, autosave, submit.
 * Owner selalu di-resolve server-side (anti-spoofing).
 */
class KuisKerjakan extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    public ?int $quizId = null;

    public array $jawaban = [];

    public string $message = '';

    public string $error = '';

    protected function mahasiswaId(): int
    {
        // Resolve strictly dari akun sendiri (staf tanpa baris mahasiswa → 403).
        $id = (int) Mahasiswa::where('user_id', Auth::id())->value('id');
        abort_unless($id > 0, 403, 'Akun mahasiswa tidak ditemukan.');

        return $id;
    }

    public function mulai(): void
    {
        abort_unless($this->quizId, 422);

        try {
            QuizService::mulai($this->quizId, $this->mahasiswaId());
            $this->error = '';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function updatedJawaban($value, $key): void
    {
        $this->autosave((int) $key, is_string($value) ? $value : (string) $value);
    }

    public function autosave(int $soalId, ?string $isi): void
    {
        abort_unless($this->quizId, 422);

        try {
            $attempt = QuizService::mulai($this->quizId, $this->mahasiswaId());
            QuizService::simpanJawaban($attempt->id, $this->mahasiswaId(), $soalId, $isi);
            $this->error = '';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function submit(): void
    {
        abort_unless($this->quizId, 422);
        $mhs = $this->mahasiswaId();
        $attempt = QuizAttempt::where('quiz_id', $this->quizId)->where('mahasiswa_id', $mhs)->firstOrFail();

        try {
            QuizService::selesai($attempt->id, $mhs);
            $this->error = '';
            $this->message = 'Jawaban dikumpulkan. Nilai PG terisi otomatis.';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        $quiz = $this->quizId ? Quiz::with('soal')->find($this->quizId) : null;
        $attempt = null;
        $tersimpan = collect();

        if ($quiz) {
            $mhs = Auth::check() ? (int) Mahasiswa::where('user_id', Auth::id())->value('id') : 0;

            if ($mhs > 0) {
                $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('mahasiswa_id', $mhs)->first();
                $tersimpan = QuizJawaban::where('mahasiswa_id', $mhs)
                    ->whereIn('quiz_soal_id', $quiz->soal->pluck('id'))
                    ->pluck('jawaban', 'quiz_soal_id');
            }
        }

        return view('livewire.lms.kuis-kerjakan', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'tersimpan' => $tersimpan,
            'sisaDetik' => $attempt && $attempt->status === QuizAttempt::STATUS_BERJALAN
                ? QuizService::sisaDetik($attempt->fresh())
                : null,
        ]);
    }
}
