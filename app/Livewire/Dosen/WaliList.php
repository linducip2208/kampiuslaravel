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

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

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

        // IPK berbobot SKS — konsisten dengan AkademikService::ringkasan()
        // (AVG(bobot) tanpa bobot SKS terbukti salah untuk SKS bervariasi).
        $ipk = Nilai::whereIn('nilai.mahasiswa_id', $mhsIds)
            ->join('kelas', 'kelas.id', '=', 'nilai.kelas_id')
            ->join('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
            ->selectRaw('nilai.mahasiswa_id, SUM(nilai.bobot * matakuliah.sks) * 1.0 / NULLIF(SUM(matakuliah.sks), 0) as ipk, COUNT(*) as total_mk')
            ->groupBy('nilai.mahasiswa_id')
            ->pluck('ipk', 'mahasiswa_id');

        $bermasalah = Nilai::whereIn('mahasiswa_id', $mhsIds)
            ->where(function ($q): void {
                $q->where('kehadiran', '<', Nilai::KEHADIRAN_MINIMAL)
                    ->orWhereIn('huruf', ['D', 'E']);
            })
            ->selectRaw('mahasiswa_id, COUNT(*) as total')
            ->groupBy('mahasiswa_id')
            ->pluck('total', 'mahasiswa_id');

        // M-025/P4: status KRS terbaru per mahasiswa via 1 query
        // (MAX(id) per mahasiswa) — tanpa muat semua riwayat + groupBy PHP.
        $krsStatus = collect();

        if (Schema::hasTable('krs') && $mhsIds !== []) {
            $latestIds = DB::table('krs')
                ->whereIn('mahasiswa_id', $mhsIds)
                ->groupBy('mahasiswa_id')
                ->selectRaw('mahasiswa_id, MAX(id) as id');

            $krsStatus = DB::table('krs')
                ->joinSub($latestIds, 'terbaru', fn ($j) => $j->on('krs.id', '=', 'terbaru.id'))
                ->pluck('krs.status', 'krs.mahasiswa_id');
        }

        return view('livewire.dosen.wali-list', [
            'wali' => $wali,
            'ipk' => $ipk,
            'bermasalah' => $bermasalah,
            'krsStatus' => $krsStatus,
        ]);
    }
}
