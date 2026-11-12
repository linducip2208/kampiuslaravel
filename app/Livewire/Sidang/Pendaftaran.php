<?php

namespace App\Livewire\Sidang;

use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\Ruang;
use App\Models\Sidang as SidangModel;
use App\Models\SkripsiJudul;
use App\Services\KeuanganService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Pendaftaran extends Component
{
    public ?int $judul_id = null;

    public string $jenis = 'proposal';

    public string $tanggal = '';

    public ?int $ruang_id = null;

    public string $message = '';

    public string $error = '';

    public function save(): void
    {
        $this->validate([
            'judul_id' => 'required|exists:skripsi_judul,id',
            'jenis' => 'required|in:proposal,hasil,tutup',
            'tanggal' => 'required|date',
            'ruang_id' => 'nullable|exists:ruang,id',
        ]);

        $judul = SkripsiJudul::findOrFail($this->judul_id);

        if (KeuanganService::isBlockedForSidang($judul->mahasiswa_id)) {
            $this->error = 'Pendaftaran ditolak: mahasiswa memiliki tunggakan.';
            $this->message = '';

            return;
        }

        $sidang = SidangModel::create([
            'judul_id' => $this->judul_id,
            'jenis' => $this->jenis,
            'tanggal' => $this->tanggal,
            'ruang_id' => $this->ruang_id,
            'status' => 'terdaftar',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'sidang.didaftarkan',
            'model' => SidangModel::class,
            'model_id' => $sidang->id,
            'created_at' => now(),
        ]);

        $this->reset(['judul_id', 'tanggal', 'ruang_id']);
        $this->error = '';
        $this->message = 'Sidang berhasil didaftarkan.';
    }

    public function render()
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');
        $juduls = SkripsiJudul::when($mahasiswaId, fn ($q) => $q->where('mahasiswa_id', $mahasiswaId))
            ->whereIn('status', ['disetujui', 'sidang'])
            ->latest()->get();

        return view('livewire.sidang.pendaftaran', [
            'juduls' => $juduls,
            'ruangs' => Ruang::all(),
            'sidangs' => SidangModel::with(['judul', 'ruang'])->latest()->paginate(10),
        ]);
    }
}
