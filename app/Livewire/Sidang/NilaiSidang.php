<?php

namespace App\Livewire\Sidang;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Sidang as SidangModel;
use App\Models\SidangPenguji;
use App\Services\BeritaAcaraService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NilaiSidang extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    public ?int $sidang_id = null;

    public ?int $dosen_id = null;

    public string $peran = 'penguji';

    public ?float $nilai = null;

    public string $status = 'lulus';

    public string $message = '';

    public ?float $rataRata = null;

    public string $hurufMutu = '-';

    public ?string $beritaAcara = null;

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
            $nilai = round((float) $avg, 2);
            SidangModel::where('id', $this->sidang_id)->update(['nilai' => $nilai]);
            $this->rataRata = $nilai;
            $this->hurufMutu = BeritaAcaraService::hurufMutu($nilai);
        }
    }

    public function setStatus(): void
    {
        $this->validate([
            'sidang_id' => 'required|exists:sidang,id',
            'status' => 'required|in:lulus,revisi,gagal,terdaftar',
        ]);

        $berita = null;
        DB::transaction(function () use (&$berita): void {
            $sidang = SidangModel::whereKey($this->sidang_id)->lockForUpdate()->firstOrFail();
            $sidang->update(['status' => $this->status]);
            $avg = SidangPenguji::where('sidang_id', $sidang->id)->avg('nilai');
            if ($avg !== null) {
                $nilai = round((float) $avg, 2);
                $sidang->update(['nilai' => $nilai]);
                $this->rataRata = $nilai;
                $this->hurufMutu = BeritaAcaraService::hurufMutu($nilai);
            }
            $sidang->refresh();

            if ($this->status === 'lulus') {
                $sidang->judul()->update(['status' => 'lulus']);
                $berita = BeritaAcaraService::generate($sidang->fresh());
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'sidang.nilai.'.$this->status,
                'model' => SidangModel::class,
                'model_id' => $sidang->id,
                'created_at' => now(),
            ]);
        });
        $this->beritaAcara = $berita;

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
