<?php

namespace App\Livewire\Notifikasi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Pengumuman;
use App\Models\Prodi;
use App\Services\NotifikasiService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kelola pengumuman: tulis, terbitkan/tarik, hapus.
 * Penerbitan bisa sekaligus push ke topik FCM prodi (guard di service).
 */
class PengumumanKelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public ?int $editId = null;

    public string $judul = '';

    public string $isi = '';

    public string $target = 'semua';

    public ?int $prodi_id = null;

    public bool $pushTopik = false;

    public string $message = '';

    public string $pushInfo = '';

    protected function authorizePengumuman(): void
    {
        abort_unless(Auth::user()?->can('pengumuman.manage'), 403);
    }

    public function simpan(): void
    {
        $this->authorizePengumuman();

        $this->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'target' => 'required|in:semua,mahasiswa,dosen',
            'prodi_id' => 'nullable|exists:prodi,id',
        ]);

        $pengumuman = Pengumuman::updateOrCreate(['id' => $this->editId ?? 0], [
            'judul' => $this->judul,
            'isi' => $this->isi,
            'target' => $this->target,
            'prodi_id' => $this->prodi_id,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'pengumuman.simpan',
            'model' => Pengumuman::class, 'model_id' => $pengumuman->id,
            'created_at' => now(),
        ]);

        $this->reset(['editId', 'judul', 'isi', 'target', 'prodi_id']);
        $this->message = 'Pengumuman tersimpan sebagai draf.';
    }

    public function edit(int $id): void
    {
        $this->authorizePengumuman();

        $p = Pengumuman::findOrFail($id);
        $this->editId = $p->id;
        $this->judul = (string) $p->judul;
        $this->isi = (string) $p->isi;
        $this->target = (string) ($p->target ?? 'semua');
        $this->prodi_id = $p->prodi_id;
    }

    public function terbitkan(int $id, NotifikasiService $notif): void
    {
        $this->authorizePengumuman();

        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->update(['published_at' => now()]);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'pengumuman.terbit',
            'model' => Pengumuman::class, 'model_id' => $pengumuman->id,
            'created_at' => now(),
        ]);

        $this->pushInfo = '';

        if ($this->pushTopik && $pengumuman->prodi_id) {
            $ok = $notif->broadcastTopik((int) $pengumuman->prodi_id, (string) $pengumuman->judul, (string) $pengumuman->isi);
            $this->pushInfo = $ok ? 'Push topik prodi terkirim.' : 'Push topik dilewati (FCM belum dikonfigurasi).';
        }

        $this->message = "Pengumuman #{$id} diterbitkan. {$this->pushInfo}";
    }

    public function tarik(int $id): void
    {
        $this->authorizePengumuman();

        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->update(['published_at' => null]);
        $this->message = "Pengumuman #{$id} ditarik (draf kembali).";
    }

    public function hapus(int $id): void
    {
        $this->authorizePengumuman();

        Pengumuman::whereKey($id)->delete();
        $this->message = "Pengumuman #{$id} dihapus.";
    }

    public function render(): View
    {
        $this->authorizePengumuman();

        return view('livewire.notifikasi.pengumuman-kelola', [
            'prodis' => Prodi::orderBy('nama')->get(['id', 'nama']),
            'pengumumans' => Pengumuman::with('prodi')->latest('id')->paginate(10),
        ]);
    }
}
