<?php

namespace App\Livewire\Pmb;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\PmbGelombang;
use App\Models\PmbPendaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulir pendaftaran PMB publik (tanpa auth).
 */
class DaftarPublik extends Component
{
    use RequiresRoles;
    use WithFileUploads;

    public array $allowedRoles = [];

    public string $nama = '';

    public string $email = '';

    public string $hp = '';

    public string $sekolah_asal = '';

    public ?int $gelombang_id = null;

    public ?int $prodi1_id = null;

    public ?int $prodi2_id = null;

    public ?string $noDaftarBerhasil = null;

    public $berkas_ijazah;

    public $berkas_kk;

    public $berkas_foto;

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
            'berkas_ijazah' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'berkas_kk' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'berkas_foto' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],
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

        $berkas = [];
        foreach (['berkas_ijazah' => 'ijazah', 'berkas_kk' => 'kk', 'berkas_foto' => 'foto'] as $prop => $key) {
            if ($this->{$prop}) {
                $berkas[$key] = $this->{$prop}->store("pmb/{$pendaftar->no_daftar}", 'public');
            }
        }

        if ($berkas !== []) {
            $pendaftar->update(['berkas_json' => $berkas]);
        }

        $this->noDaftarBerhasil = $pendaftar->no_daftar;
        $this->reset(['nama', 'email', 'hp', 'sekolah_asal', 'gelombang_id', 'prodi1_id', 'prodi2_id', 'berkas_ijazah', 'berkas_kk', 'berkas_foto']);
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
