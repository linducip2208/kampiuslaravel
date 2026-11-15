<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\ForumBalasan;
use App\Models\ForumThread;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-029: forum diskusi per kelas. Semua login boleh buka thread + balas;
 * kunci/buka thread khusus dosen/admin (diuji).
 */
class Forum extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public ?int $kelasId = null;

    public string $judul = '';

    public string $isi = '';

    public ?int $threadId = null;

    public string $balasan = '';

    public string $message = '';

    protected function authorizeKunci(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi']), 403);
    }

    public function buatThread(): void
    {
        abort_unless(Auth::check(), 401);
        abort_unless($this->kelasId, 422, 'Pilih kelas dulu (?kelas=ID).');

        $this->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string|max:5000',
        ]);

        $thread = ForumThread::create([
            'kelas_id' => $this->kelasId,
            'dibuat_oleh' => Auth::id(),
            'judul' => $this->judul,
            'isi' => $this->isi,
        ]);

        $this->reset(['judul', 'isi']);
        $this->threadId = $thread->id;
        $this->message = 'Thread dibuat.';
    }

    public function balas(): void
    {
        abort_unless(Auth::check(), 401);

        $this->validate([
            'threadId' => 'required|exists:forum_thread,id',
            'balasan' => 'required|string|max:5000',
        ]);

        $thread = ForumThread::findOrFail($this->threadId);

        if ($thread->terkunci) {
            $this->addError('balasan', 'Thread terkunci.');

            return;
        }

        ForumBalasan::create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'isi' => $this->balasan,
        ]);

        $this->reset('balasan');
        $this->message = 'Balasan terkirim.';
    }

    public function kunci(int $id): void
    {
        $this->authorizeKunci();
        ForumThread::findOrFail($id)->update(['terkunci' => true]);
        $this->message = 'Thread dikunci.';
    }

    public function buka(int $id): void
    {
        $this->authorizeKunci();
        ForumThread::findOrFail($id)->update(['terkunci' => false]);
        $this->message = 'Thread dibuka.';
    }

    public function render()
    {
        $threads = ForumThread::with(['penulis', 'kelas'])
            ->when($this->kelasId, fn ($q) => $q->where('kelas_id', $this->kelasId))
            ->orderByDesc('disematkan')->latest('id')
            ->paginate(10);

        $balasan = $this->threadId
            ? ForumBalasan::with('penulis')->where('thread_id', $this->threadId)->latest('id')->paginate(10)
            : null;

        return view('livewire.lms.forum', ['threads' => $threads, 'daftarBalasan' => $balasan]);
    }
}
