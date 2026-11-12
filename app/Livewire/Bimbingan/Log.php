<?php

namespace App\Livewire\Bimbingan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Bimbingan as BimbinganModel;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\SkripsiJudul;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Log extends Component
{
    use RequiresRoles;

    /** Otorisasi owner-scope dicek manual di mount(). */
    public array $allowedRoles = [];

    public ?int $judul_id = null;

    public ?int $dosen_id = null;

    public string $catatan = '';

    public string $tanggal = '';

    public string $message = '';

    public function save(): void
    {
        $this->validate([
            'judul_id' => 'required|exists:skripsi_judul,id',
            'dosen_id' => 'nullable|exists:dosen,id',
            'catatan' => 'required|string',
            'tanggal' => 'nullable|date',
        ]);

        // Hanya pemilik judul, pembimbingnya, atau staf yang boleh menulis log.
        $judul = SkripsiJudul::findOrFail($this->judul_id);
        $user = Auth::user();
        $ownMhs = Mahasiswa::where('user_id', $user->id)->value('id');
        $ownDosen = Dosen::where('user_id', $user->id)->value('id');
        $isStaff = $user->hasAnyRole(['superadmin', 'admin_prodi', 'keuangan', 'pimpinan']);

        abort_unless(
            $isStaff || $judul->mahasiswa_id === $ownMhs
                || in_array($ownDosen, [$judul->pembimbing1_id, $judul->pembimbing2_id], false),
            403
        );

        $log = BimbinganModel::create([
            'judul_id' => $this->judul_id,
            'dosen_id' => $this->dosen_id,
            'catatan' => $this->catatan,
            'tanggal' => $this->tanggal ?: now()->toDateString(),
            'status' => 'pending',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'bimbingan.created',
            'model' => BimbinganModel::class,
            'model_id' => $log->id,
            'created_at' => now(),
        ]);

        $this->reset(['catatan', 'tanggal']);
        $this->message = 'Log bimbingan tersimpan.';
    }

    public function setujui(int $id): void
    {
        $log = BimbinganModel::findOrFail($id);
        $log->update(['status' => 'disetujui']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'bimbingan.disetujui',
            'model' => BimbinganModel::class,
            'model_id' => $log->id,
            'created_at' => now(),
        ]);
    }

    public function revisi(int $id): void
    {
        $log = BimbinganModel::findOrFail($id);
        $log->update(['status' => 'revisi']);
    }

    public function render()
    {
        $logs = BimbinganModel::with(['judul', 'dosen'])
            ->when($this->judul_id, fn ($q) => $q->where('judul_id', $this->judul_id))
            ->latest()->paginate(10);

        return view('livewire.bimbingan.log', [
            'logs' => $logs,
            'juduls' => SkripsiJudul::where('status', 'disetujui')->latest()->take(100)->get(),
        ]);
    }
}
