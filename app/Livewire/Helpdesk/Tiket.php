<?php

namespace App\Livewire\Helpdesk;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Tiket as TiketModel;
use App\Models\TiketBalasan;
use App\Services\HelpdeskSlaService;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Tiket extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public string $subjek = '';

    public string $isi = '';

    public string $kategori = 'umum';

    public string $prioritas = 'normal';

    public ?int $bukaId = null;

    public string $balasan = '';

    public string $message = '';

    private function isStaff(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi', 'keuangan', 'pimpinan', 'dosen']);
    }

    public function buat(): void
    {
        $this->validate([
            'subjek' => 'required|string|max:150',
            'isi' => 'required|string|max:2000',
            'kategori' => 'required|string|max:50',
            'prioritas' => 'required|in:rendah,normal,tinggi,kritis',
        ]);

        $tiket = TiketModel::create([
            'user_id' => Auth::id(),
            'subjek' => $this->subjek,
            'isi' => $this->isi,
            'kategori' => $this->kategori,
            'prioritas' => $this->prioritas,
            'status' => 'terbuka',
        ]);

        HelpdeskSlaService::apply($tiket);

        $this->reset(['subjek', 'isi', 'kategori', 'prioritas']);
        $this->prioritas = 'normal';
        $this->kategori = 'umum';
        $this->message = 'Tiket terkirim. SLA dihitung otomatis.';
    }

    public function buka(int $id): void
    {
        $tiket = TiketModel::findOrFail($id);

        abort_unless($this->isStaff() || $tiket->user_id === Auth::id(), 403);

        $this->bukaId = $id;
    }

    public function balas(NotifikasiService $notif): void
    {
        $this->validate(['balasan' => 'required|string|max:2000', 'bukaId' => 'required|exists:tiket,id']);

        $tiket = TiketModel::findOrFail($this->bukaId);

        abort_unless($this->isStaff() || $tiket->user_id === Auth::id(), 403);

        TiketBalasan::create(['tiket_id' => $tiket->id, 'user_id' => Auth::id(), 'isi' => $this->balasan]);

        if ($tiket->user_id !== Auth::id()) {
            $tiket->update(['status' => 'diproses']);
            $notif->send($tiket->user, 'Balasan tiket: '.$tiket->subjek, $this->balasan);
        }

        $this->reset('balasan');
        $this->message = 'Balasan terkirim.';
    }

    public function tutup(int $id): void
    {
        $tiket = TiketModel::findOrFail($id);

        abort_unless($this->isStaff() || $tiket->user_id === Auth::id(), 403);
        $tiket->update(['status' => 'selesai']);
        $this->message = 'Tiket ditutup.';
    }

    public function eskalasi(int $id): void
    {
        abort_unless($this->isStaff(), 403);

        $tiket = TiketModel::findOrFail($id);

        if (HelpdeskSlaService::cekEskalasi($tiket)) {
            $this->message = 'Tiket dieskalasi ke atasan.';
        } else {
            $tiket->forceFill(['dieskalasi' => true])->save();
            $this->message = 'Tiket ditandai eskalasi manual.';
        }
    }

    public function render()
    {
        $query = TiketModel::with(['user', 'balasan.user'])->latest('id');

        if (! $this->isStaff()) {
            $query->where('user_id', Auth::id());
        }

        $tikets = $query->paginate(10);

        foreach ($tikets as $t) {
            HelpdeskSlaService::cekEskalasi($t);
        }

        return view('livewire.helpdesk.tiket', [
            'tikets' => $tikets,
            'detail' => $this->bukaId ? TiketModel::with(['user', 'balasan.user'])->find($this->bukaId) : null,
            'isStaff' => $this->isStaff(),
        ]);
    }
}
