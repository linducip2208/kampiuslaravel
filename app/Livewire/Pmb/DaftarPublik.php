<?php

namespace App\Livewire\Pmb;

use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Formulir pendaftaran PMB publik (tanpa auth).
 */
class DaftarPublik extends Component
{
    public string $nama = '';

    public string $email = '';

    public string $hp = '';

    public string $sekolah_asal = '';

    public ?int $gelombang_id = null;

    public ?int $prodi1_id = null;

    public ?int $prodi2_id = null;

    public ?string $noDaftarBerhasil = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:100', 'unique:pmb_pendaftars,email'],
            'hp' => ['required', 'regex:/^[0-9+\- ]{9,16}$/'],
            'sekolah_asal' => ['required', 'string', 'min:3', 'max:150'],
            'gelombang_id' => ['required', 'integer', 'exists:pmb_gelombangs,id'],
            'prodi1_id' => ['required', 'integer', 'min:1'],
            'prodi2_id' => ['nullable', 'integer', 'min:1', 'different:prodi1_id'],
        ];
    }

    /**
     * @return Collection<int, PmbGelombang>
     */
    public function getGelombangTerbukaProperty(): Collection
    {
        return PmbGelombang::query()
            ->where('aktif', true)
            ->orderBy('tgl_buka')
            ->get()
            ->filter(fn (PmbGelombang $gelombang): bool => $gelombang->isBuka())
            ->values();
    }

    public function submit(): void
    {
        $this->validate();

        $gelombang = PmbGelombang::findOrFail($this->gelombang_id);

        if (! $gelombang->isBuka()) {
            $this->addError('gelombang_id', 'Gelombang yang dipilih sudah tutup.');

            return;
        }

        $pendaftar = PmbPendaftar::query()->create([
            'no_daftar' => $this->generateNoDaftar(),
            'nama' => trim($this->nama),
            'email' => trim($this->email),
            'hp' => trim($this->hp),
            'sekolah_asal' => trim($this->sekolah_asal),
            'prodi1_id' => $this->prodi1_id,
            'prodi2_id' => $this->prodi2_id,
            'gelombang_id' => $gelombang->id,
            'status' => PmbPendaftar::STATUS_DIAJUKAN,
        ]);

        $this->noDaftarBerhasil = $pendaftar->no_daftar;
        $this->reset(['nama', 'email', 'hp', 'sekolah_asal', 'gelombang_id', 'prodi1_id', 'prodi2_id']);
    }

    private function generateNoDaftar(): string
    {
        do {
            $noDaftar = 'PMB-'.date('Y').'-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (PmbPendaftar::query()->where('no_daftar', $noDaftar)->exists());

        return $noDaftar;
    }

    public function render(): View
    {
        return view('livewire.pmb.daftar-publik', [
            'gelombangs' => $this->gelombangTerbuka,
        ]);
    }
}
