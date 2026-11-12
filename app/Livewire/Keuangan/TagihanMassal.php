<?php

namespace App\Livewire\Keuangan;

use App\Models\AuditLog;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use App\Services\KeuanganService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TagihanMassal extends Component
{
    public ?int $tahun_id = null;

    public string $jenis = 'UKT';

    public int $nominal = 0;

    public ?int $prodi_id = null;

    public string $angkatan = '';

    public string $message = '';

    public function generate(): void
    {
        $this->validate([
            'tahun_id' => 'required|exists:tahun_akademik,id',
            'jenis' => 'required|in:UKT,SPP,PMB,lain',
            'nominal' => 'required|integer|min:1',
            'prodi_id' => 'nullable|exists:prodi,id',
            'angkatan' => 'nullable|string|max:4',
        ]);

        $result = KeuanganService::generateMassal(
            $this->tahun_id,
            $this->jenis,
            $this->nominal,
            $this->prodi_id,
            $this->angkatan ?: null
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'tagihan.generate_massal',
            'model' => null,
            'model_id' => null,
            'diff_json' => $result,
            'created_at' => now(),
        ]);

        $this->message = "Berhasil: {$result['created']} dibuat, {$result['skipped']} dilewati (sudah ada).";
    }

    public function render()
    {
        return view('livewire.keuangan.tagihan-massal', [
            'tahuns' => TahunAkademik::orderByDesc('id')->get(),
            'prodis' => Prodi::orderBy('nama')->get(),
        ]);
    }
}
