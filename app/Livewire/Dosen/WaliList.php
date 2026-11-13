<?php

namespace App\Livewire\Dosen;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Nilai;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class WaliList extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'superadmin', 'admin_prodi'];

    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
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
        $dosenId = $this->dosenId();

        $query = DB::table('mahasiswa')
            ->leftJoin('users', 'users.id', '=', 'mahasiswa.user_id')
            ->leftJoin('prodi', 'prodi.id', '=', 'mahasiswa.prodi_id')
            ->select('mahasiswa.*', 'users.name as nama', 'prodi.nama as prodi_nama');

        if ($dosenId) {
            $query->where('mahasiswa.dosen_pa_id', $dosenId);
        }

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('mahasiswa.nim', 'like', '%'.$this->search.'%')
                    ->orWhere('users.name', 'like', '%'.$this->search.'%');
            });
        }

        $wali = $query->orderBy('mahasiswa.nim')->paginate(15);

        $mhsIds = collect($wali->items())->pluck('id')->all();

        $ipk = Nilai::whereIn('mahasiswa_id', $mhsIds)
            ->selectRaw('mahasiswa_id, AVG(bobot) as ipk, COUNT(*) as total_mk')
            ->groupBy('mahasiswa_id')
            ->pluck('ipk', 'mahasiswa_id');

        $bermasalah = Nilai::whereIn('mahasiswa_id', $mhsIds)
            ->where(function ($q): void {
                $q->where('kehadiran', '<', Nilai::KEHADIRAN_MINIMAL)
                    ->orWhereIn('huruf', ['D', 'E']);
            })
            ->selectRaw('mahasiswa_id, COUNT(*) as total')
            ->groupBy('mahasiswa_id')
            ->pluck('total', 'mahasiswa_id');

        $krsStatus = Schema::hasTable('krs') && $mhsIds !== []
            ? DB::table('krs')->whereIn('mahasiswa_id', $mhsIds)->latest('id')
                ->get()->groupBy('mahasiswa_id')->map(fn ($rows) => $rows->first()->status)
            : collect();

        return view('livewire.dosen.wali-list', [
            'wali' => $wali,
            'ipk' => $ipk,
            'bermasalah' => $bermasalah,
            'krsStatus' => $krsStatus,
        ]);
    }
}
