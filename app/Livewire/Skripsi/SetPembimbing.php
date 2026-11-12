<?php

namespace App\Livewire\Skripsi;

use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\SkripsiJudul;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SetPembimbing extends Component
{
    public ?int $judulId = null;

    public ?int $pembimbing1_id = null;

    public ?int $pembimbing2_id = null;

    public string $status = 'disetujui';

    public string $message = '';

    public function setPembimbing(int $id): void
    {
        $this->validate([
            'pembimbing1_id' => 'required|exists:dosen,id',
            'pembimbing2_id' => 'nullable|exists:dosen,id|different:pembimbing1_id',
            'status' => 'required|in:disetujui,ditolak,revisi',
        ]);

        $judul = SkripsiJudul::findOrFail($id);
        $judul->update([
            'pembimbing1_id' => $this->pembimbing1_id,
            'pembimbing2_id' => $this->pembimbing2_id,
            'status' => $this->status,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'skripsi.pembimbing.set',
            'model' => SkripsiJudul::class,
            'model_id' => $judul->id,
            'created_at' => now(),
        ]);

        $this->reset(['judulId', 'pembimbing1_id', 'pembimbing2_id']);
        $this->status = 'disetujui';
        $this->message = 'Pembimbing berhasil ditetapkan.';
    }

    public function render()
    {
        return view('livewire.skripsi.set-pembimbing', [
            'pengajuans' => SkripsiJudul::with(['mahasiswa', 'pembimbing1', 'pembimbing2'])->latest()->paginate(10),
            'dosens' => Dosen::where('status', 'aktif')->get(),
        ]);
    }
}
