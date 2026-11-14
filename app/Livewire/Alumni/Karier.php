<?php

namespace App\Livewire\Alumni;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Alumni;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * M-055: alumni memutakhirkan kariernya sendiri (pekerjaan/instansi/kontak).
 */
class Karier extends Component
{
    use RequiresRoles;

    public array $allowedRoles = [];

    public string $pekerjaan = '';

    public string $instansi = '';

    public string $hp = '';

    public bool $bersedia_tracer = true;

    public string $message = '';

    public function mount(): void
    {
        $alumni = $this->alumni();

        if ($alumni) {
            $this->pekerjaan = (string) ($alumni->pekerjaan ?? '');
            $this->instansi = (string) ($alumni->instansi ?? '');
            $this->hp = (string) ($alumni->hp ?? '');
            $this->bersedia_tracer = (bool) $alumni->bersedia_tracer;
        }
    }

    protected function alumni(): ?Alumni
    {
        $mhsId = (int) Mahasiswa::where('user_id', Auth::id())->value('id');

        return $mhsId > 0 ? Alumni::where('mahasiswa_id', $mhsId)->first() : null;
    }

    public function simpan(): void
    {
        $alumni = $this->alumni();
        abort_unless($alumni, 403, 'Data alumni tidak ditemukan.');

        $this->validate([
            'pekerjaan' => 'nullable|string|max:150',
            'instansi' => 'nullable|string|max:150',
            'hp' => 'nullable|string|max:30',
            'bersedia_tracer' => 'boolean',
        ]);

        $alumni->update([
            'pekerjaan' => $this->pekerjaan ?: null,
            'instansi' => $this->instansi ?: null,
            'hp' => $this->hp ?: null,
            'bersedia_tracer' => $this->bersedia_tracer,
        ]);

        $this->message = 'Data karier tersimpan. Terima kasih.';
    }

    public function render()
    {
        return view('livewire.alumni.karier', ['alumni' => $this->alumni()]);
    }
}
