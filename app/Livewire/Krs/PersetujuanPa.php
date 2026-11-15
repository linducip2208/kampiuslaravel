<?php

namespace App\Livewire\Krs;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Krs;
use App\Services\AkademikService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Persetujuan KRS oleh Dosen PA (approve / tolak + catatan).
 * Hardening: persetujuan memverifikasi ulang SKS-maks by IPK,
 * prasyarat, bentrok, kuota, dan tunggakan di dalam transaksi.
 * Penolakan membebaskan kuota dan memicu promosi waiting list.
 */
class PersetujuanPa extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    use WithPagination;

    public string $search = '';

    public string $catatan = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setujui(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        try {
            DB::transaction(function () use ($id): void {
                $krs = $this->krsBimbingan()->lockForUpdate()->findOrFail($id);

                if ($krs->status !== Krs::STATUS_DIAJUKAN) {
                    throw new \RuntimeException("KRS #{$krs->id} sudah diproses (status: {$krs->status}).");
                }

                $kelasIds = $krs->details()->pluck('kelas_id')->filter()->map(fn ($v): int => (int) $v)->all();
                $ipk = (float) ($krs->ipk_snapshot ?? 0.0);

                // Verifikasi ulang penuh; KRS yang sedang disetujui
                // dikecualikan dari hitung kuota (kursinya milik dia).
                foreach (AkademikService::validasiKrs((int) $krs->mahasiswa_id, $kelasIds, $ipk, (int) $krs->id) as $pesan) {
                    throw new \RuntimeException("KRS #{$krs->id} gagal verifikasi: {$pesan}");
                }

                $krs->update(['status' => Krs::STATUS_DISETUJUI, 'catatan' => null]);

                session()->flash('info', "KRS #{$krs->id} disetujui.");
            });
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function tolak(int $id): void
    {
        $this->validate([
            'catatan' => ['required', 'string', 'min:3', 'max:1000'],
        ], [], ['catatan' => 'alasan penolakan']);

        if ($id <= 0) {
            return;
        }

        $dipromosikan = [];

        DB::transaction(function () use ($id, &$dipromosikan): void {
            $krs = $this->krsBimbingan()->lockForUpdate()->findOrFail($id);

            if ($krs->status !== Krs::STATUS_DIAJUKAN) {
                throw new \RuntimeException("KRS #{$krs->id} sudah diproses (status: {$krs->status}).");
            }

            $kelasIds = $krs->details()->pluck('kelas_id')->filter()->map(fn ($v): int => (int) $v)->all();

            $krs->update(['status' => Krs::STATUS_DITOLAK, 'catatan' => $this->catatan]);

            // KRS ditolak tidak memakan kuota → promosikan antrean terdepan.
            foreach ($kelasIds as $kelasId) {
                $mhs = AkademikService::promosikanWaitlist($kelasId);

                if ($mhs !== null) {
                    $dipromosikan[] = $kelasId;
                }
            }
        });

        $this->reset('catatan');

        $info = "KRS #{$id} ditolak.";
        if ($dipromosikan !== []) {
            $info .= ' Waitlist dipromosikan untuk kelas: '.implode(', ', $dipromosikan).'.';
        }

        session()->flash('info', $info);
    }

    /**
     * Promosikan antrean terdepan satu kelas bila kuota tersedia.
     */
    public function promosikanWaitlist(int $kelasId): void
    {
        if ($kelasId <= 0) {
            return;
        }

        $mhs = DB::transaction(fn (): ?int => AkademikService::promosikanWaitlist($kelasId));

        if ($mhs === null) {
            session()->flash('error', "Tidak ada waitlist yang bisa dipromosikan untuk kelas #{$kelasId}.");
        } else {
            session()->flash('info', "Waitlist kelas #{$kelasId} dipromosikan (mahasiswa #{$mhs}).");
        }
    }

    /**
     * KRS yang menjadi bimbingan PA yang sedang login.
     * Jika tabel M1 belum tersedia, tampilkan semua KRS berstatus diajukan.
     *
     * @return Builder<Krs>
     */
    private function krsBimbingan(): Builder
    {
        $query = Krs::query()->with(['details.kelas.matakuliah', 'mahasiswa.user', 'mahasiswa.prodi']);

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
