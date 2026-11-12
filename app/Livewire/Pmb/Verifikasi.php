<?php

namespace App\Livewire\Pmb;

use App\Models\PmbPendaftar;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Verifikasi berkas pendaftar PMB oleh admin.
 */
class Verifikasi extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public ?int $selectedId = null;

    public string $catatan = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function ubahStatus(int $id, string $status): void
    {
        if (! in_array($status, PmbPendaftar::STATUSES, true)) {
            return;
        }

        $this->validate([
            'catatan' => [
                Rule::requiredIf(in_array($status, [PmbPendaftar::STATUS_DITOLAK, PmbPendaftar::STATUS_TIDAK], true)),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $pendaftar = PmbPendaftar::query()->findOrFail($id);
        $pendaftar->update([
            'status' => $status,
            'catatan_verifikasi' => $this->catatan ?: $pendaftar->catatan_verifikasi,
        ]);

        $this->reset(['selectedId', 'catatan']);
        session()->flash('info', "Status {$pendaftar->no_daftar} diubah menjadi {$status}.");
    }

    public function pilih(int $id): void
    {
        $pendaftar = PmbPendaftar::query()->findOrFail($id);
        $this->selectedId = $pendaftar->id;
        $this->catatan = (string) ($pendaftar->catatan_verifikasi ?? '');
    }

    /**
     * Daftar ulang: pendaftar lulus -> buatkan akun user + tautkan.
     */
    public function daftarUlang(int $id): void
    {
        $pendaftar = PmbPendaftar::query()->findOrFail($id);

        if ($pendaftar->status !== PmbPendaftar::STATUS_LULUS) {
            $this->addError('selectedId', 'Hanya pendaftar berstatus lulus yang bisa daftar ulang.');

            return;
        }

        if ($pendaftar->user_id) {
            $this->addError('selectedId', 'Pendaftar ini sudah memiliki akun.');

            return;
        }

        $user = DB::transaction(function () use ($pendaftar) {
            $user = User::query()->firstOrCreate(
                ['email' => $pendaftar->email],
                [
                    'name' => $pendaftar->nama,
                    'password' => str()->random(12),
                ]
            );

            $pendaftar->update(['user_id' => $user->id]);

            return $user;
        });

        $this->reset(['selectedId', 'catatan']);
        session()->flash('info', "Akun {$user->email} berhasil dibuat untuk {$pendaftar->no_daftar}.");
    }

    public function render(): View
    {
        $pendaftars = PmbPendaftar::query()
            ->with('gelombang')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('no_daftar', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        return view('livewire.pmb.verifikasi', [
            'pendaftars' => $pendaftars,
            'statuses' => PmbPendaftar::STATUSES,
        ]);
    }
}
