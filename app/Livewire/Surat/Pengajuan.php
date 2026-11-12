<?php

namespace App\Livewire\Surat;

use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\Surat as SuratModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Pengajuan extends Component
{
    public string $jenis = 'aktif_kuliah';

    public string $keperluan = '';

    public string $message = '';

    public function save(): void
    {
        $this->validate([
            'jenis' => 'required|in:aktif_kuliah,pengantar_kp,keterangan_lulus,legalisir',
            'keperluan' => 'nullable|string',
        ]);

        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();

        $surat = SuratModel::create([
            'mahasiswa_id' => $mahasiswa->id,
            'jenis' => $this->jenis,
            'keperluan' => $this->keperluan ?: null,
            'status' => 'diajukan',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'surat.diajukan',
            'model' => SuratModel::class,
            'model_id' => $surat->id,
            'created_at' => now(),
        ]);

        $this->reset(['keperluan']);
        $this->message = 'Surat berhasil diajukan.';
    }

    public function render()
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');

        return view('livewire.surat.pengajuan', [
            'riwayat' => $mahasiswaId
                ? SuratModel::where('mahasiswa_id', $mahasiswaId)->latest()->get()
                : collect(),
        ]);
    }
}
