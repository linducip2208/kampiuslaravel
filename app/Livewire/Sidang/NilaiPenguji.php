<?php

namespace App\Livewire\Sidang;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\SidangPenguji;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * RBAC batch 3: nilai mandiri penguji — owner-scope ketat.
 * Hanya baris SidangPenguji milik dosen sendiri yang tampil & bisa diubah.
 * Status kelulusan tetap dipegang NilaiSidang (dosen/admin).
 */
class NilaiPenguji extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['penguji', 'dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    public array $nilai = [];

    public string $message = '';

    protected function dosenId(): int
    {
        $id = (int) Dosen::where('user_id', Auth::id())->value('id');
        abort_unless($id > 0, 403, 'Akun dosen tidak ditemukan.');

        return $id;
    }

    public function simpan(int $pengujiId): void
    {
        $this->validate(['nilai.'.$pengujiId => 'required|numeric|min:0|max:100']);

        $row = SidangPenguji::whereKey($pengujiId)->where('dosen_id', $this->dosenId())->firstOrFail();

        DB::transaction(function () use ($row): void {
            $row->update(['nilai' => (float) $this->nilai[$row->id]]);
            $avg = SidangPenguji::where('sidang_id', $row->sidang_id)->avg('nilai');

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'sidang.nilai-penguji',
                'model' => SidangPenguji::class,
                'model_id' => $row->id,
                'diff_json' => ['skor' => (float) $row->fresh()->nilai, 'rata' => $avg === null ? null : round((float) $avg, 2)],
                'created_at' => now(),
            ]);
        });

        $this->message = 'Nilai tersimpan.';
    }

    public function render()
    {
        return view('livewire.sidang.nilai-penguji', [
            'tugas' => SidangPenguji::with(['sidang.judul.mahasiswa.user'])
                ->where('dosen_id', $this->dosenId())
                ->latest('id')
                ->paginate(10),
        ]);
    }
}
