<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Coa;
use App\Models\JurnalUmum;
use App\Models\TahunAkademik;
use App\Services\GlService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-052: GL — CoA + jurnal umum berimbang + buku besar + neraca saldo.
 */
class Gl extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'kasir', 'akuntansi', 'superadmin', 'admin_prodi'];

    public ?int $tahunId = null;

    public string $coa_kode = '';

    public string $coa_nama = '';

    public string $coa_tipe = 'aset';

    public string $tanggal = '';

    public string $keterangan = '';

    public string $referensi = '';

    /** @var list<array{coa_id: int, debet: int, kredit: int}> */
    public array $baris = [];

    public ?int $bukuCoaId = null;

    public string $message = '';

    public function tambahBaris(): void
    {
        $this->baris[] = ['coa_id' => 0, 'debet' => 0, 'kredit' => 0];
    }

    public function hapusBaris(int $i): void
    {
        unset($this->baris[$i]);
        $this->baris = array_values($this->baris);
    }

    public function simpanCoa(): void
    {
        $this->validate([
            'coa_kode' => 'required|string|max:20|unique:coa,kode',
            'coa_nama' => 'required|string|max:150',
            'coa_tipe' => 'required|in:'.implode(',', Coa::TIPE),
        ]);

        $normal = in_array($this->coa_tipe, ['kewajiban', 'ekuitas', 'pendapatan'], true) ? 'kredit' : 'debet';
        Coa::create(['kode' => $this->coa_kode, 'nama' => $this->coa_nama, 'tipe' => $this->coa_tipe, 'normal' => $normal]);

        $this->reset(['coa_kode', 'coa_nama']);
        $this->message = 'Akun COA tersimpan.';
    }

    public function posting(): void
    {
        abort_unless($this->tahunId, 422);

        $this->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:500',
            'referensi' => 'nullable|string|max:100',
            'baris' => 'required|array|min:2',
            'baris.*.coa_id' => 'required|exists:coa,id',
            'baris.*.debet' => 'required|integer|min:0',
            'baris.*.kredit' => 'required|integer|min:0',
        ]);

        try {
            GlService::posting(
                $this->tahunId, $this->tanggal, $this->keterangan,
                array_map(fn ($b) => ['coa_id' => (int) $b['coa_id'], 'debet' => (int) $b['debet'], 'kredit' => (int) $b['kredit']], $this->baris),
                $this->referensi ?: null,
                auth()->id()
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $this->addError('baris', $e->getMessage());

            return;
        }

        $this->reset(['tanggal', 'keterangan', 'referensi', 'baris']);
        $this->message = 'Jurnal diposting (seimbang).';
    }

    public function render()
    {
        $this->tahunId ??= TahunAkademik::aktif()->value('id') ?? TahunAkademik::orderByDesc('id')->value('id');

        return view('livewire.keuangan.gl', [
            'coas' => Coa::where('aktif', true)->orderBy('kode')->get(),
            'jurnals' => $this->tahunId
                ? JurnalUmum::with('item.coa')->where('tahun_akademik_id', $this->tahunId)->latest('id')->paginate(10)
                : null,
            'buku' => $this->bukuCoaId && $this->tahunId ? GlService::bukuBesar($this->bukuCoaId, $this->tahunId) : null,
            'neraca' => $this->tahunId ? GlService::neracaSaldo($this->tahunId) : null,
            'labarugi' => $this->tahunId ? GlService::labaRugi($this->tahunId) : null,
            'tahuns' => TahunAkademik::orderByDesc('id')->get(),
        ]);
    }
}
