<?php

namespace App\Livewire\Krs;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\KelasWaitlist;
use App\Models\Krs;
use App\Services\AkademikService;
use App\Services\KalenderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * Pengajuan KRS oleh mahasiswa: pilih kelas, cek SKS maks by IPK,
 * cek tunggakan tagihan, lalu ajukan untuk persetujuan PA.
 * Kelas penuh diarahkan ke waiting list (tabel `kelas_waitlist`).
 */
class Pengajuan extends Component
{
    use RequiresRoles;

    /** Otorisasi owner-scope dicek manual di mount(). */
    public array $allowedRoles = [];

    public ?int $tahunAkademikId = null;

    public float $ipk = 0.0;

    /** @var list<int> */
    public array $kelasDipilih = [];

    public ?int $krsId = null;

    public ?string $pesanBlokir = null;

    public function mount(?int $tahunId = null): void
    {
        $this->tahunAkademikId = $tahunId ?? $this->tahunAktifId();
        // IPK selalu dihitung server-side dari Nilai (tidak dipercaya dari input).
        $this->ipk = (float) (AkademikService::ringkasan($this->mahasiswaId())['ipk'] ?? 0.0);
        $this->kelasDipilih = $this->krsBerjalan()?->details()->pluck('kelas_id')->filter()->map(fn ($id): int => (int) $id)->all() ?? [];
        $this->kelasDipilih = array_values(array_unique(array_map('intval', $this->kelasDipilih)));
    }

    public function toggleKelas(int $kelasId): void
    {
        if ($kelasId <= 0) {
            return;
        }

        if (in_array($kelasId, $this->kelasDipilih, true)) {
            $this->kelasDipilih = array_values(array_diff($this->kelasDipilih, [$kelasId]));
        } else {
            $this->kelasDipilih[] = $kelasId;
        }
    }

    public function getKelasTersediaProperty(): Collection
    {
        if (! Schema::hasTable('kelas')) {
            return collect();
        }

        $query = DB::table('kelas')->select('kelas.*');

        if (Schema::hasTable('matakuliah')) {
            $query->leftJoin('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
                ->addSelect(['matakuliah.nama as matakuliah_nama', 'matakuliah.kode as matakuliah_kode', 'matakuliah.sks']);
        }

        if ($this->tahunAkademikId && Schema::hasColumn('kelas', 'tahun_akademik_id')) {
            $query->where('kelas.tahun_akademik_id', $this->tahunAkademikId);
        }

        return $query->orderBy('kelas.id')->get();
    }

    public function getTotalSksProperty(): int
    {
        return $this->sksPerKelas()->sum();
    }

    public function getSksMaksProperty(): int
    {
        return AkademikService::batasSks($this->ipk);
    }

    /**
     * Entri waiting list milik mahasiswa pada tahun akademik berjalan.
     */
    public function getWaitlistSayaProperty(): Collection
    {
        if (! Schema::hasTable('kelas_waitlist')) {
            return collect();
        }

        $query = KelasWaitlist::query()->where('mahasiswa_id', $this->mahasiswaId());

        if ($this->tahunAkademikId && Schema::hasColumn('kelas', 'tahun_akademik_id')) {
            $query->whereHas('kelas', fn ($q) => $q->where('tahun_akademik_id', $this->tahunAkademikId));
        }

        return $query->orderBy('id')->get();
    }

    /**
     * @return Collection<int, int> peta kelas_id => sks
     */
    private function sksPerKelas(): Collection
    {
        return AkademikService::sksPerKelas($this->kelasDipilih);
    }

    public function adaTunggakan(): bool
    {
        return AkademikService::adaTunggakan($this->mahasiswaId());
    }

    /**
     * Masuk antrean waiting list untuk kelas yang kuotanya penuh.
     * Idempoten: entri ganda tidak dibuat.
     */
    public function masukWaitlist(int $kelasId): void
    {
        if ($kelasId <= 0 || ! Schema::hasTable('kelas_waitlist')) {
            $this->addError('kelasDipilih', 'Waiting list belum tersedia.');

            return;
        }

        $mahasiswaId = $this->mahasiswaId();

        if (! AkademikService::kelasPenuh($kelasId)) {
            $this->addError('kelasDipilih', "Kuota kelas #{$kelasId} masih tersedia, silakan ajukan KRS langsung.");

            return;
        }

        $sudah = KelasWaitlist::query()
            ->where('kelas_id', $kelasId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->where('status', KelasWaitlist::STATUS_MENUNGGU)
            ->exists();

        if ($sudah) {
            $posisi = AkademikService::posisiWaitlist($kelasId, $mahasiswaId);
            session()->flash('info', "Anda sudah dalam waiting list kelas #{$kelasId} (posisi {$posisi}).");

            return;
        }

        KelasWaitlist::query()->create([
            'kelas_id' => $kelasId,
            'mahasiswa_id' => $mahasiswaId,
            'status' => KelasWaitlist::STATUS_MENUNGGU,
        ]);

        $posisi = AkademikService::posisiWaitlist($kelasId, $mahasiswaId);
        session()->flash('info', "Masuk waiting list kelas #{$kelasId} (posisi {$posisi}).");
    }

    /**
     * Keluar dari antrean waiting list milik sendiri.
     */
    public function keluarWaitlist(int $kelasId): void
    {
        if (! Schema::hasTable('kelas_waitlist')) {
            return;
        }

        KelasWaitlist::query()
            ->where('kelas_id', $kelasId)
            ->where('mahasiswa_id', $this->mahasiswaId())
            ->where('status', KelasWaitlist::STATUS_MENUNGGU)
            ->update(['status' => KelasWaitlist::STATUS_DIBATALKAN]);

        session()->flash('info', "Keluar dari waiting list kelas #{$kelasId}.");
    }

    public function ajukan(): void
    {
        $this->kelasDipilih = array_values(array_unique(array_map('intval', $this->kelasDipilih)));

        $this->validate([
            'tahunAkademikId' => ['required', 'integer', 'min:1'],
            'kelasDipilih' => ['required', 'array', 'min:1'],
            'kelasDipilih.*' => ['integer', 'min:1'],
        ]);

        $mahasiswaId = $this->mahasiswaId();

        // IPK otoritatif dari server — input $this->ipk dari browser TIDAK dipakai
        // untuk validasi maupun snapshot (mencegah penggelembungan batas SKS).
        $ipkServer = (float) (AkademikService::ringkasan($mahasiswaId)['ipk'] ?? 0.0);
        $this->ipk = $ipkServer;

        // Revisi KRS yang sudah disetujui tidak boleh menimpa sepihak.
        $berjalan = $this->krsBerjalan();
        if ($berjalan && $berjalan->status === Krs::STATUS_DISETUJUI) {
            $this->addError('kelasDipilih', 'KRS sudah disetujui Dosen PA. Hubungi admin prodi untuk revisi.');

            return;
        }

        // Kunci kalender: pengajuan hanya dibuka dalam rentang event jenis `krs`.
        if (! KalenderService::isOpen('krs')) {
            $this->pesanBlokir = KalenderService::closedMessage('krs');
            $this->addError('kelasDipilih', $this->pesanBlokir);

            return;
        }

        // Validasi terpusat (SKS-maks, tunggakan, prasyarat, bentrok, kuota).
        // Dijalankan ulang di dalam transaksi agar lolos dari race condition.
        // KRS berjalan milik sendiri dikecualikan dari hitung kuota (revisi).
        $abaikanKrsId = $berjalan?->id;
        foreach (AkademikService::validasiKrs($mahasiswaId, $this->kelasDipilih, $ipkServer, $abaikanKrsId) as $pesan) {
            $this->addError('kelasDipilih', $pesan);
        }

        if ($this->getErrorBag()->has('kelasDipilih')) {
            $tunggakan = array_filter(
                $this->getErrorBag()->get('kelasDipilih'),
                fn (string $m): bool => str_contains($m, 'menunggak')
            );

            if ($tunggakan !== []) {
                $this->pesanBlokir = reset($tunggakan);
            }

            return;
        }

        $sksMap = $this->sksPerKelas();

        try {
            DB::transaction(function () use ($sksMap, $mahasiswaId, $abaikanKrsId, $ipkServer): void {
                // Kunci baris kelas agar dua pengaju bersamaan tidak overbook kuota.
                if (Schema::hasTable('kelas')) {
                    DB::table('kelas')->whereIn('id', $sksMap->keys()->all())->lockForUpdate()->get();
                }

                // Cek ulang kuota di dalam transaksi (atomic).
                foreach ($sksMap->keys()->all() as $kelasId) {
                    if (AkademikService::kelasPenuh((int) $kelasId, $abaikanKrsId)) {
                        throw new \RuntimeException("Kuota kelas #{$kelasId} penuh. Silakan masuk waiting list.");
                    }
                }

                $krs = Krs::query()->updateOrCreate(
                    ['mahasiswa_id' => $mahasiswaId, 'tahun_akademik_id' => $this->tahunAkademikId],
                    [
                        'status' => Krs::STATUS_DIAJUKAN,
                        'total_sks' => $sksMap->sum(),
                        'ipk_snapshot' => $ipkServer,
                        'catatan' => null,
                    ]
                );

                $krs->details()->delete();

                foreach ($sksMap as $kelasId => $sks) {
                    $krs->details()->create([
                        'kelas_id' => $kelasId,
                        'sks' => $sks,
                        'status' => 'aktif',
                    ]);
                }

                $this->krsId = $krs->id;
            });
        } catch (\RuntimeException $e) {
            $this->addError('kelasDipilih', $e->getMessage());

            return;
        }

        $this->pesanBlokir = null;
        session()->flash('info', "KRS berhasil diajukan ({$this->totalSks} SKS). Menunggu persetujuan Dosen PA.");
    }

    private function krsBerjalan(): ?Krs
    {
        if (! $this->tahunAkademikId) {
            return null;
        }

        return Krs::query()
            ->where('mahasiswa_id', $this->mahasiswaId())
            ->where('tahun_akademik_id', $this->tahunAkademikId)
            ->first();
    }

    private function mahasiswaId(): int
    {
        if (! Schema::hasTable('mahasiswa')) {
            return (int) auth()->id();
        }

        try {
            $id = DB::table('mahasiswa')->where('user_id', auth()->id())->value('id');

            return (int) ($id ?? auth()->id());
        } catch (\Throwable) {
            return (int) auth()->id();
        }
    }

    private function tahunAktifId(): ?int
    {
        if (! Schema::hasTable('tahun_akademik')) {
            return null;
        }

        try {
            return DB::table('tahun_akademik')->where('aktif', true)->value('id');
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.krs.pengajuan', [
            'kelasTersedia' => $this->kelasTersedia,
            'totalSks' => $this->totalSks,
            'sksMaks' => $this->sksMaks,
            'waitlistSaya' => $this->waitlistSaya,
        ]);
    }
}
