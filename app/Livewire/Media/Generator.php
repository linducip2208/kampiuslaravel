<?php

namespace App\Livewire\Media;

use App\Jobs\GenerateMediaJob;
use App\Livewire\Concerns\RequiresRoles;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Generator extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'];

    public string $prompt = '';

    public string $negative = '';

    public int $steps = 20;

    public int $width = 832;

    public int $height = 1216;

    public ?int $pengumuman_id = null;

    public string $message = '';

    public function queue(): void
    {
        $this->validate([
            'prompt' => 'required|string|min:5|max:1000',
            'negative' => 'nullable|string|max:500',
            'steps' => 'required|integer|min:1|max:100',
            'width' => 'required|integer|min:256|max:2048',
            'height' => 'required|integer|min:256|max:2048',
            'pengumuman_id' => 'nullable|exists:pengumuman,id',
        ]);

        GenerateMediaJob::dispatch($this->prompt, [
            'negative' => $this->negative ?: null,
            'steps' => $this->steps,
            'width' => $this->width,
            'height' => $this->height,
        ], $this->pengumuman_id);

        $this->message = 'Masuk antrean generate. Jalankan php artisan queue:work, hasil otomatis jadi cover bila pengumuman dipilih.';
    }

    public function render()
    {
        $files = collect(Storage::disk((string) config('comfyui.disk', 'public'))->files(trim((string) config('comfyui.prefix', 'generated'), '/')))
            ->sortDesc()
            ->take(12)
            ->values();

        return view('livewire.media.generator', [
            'files' => $files,
            'pengumuman' => Pengumuman::orderByDesc('id')->take(20)->get(['id', 'judul']),
        ]);
    }
}
