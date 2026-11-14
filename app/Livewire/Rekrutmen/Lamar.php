<?php

namespace App\Livewire\Rekrutmen;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\LamaranKerja;
use App\Models\LowonganKerja;
use Livewire\Component;

/**
 * M-039: lamar lowongan — publik tanpa login (tanpa RequiresRoles:
 * tidak ada data sensitif, anti-spam via throttle rute + captcha kelak).
 * Idempoten per (lowongan, email).
 */
class Lamar extends Component
{
    public ?int $lowongan_id = null;

    public string $nama = '';

    public string $email = '';

    public string $hp = '';

    public string $message = '';

    public function kirim(?int $lowonganId = null): void
    {
        if ($lowonganId !== null) {
            $this->lowongan_id = $lowonganId;
        }

        $this->validate([
            'lowongan_id' => 'required|exists:lowongan_kerja,id',
            'nama' => 'required|string|min:3|max:150',
            'email' => 'required|email:rfc|max:150',
            'hp' => 'required|string|min:9|max:30',
        ]);

        $lowongan = LowonganKerja::findOrFail($this->lowongan_id);

        if (! $lowongan->masihBuka()) {
            $this->addError('lowongan_id', 'Lowongan sudah ditutup.');

            return;
        }

        LamaranKerja::firstOrCreate(
            ['lowongan_id' => $lowongan->id, 'email' => trim($this->email)],
            ['nama' => trim($this->nama), 'hp' => trim($this->hp), 'status' => 'masuk']
        );

        $this->reset(['nama', 'email', 'hp']);
        $this->message = 'Lamaran terkirim. Panitia menghubungi bila lolos seleksi berkas.';
    }

    public function render()
    {
        return view('livewire.rekrutmen.lamar', [
            'lowongans' => LowonganKerja::where('status', 'buka')->latest('id')->get(),
        ]);
    }
}
