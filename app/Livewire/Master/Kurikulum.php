<?php

namespace App\Livewire\Master;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Kurikulum as KurikulumModel;
use App\Models\KurikulumMk;
use App\Models\Matakuliah;
use App\Models\MkPrasyarat;
use App\Models\Prodi;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kelola versi kurikulum per prodi, pemetaan MK per semester,
 * relasi prasyarat/ekivalensi MK, dan graf prasyarat visual (SVG).
 *
 * Catatan: kolom legacy matakuliah.prasyarat_id TIDAK diubah logikanya;
 * saat prasyarat baru ditambahkan dan kolom legacy masih kosong,
 * kolom itu diisi agar cek KRS yang ada tetap mengenali relasi.
 */
class Kurikulum extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $prodiId = null;

    public ?int $kurikulumId = null;

    public ?int $editKurikulumId = null;

    /** @var array<string, mixed> */
    public array $formKurikulum = [];

    /** @var array<string, mixed> */
    public array $formMk = ['matakuliah_id' => null, 'semester' => 1, 'sifat' => 'wajib'];

    /** @var array<string, mixed> */
    public array $formRelasi = ['matakuliah_id' => null, 'prasyarat_id' => null, 'jenis' => 'prasyarat', 'keterangan' => null];

    public string $message = '';

    public function mount(?int $prodiId = null): void
    {
        $this->prodiId = $prodiId ?? Prodi::orderBy('nama')->value('id');
        $this->kurikulumId = KurikulumModel::query()
            ->when($this->prodiId, fn ($q) => $q->where('prodi_id', $this->prodiId))
            ->orderByDesc('tahun_berlaku')->value('id');
    }

    public function gantiProdi(?int $prodiId): void
    {
        $this->prodiId = $prodiId;
        $this->kurikulumId = KurikulumModel::query()
            ->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))
            ->orderByDesc('tahun_berlaku')->value('id');
        $this->resetPage();
    }

    public function pilihKurikulum(?int $id): void
    {
        $this->kurikulumId = $id;
        $this->resetPage();
    }

    public function editKurikulum(int $id): void
    {
        $row = KurikulumModel::findOrFail($id);
        $this->editKurikulumId = $row->id;
        $this->formKurikulum = $row->only(['prodi_id', 'kode', 'nama', 'tahun_berlaku', 'status', 'keterangan']);
    }

    public function simpanKurikulum(): void
    {
        $this->validate([
            'formKurikulum.prodi_id' => ['required', 'exists:prodi,id'],
            'formKurikulum.kode' => ['required', 'string', 'max:30'],
            'formKurikulum.nama' => ['required', 'string', 'max:150'],
            'formKurikulum.tahun_berlaku' => ['required', 'integer', 'min:2000', 'max:2100'],
            'formKurikulum.status' => ['required', 'in:aktif,nonaktif,draf'],
            'formKurikulum.keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        KurikulumModel::updateOrCreate(
            ['id' => $this->editKurikulumId ?? 0],
            $this->formKurikulum
        );

        $this->reset(['editKurikulumId', 'formKurikulum']);
        $this->message = 'Kurikulum tersimpan.';
    }

    public function aktifkanKurikulum(int $id): void
    {
        $row = KurikulumModel::findOrFail($id);
        KurikulumModel::query()->where('prodi_id', $row->prodi_id)->update(['status' => 'nonaktif']);
        $row->update(['status' => 'aktif']);
        $this->message = 'Kurikulum '.$row->kode.' diaktifkan.';
    }

    public function hapusKurikulum(int $id): void
    {
        KurikulumModel::findOrFail($id)->delete();
        if ($this->kurikulumId === $id) {
            $this->kurikulumId = null;
        }
        $this->message = 'Kurikulum dihapus.';
    }

    public function tambahMk(): void
    {
        $this->validate([
            'kurikulumId' => ['required', 'exists:kurikulum,id'],
            'formMk.matakuliah_id' => ['required', 'exists:matakuliah,id'],
            'formMk.semester' => ['required', 'integer', 'min:1', 'max:14'],
            'formMk.sifat' => ['required', 'in:wajib,pilihan'],
        ]);

        KurikulumMk::updateOrCreate(
            ['kurikulum_id' => $this->kurikulumId, 'matakuliah_id' => $this->formMk['matakuliah_id']],
            ['semester' => $this->formMk['semester'], 'sifat' => $this->formMk['sifat']]
        );

        $this->reset('formMk');
        $this->formMk = ['matakuliah_id' => null, 'semester' => 1, 'sifat' => 'wajib'];
        $this->message = 'Matakuliah ditambahkan ke kurikulum.';
    }

    public function hapusMk(int $id): void
    {
        KurikulumMk::findOrFail($id)->delete();
        $this->message = 'Matakuliah dilepas dari kurikulum.';
    }

    public function tambahRelasi(): void
    {
        $this->validate([
            'formRelasi.matakuliah_id' => ['required', 'exists:matakuliah,id'],
            'formRelasi.prasyarat_id' => ['required', 'exists:matakuliah,id', 'different:formRelasi.matakuliah_id'],
            'formRelasi.jenis' => ['required', 'in:prasyarat,ekivalensi'],
            'formRelasi.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $mkId = (int) $this->formRelasi['matakuliah_id'];
        $praId = (int) $this->formRelasi['prasyarat_id'];
        $jenis = (string) $this->formRelasi['jenis'];

        if ($jenis === MkPrasyarat::JENIS_PRASYARAT && $this->menimbulkanSiklus($mkId, $praId)) {
            $this->addError('formRelasi.prasyarat_id', 'Relasi ditolak: menimbulkan siklus prasyarat.');

            return;
        }

        MkPrasyarat::updateOrCreate(
            ['matakuliah_id' => $mkId, 'prasyarat_id' => $praId, 'jenis' => $jenis],
            ['keterangan' => $this->formRelasi['keterangan']]
        );

        // Jaga kompatibilitas cek KRS lama (matakuliah.prasyarat_id):
        // isi hanya bila masih kosong, tidak menimpa data yang ada.
        if ($jenis === MkPrasyarat::JENIS_PRASYARAT) {
            Matakuliah::query()->where('id', $mkId)->whereNull('prasyarat_id')->update(['prasyarat_id' => $praId]);
        }

        $this->reset('formRelasi');
        $this->formRelasi = ['matakuliah_id' => null, 'prasyarat_id' => null, 'jenis' => 'prasyarat', 'keterangan' => null];
        $this->message = 'Relasi '.($jenis === 'ekivalensi' ? 'ekivalensi' : 'prasyarat').' tersimpan.';
    }

    public function hapusRelasi(int $id): void
    {
        MkPrasyarat::findOrFail($id)->delete();
        $this->message = 'Relasi dihapus.';
    }

    /**
     * Deteksi siklus: apakah praId (transitif) sudah bergantung ke mkId?
     */
    private function menimbulkanSiklus(int $mkId, int $praId): bool
    {
        $kunjungi = [$praId];
        $sudah = [];

        while ($kunjungi !== []) {
            $saatIni = array_pop($kunjungi);
            if ($saatIni === $mkId) {
                return true;
            }
            if (in_array($saatIni, $sudah, true)) {
                continue;
            }
            $sudah[] = $saatIni;
            $lanjut = MkPrasyarat::query()
                ->where('matakuliah_id', $saatIni)
                ->where('jenis', MkPrasyarat::JENIS_PRASYARAT)
                ->pluck('prasyarat_id')->all();
            foreach ($lanjut as $berikutnya) {
                $kunjungi[] = (int) $berikutnya;
            }
        }

        return false;
    }

    /**
     * @return array{nodes: Collection<int, object>, prasyarat: Collection<int, MkPrasyarat>, ekivalensi: Collection<int, MkPrasyarat>}
     */
    public function getGrafProperty(): array
    {
        $nodes = collect();
        $prasyarat = collect();
        $ekivalensi = collect();

        if ($this->kurikulumId) {
            $mkIds = KurikulumMk::query()->where('kurikulum_id', $this->kurikulumId)->pluck('matakuliah_id')->all();
            $nodes = Matakuliah::query()->whereIn('id', $mkIds)->orderBy('semester')->orderBy('kode')->get();
            if ($mkIds !== []) {
                $prasyarat = MkPrasyarat::query()
                    ->with(['matakuliah:id,kode,nama', 'prasyarat:id,kode,nama'])
                    ->whereIn('matakuliah_id', $mkIds)
                    ->where('jenis', MkPrasyarat::JENIS_PRASYARAT)->get();
                $ekivalensi = MkPrasyarat::query()
                    ->with(['matakuliah:id,kode,nama', 'prasyarat:id,kode,nama'])
                    ->whereIn('matakuliah_id', $mkIds)
                    ->where('jenis', MkPrasyarat::JENIS_EKIVALENSI)->get();
            }
        }

        return ['nodes' => $nodes, 'prasyarat' => $prasyarat, 'ekivalensi' => $ekivalensi];
    }

    public function render(): View
    {
        $kurikulums = KurikulumModel::query()->with('prodi:id,nama')
            ->when($this->prodiId, fn ($q) => $q->where('prodi_id', $this->prodiId))
            ->orderByDesc('tahun_berlaku')->paginate(10);

        $items = $this->kurikulumId
            ? KurikulumMk::query()->with('matakuliah:id,kode,nama,sks')
                ->where('kurikulum_id', $this->kurikulumId)
                ->orderBy('semester')->paginate(12, ['*'], 'mkPage')
            : null;

        $relasi = MkPrasyarat::query()->with(['matakuliah:id,kode,nama', 'prasyarat:id,kode,nama'])
            ->orderByDesc('id')->paginate(10, ['*'], 'relPage');

        return view('livewire.master.kurikulum', [
            'kurikulums' => $kurikulums,
            'items' => $items,
            'relasi' => $relasi,
            'graf' => $this->graf,
            'prodis' => Prodi::orderBy('nama')->get(['id', 'nama']),
            'semuaMk' => Matakuliah::orderBy('kode')->get(['id', 'kode', 'nama']),
            'kurikulumAktif' => $this->kurikulumId ? KurikulumModel::find($this->kurikulumId) : null,
        ]);
    }
}
