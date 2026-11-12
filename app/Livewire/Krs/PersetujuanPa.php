<?php

namespace App\Livewire\Krs;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Krs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Persetujuan KRS oleh Dosen PA (approve / tolak + catatan).
 */
class PersetujuanPa extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'superadmin', 'admin_prodi'];

    use WithPagination;

    public string $search = '';

    public string $catatan = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setujui(int $id): void
    {
        $krs = $this->krsBimbingan()->findOrFail($id);
        $krs->update(['status' => Krs::STATUS_DISETUJUI, 'catatan' => null]);

        session()->flash('info', "KRS #{$krs->id} disetujui.");
    }

    public function tolak(int $id): void
    {
        $this->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:1000'],
        ], [], ['catatan' => 'alasan penolakan']);

        $krs = $this->krsBimbingan()->findOrFail($id);
        $krs->update(['status' => Krs::STATUS_DITOLAK, 'catatan' => $this->catatan]);

        $this->reset('catatan');
        session()->flash('info', "KRS #{$krs->id} ditolak.");
    }

    /**
     * KRS yang menjadi bimbingan PA yang sedang login.
     * Jika tabel M1 belum tersedia, tampilkan semua KRS berstatus diajukan.
     *
     * @return Builder<Krs>
     */
    private function krsBimbingan(): Builder
    {
        $query = Krs::query()->with('details');

        $dosenId = $this->dosenId();

        if ($dosenId && Schema::hasTable('mahasiswa') && Schema::hasColumn('mahasiswa', 'dosen_pa_id')) {
            try {
                $mhsIds = DB::table('mahasiswa')->where('dosen_pa_id', $dosenId)->pluck('id')->all();
                $query->whereIn('mahasiswa_id', $mhsIds);
            } catch (\Throwable) {
                // abaikan filter, tampilkan semua
            }
        }

        return $query;
    }

    private function dosenId(): ?int
    {
        if (! Schema::hasTable('dosen')) {
            return null;
        }

        try {
            $id = DB::table('dosen')->where('user_id', auth()->id())->value('id');

            return $id ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        $krsList = $this->krsBimbingan()
            ->where('status', Krs::STATUS_DIAJUKAN)
            ->when($this->search !== '', fn ($query) => $query->where('mahasiswa_id', $this->search))
            ->latest()
            ->paginate(10);

        return view('livewire.krs.persetujuan-pa', [
            'krsList' => $krsList,
        ]);
    }
}
