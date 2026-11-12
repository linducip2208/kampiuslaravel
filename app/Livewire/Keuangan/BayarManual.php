<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Mahasiswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class BayarManual extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    use WithFileUploads;

    public ?int $tagihan_id = null;

    public $bukti;

    public string $message = '';

    public function mount(): void
    {
        abort_unless(Auth::check(), 401);
    }

    public function simpan(): void
    {
        $this->validate([
            'tagihan_id' => 'required|exists:tagihan,id',
            'bukti' => 'required|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');
        $tagihan = Tagihan::findOrFail($this->tagihan_id);

        abort_unless(
            $tagihan->mahasiswa_id === $mahasiswaId
                || Auth::user()->hasAnyRole(['keuangan', 'superadmin', 'admin_prodi']),
            403
        );
        abort_if($tagihan->status === 'lunas', 422, 'Tagihan sudah lunas.');

        $path = $this->bukti->store('bukti-bayar', 'public');

        Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'metode' => 'manual',
            'nominal' => $tagihan->sisa(),
            'bukti_path' => $path,
            'status' => 'pending',
        ]);

        $this->reset(['tagihan_id', 'bukti']);
        $this->message = 'Bukti bayar terkirim. Menunggu verifikasi petugas keuangan.';
    }

    public function render()
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');

        return view('livewire.keuangan.bayar-manual', [
            'tagihan' => $mahasiswaId
                ? Tagihan::where('mahasiswa_id', $mahasiswaId)->where('status', '!=', 'lunas')->latest()->get()
                : collect(),
            'riwayat' => $mahasiswaId
                ? Pembayaran::whereIn('tagihan_id', Tagihan::where('mahasiswa_id', $mahasiswaId)->pluck('id'))
                    ->latest()->take(10)->get()
                : collect(),
        ]);
    }
}
