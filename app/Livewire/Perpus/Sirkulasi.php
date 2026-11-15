<?php

namespace App\Livewire\Perpus;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\PerpusBuku;
use App\Models\PerpusEksemplar;
use App\Models\PerpusPinjam;
use App\Services\PerpustakaanService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

/**
 * Sirkulasi: pinjam by barcode + kembali/hilang + denda otomatis.
 */
class Sirkulasi extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'pustakawan'];

    public string $barcode = '';

    public int $userId = 0;

    public string $cari = '';

    public string $message = '';

    public string $error = '';

    public function pinjam(): void
    {
        $this->validate([
            'barcode' => 'required|string|max:60|exists:perpus_eksemplar,barcode',
            'userId' => 'required|integer|min:1|exists:users,id',
        ]);

        try {
            $eks = PerpusEksemplar::where('barcode', trim($this->barcode))->firstOrFail();
            PerpustakaanService::pinjam($eks->id, $this->userId);

            AuditLog::create([
                'user_id' => Auth::id(), 'action' => 'perpus.pinjam',
                'model' => PerpusPinjam::class, 'model_id' => 0, 'created_at' => now(),
            ]);

            $this->reset(['barcode', 'userId']);
            $this->error = '';
            $this->message = 'Peminjaman tercatat (tempo 14 hari).';
        } catch (RuntimeException $e) {
            $this->message = '';
            $this->error = $e->getMessage();
        }
    }

    public function kembali(int $id, string $kondisi = 'baik'): void
    {
        try {
            $pinjam = PerpustakaanService::kembali($id, $kondisi);

            AuditLog::create([
                'user_id' => Auth::id(), 'action' => 'perpus.kembali',
                'model' => PerpusPinjam::class, 'model_id' => $id, 'created_at' => now(),
            ]);

            $this->message = $pinjam->denda_rp > 0
                ? 'Kembali dengan denda Rp'.number_format($pinjam->denda_rp, 0, ',', '.').'.'
                : 'Kembali tepat waktu. Terima kasih.';
            $this->error = '';
        } catch (RuntimeException $e) {
            $this->message = '';
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.perpus.sirkulasi', [
            'aktif' => PerpusPinjam::with(['eksemplar.buku', 'peminjam'])
                ->where('status', PerpusPinjam::STATUS_PINJAM)
                ->when($this->cari !== '', fn ($q) => $q->whereHas('eksemplar', fn ($e) => $e->where('barcode', 'like', '%'.$this->cari.'%')))
                ->latest('id')->paginate(10),
            'bukuBaru' => PerpusBuku::latest('id')->take(8)->get(),
        ]);
    }
}
