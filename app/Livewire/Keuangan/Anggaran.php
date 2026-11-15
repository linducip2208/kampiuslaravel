<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Anggaran as AnggaranModel;
use App\Models\TahunAkademik;
use App\Services\AnggaranService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-033: kelola anggaran + pantau realisasi vs pagu.
 */
class Anggaran extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'kasir', 'akuntansi', 'superadmin', 'admin_prodi'];

    public ?int $editId = null;

    public ?int $tahun_akademik_id = null;

    public string $kode = '';

    public string $nama = '';

    public int $pagu = 0;

    public string $keterangan = '';

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'tahun_akademik_id' => 'required|exists:tahun_akademik,id',
            'kode' => 'required|string|max:60',
            'nama' => 'required|string|max:150',
            'pagu' => 'required|integer|min:0',
            'keterangan' => 'nullable|string|max:500',
        ]);

        AnggaranModel::updateOrCreate(['id' => $this->editId ?? 0], [
            'tahun_akademik_id' => $this->tahun_akademik_id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'pagu' => $this->pagu,
            'keterangan' => $this->keterangan ?: null,
        ]);

        $this->reset(['editId', 'kode', 'nama', 'pagu', 'keterangan']);
        $this->message = 'Anggaran tersimpan.';
    }

    public function edit(int $id): void
    {
        $a = AnggaranModel::findOrFail($id);
        $this->editId = $a->id;
        $this->tahun_akademik_id = $a->tahun_akademik_id;
        $this->kode = $a->kode;
        $this->nama = $a->nama;
        $this->pagu = (int) $a->pagu;
        $this->keterangan = (string) ($a->keterangan ?? '');
    }

    public function hapus(int $id): void
    {
        AnggaranModel::findOrFail($id)->delete();
        $this->message = 'Anggaran dihapus.';
    }

    public function render()
    {
        $tahunId = $this->tahun_akademik_id
            ?? TahunAkademik::aktif()->value('id')
            ?? TahunAkademik::orderByDesc('id')->value('id');

        return view('livewire.keuangan.anggaran', [
            'tahuns' => TahunAkademik::orderByDesc('id')->get(),
            'ringkasan' => $tahunId ? AnggaranService::ringkasanPeriode((int) $tahunId) : [],
            'tahunAktif' => $tahunId,
        ]);
    }
}
