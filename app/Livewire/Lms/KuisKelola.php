<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Kelas;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizJawaban;
use App\Models\QuizSoal;
use App\Services\QuizService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-028: kelola kuis (dosen) — buat kuis, impor bank soal, nilai & koreksi.
 */
class KuisKelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['dosen', 'superadmin', 'admin_prodi'];

    public ?int $kelasId = null;

    public string $judul = '';

    public int $durasi_menit = 60;

    public string $deadline = '';

    public string $mode = 'latihan';

    public string $token = '';

    public bool $acak_soal = false;

    public ?int $imporQuizId = null;

    public int $imporLimit = 20;

    public string $message = '';

    public ?int $lihatQuizId = null;

    public array $koreksi = [];

    public function simpan(): void
    {
        abort_unless($this->kelasId, 422, 'Pilih kelas dulu (?kelas=ID).');

        $this->validate([
            'judul' => 'required|string|max:255',
            'durasi_menit' => 'required|integer|min:0|max:600',
            'deadline' => 'nullable|date|after:now',
            'mode' => 'required|in:latihan,cbt',
            'token' => 'nullable|string|max:12',
            'acak_soal' => 'boolean',
        ]);

        Quiz::create([
            'kelas_id' => $this->kelasId,
            'judul' => $this->judul,
            'mode' => $this->mode,
            'token' => $this->mode === 'cbt' && $this->token !== '' ? $this->token : null,
            'acak_soal' => $this->mode === 'cbt' && $this->acak_soal,
            'durasi_menit' => $this->durasi_menit,
            'deadline' => $this->deadline ?: null,
        ]);

        $this->reset(['judul', 'durasi_menit', 'deadline', 'mode', 'token', 'acak_soal']);
        $this->durasi_menit = 60;
        $this->mode = 'latihan';
        $this->message = 'Kuis dibuat. Impor soal dari bank soal untuk mengisi.';
    }

    public function impor(): void
    {
        $this->validate([
            'imporQuizId' => 'required|exists:quiz,id',
            'imporLimit' => 'required|integer|min:1|max:200',
        ]);

        $quiz = Quiz::findOrFail($this->imporQuizId);
        $jumlah = QuizService::imporDariBank($quiz->id, (int) $quiz->kelas->matakuliah_id, $this->imporLimit);

        $this->message = "Diimpor {$jumlah} soal dari bank soal.";
    }

    public function koreksi(int $soalId, int $mahasiswaId): void
    {
        $this->validate(['koreksi.'.$soalId.'.'.$mahasiswaId => 'nullable|numeric|min:0']);

        QuizService::koreksi($soalId, $mahasiswaId, (float) ($this->koreksi[$soalId][$mahasiswaId] ?? 0));
        $this->message = 'Koreksi tersimpan; total attempt dihitung ulang.';
    }

    public function hapus(int $id): void
    {
        Quiz::findOrFail($id)->delete();
        $this->message = 'Kuis dihapus.';
    }

    public function render()
    {
        $daftar = Quiz::with(['soal'])->withCount('soal')
            ->when($this->kelasId, fn ($q) => $q->where('kelas_id', $this->kelasId))
            ->latest('id')
            ->paginate(10);

        $hasil = $this->lihatQuizId
            ? QuizAttempt::with('mahasiswa.user')->where('quiz_id', $this->lihatQuizId)->latest('id')->paginate(10)
            : null;

        $esai = [];
        if ($this->lihatQuizId) {
            $esai = QuizJawaban::with(['soal', 'mahasiswa.user'])
                ->whereIn('quiz_soal_id', QuizSoal::where('quiz_id', $this->lihatQuizId)->where('tipe', '!=', 'pg')->pluck('id'))
                ->latest('id')->take(50)->get();
        }

        return view('livewire.lms.kuis-kelola', [
            'daftar' => $daftar,
            'hasil' => $hasil,
            'esai' => $esai,
            'kelas' => $this->kelasId ? Kelas::with('matakuliah')->find($this->kelasId) : null,
        ]);
    }
}
