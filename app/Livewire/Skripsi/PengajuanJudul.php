<?php

namespace App\Livewire\Skripsi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\SkripsiJudul;
use App\Models\SkripsiTopik;
use App\Services\PlagiarismeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PengajuanJudul extends Component
{
    use RequiresRoles;

    /** Otorisasi owner-scope dicek manual di mount(). */
    public array $allowedRoles = [];

    public string $judul = '';

    public string $deskripsi = '';

    public ?int $topik_id = null;

    public string $message = '';

    public string $plagiarisme = '';

    public function save(): void
    {
        $this->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'topik_id' => 'nullable|exists:skripsi_topik,id',
        ]);

        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();

        $pengajuan = SkripsiJudul::create([
            'mahasiswa_id' => $mahasiswa->id,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi ?: null,
            'status' => 'diajukan',
            'topik_id' => $this->topik_id,
        ]);

        $hasil = PlagiarismeService::checkJudul($pengajuan);
        $this->plagiarisme = "Kemiripan {$hasil['skor']}% ({$hasil['status']}).";
        if ($hasil['status'] !== 'lolos') {
            $this->plagiarisme .= ' Judul berpotensi plagiat, silakan ubah sebelum disetujui pembimbing.';
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'skripsi.judul.diajukan',
            'model' => SkripsiJudul::class,
            'model_id' => $pengajuan->id,
            'created_at' => now(),
        ]);

        $this->reset(['judul', 'deskripsi', 'topik_id']);
        $this->message = 'Judul berhasil diajukan. '.$this->plagiarisme;
    }

    public function render()
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');
        $riwayat = $mahasiswaId
            ? SkripsiJudul::where('mahasiswa_id', $mahasiswaId)->latest()->get()
            : collect();

        return view('livewire.skripsi.pengajuan-judul', [
            'riwayat' => $riwayat,
            'bankTopik' => SkripsiTopik::where('status', 'buka')->whereColumn('diambil', '<', 'kuota')->latest()->take(20)->get(),
        ]);
    }
}
