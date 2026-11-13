<?php

namespace App\Livewire\Kalender;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\KalenderEvent;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Kelola extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    public ?int $editId = null;

    public string $judul = '';

    public string $jenis = 'lain';

    public string $tgl_mulai = '';

    public ?string $tgl_selesai = null;

    public ?int $tahun_akademik_id = null;

    public string $deskripsi = '';

    public string $message = '';

    private function bisaKelola(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi', 'pimpinan']);
    }

    public const JENIS = ['kuliah', 'uts', 'uas', 'libur', 'pembayaran', 'pmb', 'wisuda', 'krs', 'sidang', 'lain'];

    public function simpan(): void
    {
        abort_unless($this->bisaKelola(), 403);

        $this->validate([
            'judul' => 'required|string|max:150',
            'jenis' => 'required|in:kuliah,uts,uas,libur,pembayaran,pmb,wisuda,krs,sidang,lain',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'nullable|date|after_or_equal:tgl_mulai',
            'tahun_akademik_id' => 'nullable|exists:tahun_akademik,id',
        ]);

        $event = KalenderEvent::updateOrCreate(['id' => $this->editId ?? 0], [
            'judul' => $this->judul,
            'jenis' => $this->jenis,
            'tgl_mulai' => $this->tgl_mulai,
            'tgl_selesai' => $this->tgl_selesai ?: null,
            'tahun_akademik_id' => $this->tahun_akademik_id,
            'deskripsi' => $this->deskripsi ?: null,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $this->editId ? 'kalender.diubah' : 'kalender.dibuat',
            'model' => KalenderEvent::class,
            'model_id' => $event->id,
            'created_at' => now(),
        ]);
        $this->reset(['editId', 'judul', 'jenis', 'tgl_mulai', 'tgl_selesai', 'tahun_akademik_id', 'deskripsi']);
        $this->message = 'Agenda tersimpan.';
    }

    public function edit(int $id): void
    {
        abort_unless($this->bisaKelola(), 403);

        $e = KalenderEvent::findOrFail($id);
        $this->editId = $e->id;
        $this->judul = $e->judul;
        $this->jenis = $e->jenis;
        $this->tgl_mulai = $e->tgl_mulai->format('Y-m-d');
        $this->tgl_selesai = $e->tgl_selesai?->format('Y-m-d');
        $this->tahun_akademik_id = $e->tahun_akademik_id;
        $this->deskripsi = (string) $e->deskripsi;
    }

    public function hapus(int $id): void
    {
        abort_unless($this->bisaKelola(), 403);

        $event = KalenderEvent::findOrFail($id);
        $event->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'kalender.dihapus',
            'model' => KalenderEvent::class,
            'model_id' => $id,
            'created_at' => now(),
        ]);

        $this->message = 'Agenda dihapus.';
    }

    public function render()
    {
        return view('livewire.kalender.kelola', [
            'events' => KalenderEvent::orderBy('tgl_mulai')->paginate(15),
            'tahuns' => TahunAkademik::orderByDesc('id')->get(['id', 'tahun', 'semester']),
            'bisaKelola' => $this->bisaKelola(),
        ]);
    }
}
