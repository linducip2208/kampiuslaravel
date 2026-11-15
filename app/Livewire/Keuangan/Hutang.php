<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\HutangDagang;
use App\Models\Pemasok;
use App\Services\HutangService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-034: utang usaha — pemasok + hutang + pelunasan bertahap terkunci.
 */
class Hutang extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'kasir', 'akuntansi', 'superadmin', 'admin_prodi'];

    public string $pemasokNama = '';

    public ?int $editId = null;

    public ?int $pemasok_id = null;

    public string $nomor = '';

    public string $uraian = '';

    public int $nominal = 0;

    public string $jatuh_tempo = '';

    public int $bayarNominal = 0;

    public string $message = '';

    public function simpanPemasok(): void
    {
        $this->validate(['pemasokNama' => 'required|string|max:150']);
        Pemasok::create(['nama' => $this->pemasokNama]);
        $this->reset('pemasokNama');
        $this->message = 'Pemasok tersimpan.';
    }

    public function simpan(): void
    {
        $this->validate([
            'pemasok_id' => 'required|exists:pemasok,id',
            'nomor' => 'required|string|max:60',
            'uraian' => 'required|string|max:255',
            'nominal' => 'required|integer|min:1',
            'jatuh_tempo' => 'nullable|date',
        ]);

        HutangDagang::updateOrCreate(['id' => $this->editId ?? 0], [
            'pemasok_id' => $this->pemasok_id,
            'nomor' => $this->nomor,
            'uraian' => $this->uraian,
            'nominal' => $this->nominal,
            'jatuh_tempo' => $this->jatuh_tempo ?: null,
            'status' => HutangDagang::STATUS_BELUM,
        ]);

        $this->reset(['editId', 'pemasok_id', 'nomor', 'uraian', 'nominal', 'jatuh_tempo']);
        $this->message = 'Hutang tersimpan.';
    }

    public function bayar(int $id): void
    {
        $this->validate(['bayarNominal' => 'required|integer|min:1']);

        $hasil = HutangService::bayar($id, $this->bayarNominal, (int) Auth::id());

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'hutang.dibayar',
            'model' => HutangDagang::class, 'model_id' => $id,
            'diff_json' => ['dibayar' => $hasil['dibayar'], 'status' => $hasil['status']],
            'created_at' => now(),
        ]);

        $this->reset('bayarNominal');
        $this->message = "Terbayar Rp{$hasil['terbayar']} ({$hasil['status']}).";
    }

    public function render()
    {
        return view('livewire.keuangan.hutang', [
            'pemasoks' => Pemasok::orderBy('nama')->get(),
            'hutangs' => HutangDagang::with('pemasok')->latest('id')->paginate(10),
        ]);
    }
}
