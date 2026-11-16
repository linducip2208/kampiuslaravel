<?php

namespace App\Livewire\Lms;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\BankSoal as BankSoalModel;
use App\Models\Matakuliah;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bank soal per mata kuliah (PG + esai). Fondasi kuis/ujian;
 * belum dipakai mesin ujian (lihat ROADMAP).
 */
class BankSoal extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    public ?int $editId = null;

    public ?int $matakuliah_id = null;

    public string $jenis = 'pg';

    public string $pertanyaan = '';

    /** @var list<string> */
    public array $opsi = ['', '', '', ''];

    public string $kunci = '';

    public int $bobot = 1;

    public bool $aktif = true;

    public ?int $filterMk = null;

    /** Tujuan klon soal lintas MK (dipilih di header daftar). */
    public ?int $klonTujuanMk = null;

    public string $search = '';

    public string $message = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterMk(): void
    {
        $this->resetPage();
    }

    public function simpan(): void
    {
        $this->validate([
            'matakuliah_id' => 'required|exists:matakuliah,id',
            'jenis' => 'required|in:pg,esai',
            'pertanyaan' => 'required|string|max:2000',
            'opsi' => 'nullable|array|max:6',
            'opsi.*' => 'nullable|string|max:500',
            'kunci' => 'nullable|string|max:5',
            'bobot' => 'required|integer|min:1|max:100',
            'aktif' => 'boolean',
        ]);

        if ($this->jenis === 'pg') {
            $opsiBersih = array_values(array_filter(array_map('trim', $this->opsi), fn ($o) => $o !== ''));
            if (count($opsiBersih) < 2) {
                $this->addError('opsi', 'Soal pilihan ganda butuh minimal 2 opsi jawaban.');
            }
            if ($this->kunci !== '' && ! in_array(mb_strtoupper(trim($this->kunci)), ['A', 'B', 'C', 'D', 'E', 'F'], true)) {
                $this->addError('kunci', 'Kunci harus A-F sesuai urutan opsi.');
            }
            if ($this->getErrorBag()->has('opsi') || $this->getErrorBag()->has('kunci')) {
                return;
            }
            $this->opsi = $opsiBersih;
        } else {
            $this->opsi = [];
        }

        BankSoalModel::updateOrCreate(['id' => $this->editId ?? 0], [
            'matakuliah_id' => $this->matakuliah_id,
            'jenis' => $this->jenis,
            'pertanyaan' => trim($this->pertanyaan),
            'opsi' => $this->jenis === 'pg' ? $this->opsi : null,
            'kunci' => $this->kunci !== '' ? mb_strtoupper(trim($this->kunci)) : null,
            'bobot' => $this->bobot,
            'aktif' => $this->aktif,
        ]);

        $this->reset(['editId', 'matakuliah_id', 'jenis', 'pertanyaan', 'opsi', 'kunci', 'bobot', 'aktif']);
        $this->opsi = ['', '', '', ''];
        $this->jenis = 'pg';
        $this->message = 'Soal tersimpan di bank soal.';
    }

    public function edit(int $id): void
    {
        $s = BankSoalModel::findOrFail($id);
        $this->editId = $s->id;
        $this->matakuliah_id = $s->matakuliah_id;
        $this->jenis = $s->jenis;
        $this->pertanyaan = $s->pertanyaan;
        $this->opsi = array_pad((array) ($s->opsi ?? []), 4, '');
        $this->kunci = (string) ($s->kunci ?? '');
        $this->bobot = (int) $s->bobot;
        $this->aktif = (bool) $s->aktif;
    }

    public function batal(): void
    {
        $this->reset(['editId', 'matakuliah_id', 'jenis', 'pertanyaan', 'kunci', 'bobot', 'aktif']);
        $this->opsi = ['', '', '', ''];
        $this->jenis = 'pg';
    }

    public function hapus(int $id): void
    {
        BankSoalModel::findOrFail($id)->delete();
        $this->message = 'Soal dihapus dari bank.';
    }

    /** Klon soal ke mata kuliah lain (pakai ulang lintas kelas; kunci ikut). */
    public function klon(int $id): void
    {
        $this->validate(['klonTujuanMk' => 'required|exists:matakuliah,id']);

        $sumber = BankSoalModel::findOrFail($id);

        BankSoalModel::create([
            'matakuliah_id' => $this->klonTujuanMk,
            'jenis' => $sumber->jenis,
            'pertanyaan' => $sumber->pertanyaan,
            'opsi' => $sumber->opsi,
            'kunci' => $sumber->kunci,
            'bobot' => $sumber->bobot,
            'aktif' => $sumber->aktif,
        ]);

        $this->message = "Soal #{$id} diklon ke MK tujuan (kunci ikut).";
    }

    public function render(): View
    {
        return view('livewire.lms.bank-soal', [
            'soals' => BankSoalModel::with('matakuliah')
                ->when($this->filterMk, fn ($q) => $q->where('matakuliah_id', $this->filterMk))
                ->when($this->search !== '', fn ($q) => $q->where('pertanyaan', 'like', '%'.str_replace(['%', '_'], '', $this->search).'%'))
                ->latest('id')->paginate(10),
            'matakuliahs' => Matakuliah::orderBy('kode')->get(['id', 'kode', 'nama']),
        ]);
    }
}
