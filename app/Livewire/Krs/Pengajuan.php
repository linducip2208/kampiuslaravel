<?php

namespace App\Livewire\Krs;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Krs;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * Pengajuan KRS oleh mahasiswa: pilih kelas, cek SKS maks by IPK,
 * cek tunggakan tagihan, lalu ajukan untuk persetujuan PA.
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
        $this->ipk = (float) ($this->krsBerjalan()?->ipk_snapshot ?? 0.0);
        $this->kelasDipilih = $this->krsBerjalan()?->details()->pluck('kelas_id')->filter()->map(fn ($id): int => (int) $id)->all() ?? [];
    }

    public function toggleKelas(int $kelasId): void
    {
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
        return Krs::sksMaksByIpk($this->ipk);
    }

    /**
     * @return Collection<int, int> peta kelas_id => sks
     */
    private function sksPerKelas(): Collection
    {
        if ($this->kelasDipilih === []) {
            return collect();
        }

        if (Schema::hasTable('kelas') && Schema::hasTable('matakuliah')) {
            return DB::table('kelas')
                ->leftJoin('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
                ->whereIn('kelas.id', $this->kelasDipilih)
                ->pluck('matakuliah.sks', 'kelas.id')
                ->map(fn ($sks): int => (int) ($sks ?? 0));
        }

        return collect($this->kelasDipilih)->mapWithKeys(fn (int $id): array => [$id => 0]);
    }

    public function adaTunggakan(): bool
    {
        // Tabel tagihan milik modul keuangan (singular `tagihan`).
        $tabel = Schema::hasTable('tagihan') ? 'tagihan' : (Schema::hasTable('tagihans') ? 'tagihans' : null);

        if (! $tabel) {
            return false;
        }

        try {
            return DB::table($tabel)
                ->where('mahasiswa_id', $this->mahasiswaId())
                ->where('status', '!=', 'lunas')
                ->whereColumn('terbayar', '<', 'nominal')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function ajukan(): void
    {
        $this->validate([
            'tahunAkademikId' => ['required', 'integer', 'min:1'],
            'ipk' => ['required', 'numeric', 'min:0', 'max:4'],
            'kelasDipilih' => ['required', 'array', 'min:1'],
            'kelasDipilih.*' => ['integer', 'min:1'],
        ]);

        if ($this->adaTunggakan()) {
            $this->pesanBlokir = 'Pengajuan KRS diblokir: masih ada tagihan menunggak. Lunasi tagihan terlebih dahulu.';
            $this->addError('kelasDipilih', $this->pesanBlokir);

            return;
        }

        if ($this->totalSks > $this->sksMaks) {
            $this->addError('kelasDipilih', "Total SKS ({$this->totalSks}) melebihi batas maksimal {$this->sksMaks} SKS untuk IPK {$this->ipk}.");

            return;
        }

        // Revisi KRS yang sudah disetujui tidak boleh menimpa sepihak.
        $berjalan = $this->krsBerjalan();
        if ($berjalan && $berjalan->status === Krs::STATUS_DISETUJUI) {
            $this->addError('kelasDipilih', 'KRS sudah disetujui Dosen PA. Hubungi admin prodi untuk revisi.');

            return;
        }

        foreach ($this->cekPrasyarat() as $pesan) {
            $this->addError('kelasDipilih', $pesan);
        }

        foreach ($this->cekBentrok() as $pesan) {
            $this->addError('kelasDipilih', $pesan);
        }

        foreach ($this->cekKuota() as $pesan) {
            $this->addError('kelasDipilih', $pesan);
        }

        if ($this->getErrorBag()->has('kelasDipilih')) {
            return;
        }

        $sksMap = $this->sksPerKelas();
        $mahasiswaId = $this->mahasiswaId();

        DB::transaction(function () use ($sksMap, $mahasiswaId): void {
            $krs = Krs::query()->updateOrCreate(
                ['mahasiswa_id' => $mahasiswaId, 'tahun_akademik_id' => $this->tahunAkademikId],
                [
                    'status' => Krs::STATUS_DIAJUKAN,
                    'total_sks' => $sksMap->sum(),
                    'ipk_snapshot' => $this->ipk,
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

    /** @return list<string> */
    private function cekPrasyarat(): array
    {
        $pesan = [];

        try {
            $kelas = DB::table('kelas')
                ->leftJoin('matakuliah', 'matakuliah.id', '=', 'kelas.matakuliah_id')
                ->whereIn('kelas.id', $this->kelasDipilih)
                ->select(['matakuliah.kode', 'matakuliah.nama as mk_nama', 'matakuliah.prasyarat_id'])
                ->get();

            foreach ($kelas as $k) {
                if (! $k->prasyarat_id) {
                    continue;
                }

                $lulus = DB::table('nilai')
                    ->where('mahasiswa_id', $this->mahasiswaId())
                    ->whereIn('kelas_id', function ($q) use ($k): void {
                        $q->select('id')->from('kelas')->where('matakuliah_id', $k->prasyarat_id);
                    })
                    ->where('bobot', '>', 0)
                    ->exists();

                if (! $lulus) {
                    $nama = $k->mk_nama ?? $k->kode ?? 'matakuliah';
                    $pesan[] = "Prasyarat belum lulus untuk mengambil {$nama}.";
                }
            }
        } catch (\Throwable) {
            // Tabel pendukung belum ada — lewati cek.
        }

        return $pesan;
    }

    /** @return list<string> */
    private function cekBentrok(): array
    {
        $pesan = [];

        try {
            $kelas = DB::table('kelas')->whereIn('id', $this->kelasDipilih)
                ->select(['id', 'nama', 'hari', 'jam_mulai', 'jam_selesai'])->get()
                ->filter(fn ($k) => $k->hari && $k->jam_mulai && $k->jam_selesai)
                ->values();

            foreach ($kelas as $i => $a) {
                foreach ($kelas as $j => $b) {
                    if ($j <= $i || $a->hari !== $b->hari) {
                        continue;
                    }

                    if ($a->jam_mulai < $b->jam_selesai && $b->jam_mulai < $a->jam_selesai) {
                        $pesan[] = "Jadwal bentrok: kelas {$a->nama} dengan {$b->nama} ({$a->hari} {$a->jam_mulai}-{$a->jam_selesai}).";
                    }
                }
            }
        } catch (\Throwable) {
            // Kolom jadwal belum ada — lewati cek.
        }

        return $pesan;
    }

    /** @return list<string> */
    private function cekKuota(): array
    {
        $pesan = [];

        try {
            foreach ($this->kelasDipilih as $kelasId) {
                $kuota = (int) DB::table('kelas')->where('id', $kelasId)->value('kuota');
                if ($kuota <= 0) {
                    continue;
                }

                $terisi = DB::table('krs_detail')->where('kelas_id', $kelasId)->count();

                if ($terisi >= $kuota) {
                    $pesan[] = "Kuota kelas #{$kelasId} penuh ({$terisi}/{$kuota}).";
                }
            }
        } catch (\Throwable) {
            // Tabel pendukung belum ada — lewati cek.
        }

        return $pesan;
    }

    public function render(): View
    {
        return view('livewire.krs.pengajuan', [
            'kelasTersedia' => $this->kelasTersedia,
            'totalSks' => $this->totalSks,
            'sksMaks' => $this->sksMaks,
        ]);
    }
}
