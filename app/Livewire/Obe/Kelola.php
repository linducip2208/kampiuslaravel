<?php

namespace App\Livewire\Obe;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\CpmkAsesmen;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Prodi;
use App\Services\ObeService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-049: kelola CPL/CPMK + pemetaan asesmen + rekap ketercapaian.
 */
class Kelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $prodi_id = null;

    public string $cpl_kode = '';

    public string $cpl_deskripsi = '';

    public ?int $mk_id = null;

    public ?int $cpl_id = null;

    public string $cpmk_kode = '';

    public string $cpmk_deskripsi = '';

    public float $cpmk_bobot = 1;

    public string $asesmen_tipe = 'quiz_soal';

    public ?int $asesmen_cpmk_id = null;

    public int $asesmen_sumber_id = 0;

    public float $asesmen_bobot = 1;

    public string $nimRekap = '';

    public string $message = '';

    public function simpanCpl(): void
    {
        $this->validate([
            'prodi_id' => 'required|exists:prodi,id',
            'cpl_kode' => 'required|string|max:20',
            'cpl_deskripsi' => 'required|string',
        ]);

        Cpl::updateOrCreate(
            ['prodi_id' => $this->prodi_id, 'kode' => $this->cpl_kode],
            ['deskripsi' => $this->cpl_deskripsi]
        );

        $this->reset(['cpl_kode', 'cpl_deskripsi']);
        $this->message = 'CPL tersimpan.';
    }

    public function simpanCpmk(): void
    {
        $this->validate([
            'mk_id' => 'required|exists:matakuliah,id',
            'cpl_id' => 'required|exists:cpl,id',
            'cpmk_kode' => 'required|string|max:20',
            'cpmk_deskripsi' => 'required|string',
            'cpmk_bobot' => 'required|numeric|min:0.1|max:100',
        ]);

        Cpmk::updateOrCreate(
            ['matakuliah_id' => $this->mk_id, 'kode' => $this->cpmk_kode],
            ['cpl_id' => $this->cpl_id, 'deskripsi' => $this->cpmk_deskripsi, 'bobot' => $this->cpmk_bobot]
        );

        $this->reset(['cpmk_kode', 'cpmk_deskripsi', 'cpmk_bobot']);
        $this->message = 'CPMK tersimpan.';
    }

    public function petakan(): void
    {
        $this->validate([
            'asesmen_cpmk_id' => 'required|exists:cpmk,id',
            'asesmen_tipe' => 'required|in:quiz_soal,tugas',
            'asesmen_sumber_id' => 'required|integer|min:1',
            'asesmen_bobot' => 'required|numeric|min:0.1|max:100',
        ]);

        CpmkAsesmen::updateOrCreate(
            ['cpmk_id' => $this->asesmen_cpmk_id, 'sumber_tipe' => $this->asesmen_tipe, 'sumber_id' => $this->asesmen_sumber_id],
            ['bobot' => $this->asesmen_bobot]
        );

        $this->message = 'Asesmen dipetakan ke CPMK.';
    }

    public function render()
    {
        return view('livewire.obe.kelola', [
            'prodis' => Prodi::orderBy('nama')->get(),
            'cpls' => $this->prodi_id ? Cpl::where('prodi_id', $this->prodi_id)->orderBy('kode')->get() : collect(),
            'mks' => $this->prodi_id ? Matakuliah::where('prodi_id', $this->prodi_id)->orderBy('kode')->get() : collect(),
            'cpmks' => Cpmk::with(['cpl', 'matakuliah', 'asesmen'])->latest('id')->paginate(10),
            'rekap' => $this->nimRekap !== ''
                ? ObeService::ketercapaian((int) Mahasiswa::where('nim', trim($this->nimRekap))->value('id'), $this->prodi_id)
                : null,
        ]);
    }
}
