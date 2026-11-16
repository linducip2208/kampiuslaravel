<?php

namespace App\Livewire\Klinik;

use App\Models\AuditLog;
use App\Models\KlinikKunjungan;
use App\Models\KlinikResep;
use App\Models\Mahasiswa;
use App\Services\AuditBacaService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Ide bank: kunjungan + resep klinik. Gate ganda: middleware
 * permission:klinik.manage di rute + can() di tiap aksi (Livewire
 * update-request melewati middleware rute).
 */
class Kunjungan extends Component
{
    use WithPagination;

    public string $nim = '';

    public string $keluhan = '';

    public string $diagnosa = '';

    public string $tindakan = '';

    public ?int $lihatId = null;

    public string $obat = '';

    public string $dosis = '';

    public string $jumlah = '';

    public string $message = '';

    private function authorizeKlinik(): void
    {
        abort_unless(Auth::user()?->can('klinik.manage'), 403, 'Khusus petugas klinik.');
    }

    public function simpan(): void
    {
        $this->authorizeKlinik();

        $this->validate([
            'nim' => 'required|string',
            'keluhan' => 'required|string|max:2000',
            'diagnosa' => 'nullable|string|max:2000',
            'tindakan' => 'nullable|string|max:2000',
        ]);

        $mhs = Mahasiswa::where('nim', trim($this->nim))->first();

        if (! $mhs) {
            $this->addError('nim', 'NIM tidak ditemukan.');

            return;
        }

        $kunjungan = KlinikKunjungan::create([
            'mahasiswa_id' => $mhs->id,
            'tanggal' => today()->toDateString(),
            'keluhan' => $this->keluhan,
            'diagnosa' => $this->diagnosa ?: null,
            'tindakan' => $this->tindakan ?: null,
            'pemeriksa_id' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'klinik.kunjungan',
            'model' => KlinikKunjungan::class, 'model_id' => $kunjungan->id,
            'created_at' => now(),
        ]);

        $this->reset(['nim', 'keluhan', 'diagnosa', 'tindakan']);
        $this->message = "Kunjungan #{$kunjungan->id} tersimpan.";
    }

    /** Buka detail rekam medis + catat audit baca (wajib per akses). */
    public function lihat(int $id): void
    {
        $this->authorizeKlinik();

        $this->lihatId = $id;
        AuditBacaService::catat(Auth::id(), 'klinik_kunjungan', $id, request()->ip());
    }

    public function tutup(): void
    {
        $this->lihatId = null;
        $this->reset(['obat', 'dosis', 'jumlah']);
    }

    public function simpanResep(): void
    {
        $this->authorizeKlinik();
        abort_unless($this->lihatId, 422);

        $this->validate([
            'obat' => 'required|string|max:150',
            'dosis' => 'nullable|string|max:100',
            'jumlah' => 'nullable|string|max:50',
        ]);

        KlinikResep::create([
            'kunjungan_id' => $this->lihatId,
            'obat' => $this->obat,
            'dosis' => $this->dosis ?: null,
            'jumlah' => $this->jumlah ?: null,
        ]);

        $this->reset(['obat', 'dosis', 'jumlah']);
        $this->message = 'Resep ditambahkan.';
    }

    public function render(): View
    {
        $this->authorizeKlinik();

        return view('livewire.klinik.kunjungan', [
            'antrean' => KlinikKunjungan::with(['mahasiswa.user'])->latest('id')->paginate(10),
            'detail' => $this->lihatId
                ? KlinikKunjungan::with(['mahasiswa.user', 'resep'])->find($this->lihatId)
                : null,
        ]);
    }
}
