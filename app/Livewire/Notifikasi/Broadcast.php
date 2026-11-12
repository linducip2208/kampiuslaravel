<?php

namespace App\Livewire\Notifikasi;

use App\Livewire\Concerns\RequiresRoles;
use App\Services\NotifikasiService;
use Livewire\Component;

class Broadcast extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'];

    public string $role = 'mahasiswa';

    public string $judul = '';

    public string $isi = '';

    public string $message = '';

    public function send(NotifikasiService $notif): void
    {
        $this->validate([
            'role' => 'required|in:superadmin,admin_prodi,keuangan,dosen,mahasiswa,pimpinan',
            'judul' => 'required|string|max:150',
            'isi' => 'required|string|max:1000',
        ]);

        $count = $notif->broadcast($this->role, $this->judul, $this->isi);

        $this->reset(['judul', 'isi']);
        $this->message = "Terkirim ke {$count} pengguna (role {$this->role}).";
    }

    public function render()
    {
        return view('livewire.notifikasi.broadcast');
    }
}
