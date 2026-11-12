<?php

namespace App\Livewire\Helpdesk;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Tiket as TiketModel;
use App\Models\TiketBalasan;
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
        ]);

        TiketModel::create(['user_id' => Auth::id(), 'subjek' => $this->subjek, 'isi' => $this->isi, 'status' => 'terbuka']);

        $this->reset(['subjek', 'isi']);
        $this->message = 'Tiket terkirim. Pantau balasan di sini.';
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

    public function render()
    {
        $query = TiketModel::with(['user', 'balasan.user'])->latest('id');

        if (! $this->isStaff()) {
            $query->where('user_id', Auth::id());
        }

        return view('livewire.helpdesk.tiket', [
            'tikets' => $query->paginate(10),
            'detail' => $this->bukaId ? TiketModel::with(['user', 'balasan.user'])->find($this->bukaId) : null,
            'isStaff' => $this->isStaff(),
        ]);
    }
}
