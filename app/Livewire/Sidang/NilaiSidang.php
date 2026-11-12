<?php

namespace App\Livewire\Sidang;

use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Sidang as SidangModel;
use App\Models\SidangPenguji;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NilaiSidang extends Component
{
    public ?int $sidang_id = null;

    public ?int $dosen_id = null;

    public string $peran = 'penguji';

    public ?float $nilai = null;

    public string $status = 'lulus';

    public string $message = '';

    public function addPenguji(): void
    {
        $this->validate([
            'sidang_id' => 'required|exists:sidang,id',
            'dosen_id' => 'required|exists:dosen,id',
            'peran' => 'required|string|max:30',
            'nilai' => 'nullable|numeric|min:0|max:100',
        ]);

        SidangPenguji::updateOrCreate(
            ['sidang_id' => $this->sidang_id, 'dosen_id' => $this->dosen_id],
            ['peran' => $this->peran, 'nilai' => $this->nilai]
        );

        $this->reset(['dosen_id', 'nilai']);
        $this->message = 'Nilai penguji tersimpan.';
        $this->recalc();
    }

    public function recalc(): void
    {
        if (! $this->sidang_id) {
            return;
        }
        $avg = SidangPenguji::where('sidang_id', $this->sidang_id)->avg('nilai');
        if ($avg !== null) {
            SidangModel::where('id', $this->sidang_id)->update(['nilai' => round((float) $avg, 2)]);
        }
    }

    public function setStatus(): void
    {
        $this->validate([
            'sidang_id' => 'required|exists:sidang,id',
            'status' => 'required|in:lulus,revisi,gagal,terdaftar',
        ]);

        $sidang = SidangModel::findOrFail($this->sidang_id);
        $sidang->update(['status' => $this->status]);

        if ($this->status === 'lulus') {
            $sidang->judul()->update(['status' => 'lulus']);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'sidang.nilai.'.$this->status,
            'model' => SidangModel::class,
            'model_id' => $sidang->id,
            'created_at' => now(),
        ]);

        $this->message = 'Status sidang diperbarui.';
    }

    public function render()
    {
        return view('livewire.sidang.nilai-sidang', [
            'sidangs' => SidangModel::with(['judul', 'pengujis.dosen'])->latest()->paginate(10),
            'dosens' => Dosen::where('status', 'aktif')->get(),
        ]);
    }
}
