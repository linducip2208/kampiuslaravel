<?php

namespace App\Livewire\Skripsi;

use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\SkripsiJudul;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PengajuanJudul extends Component
{
    public string $judul = '';

    public string $deskripsi = '';

    public string $message = '';

    public function save(): void
    {
        $this->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();

        $pengajuan = SkripsiJudul::create([
            'mahasiswa_id' => $mahasiswa->id,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi ?: null,
            'status' => 'diajukan',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'skripsi.judul.diajukan',
            'model' => SkripsiJudul::class,
            'model_id' => $pengajuan->id,
            'created_at' => now(),
        ]);

        $this->reset(['judul', 'deskripsi']);
        $this->message = 'Judul berhasil diajukan.';
    }

    public function render()
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');
        $riwayat = $mahasiswaId
            ? SkripsiJudul::where('mahasiswa_id', $mahasiswaId)->latest()->get()
            : collect();

        return view('livewire.skripsi.pengajuan-judul', ['riwayat' => $riwayat]);
    }
}
