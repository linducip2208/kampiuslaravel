<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMediaJob;
use App\Models\Pengumuman;
use App\Services\ComfyUIService;
use Illuminate\Console\Command;

class ComfyGenerateCommand extends Command
{
    protected $signature = 'comfyui:generate {prompt : Prompt teks gambar}'
        .' {--negative= : Negative prompt}'
        .' {--steps= : Jumlah steps}'
        .' {--width= : Lebar px}'
        .' {--height= : Tinggi px}'
        .' {--seed= : Seed (0 = acak)}'
        .' {--pengumuman= : ID pengumuman untuk dijadikan cover}'
        .' {--queue : Masukkan ke antrean, jangan tunggu hasil}';

    protected $description = 'Generate gambar via ComfyUI (default model WAN 2.1)';

    public function handle(ComfyUIService $comfy): int
    {
        $options = array_filter([
            'negative' => $this->option('negative'),
            'steps' => $this->option('steps') ? (int) $this->option('steps') : null,
            'width' => $this->option('width') ? (int) $this->option('width') : null,
            'height' => $this->option('height') ? (int) $this->option('height') : null,
            'seed' => $this->option('seed') !== null ? (int) $this->option('seed') : null,
        ], fn ($v) => $v !== null);

        $pengumumanId = $this->option('pengumuman') ? (int) $this->option('pengumuman') : null;

        if ($this->option('queue')) {
            GenerateMediaJob::dispatch($this->argument('prompt'), $options, $pengumumanId);
            $this->info('Masuk antrean. Jalankan: php artisan queue:work');

            return self::SUCCESS;
        }

        $this->info('Mengirim ke ComfyUI: '.$comfy->baseUrl().' ...');
        $path = $comfy->generateImage($this->argument('prompt'), $options);

        if ($pengumumanId) {
            Pengumuman::whereKey($pengumumanId)->update(['cover_path' => $path]);
            $this->info('Cover pengumuman #'.$pengumumanId.' diperbarui.');
        }

        $this->info('Tersimpan: storage/app/public/'.$path);

        return self::SUCCESS;
    }
}
