<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use App\Services\KeuanganService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TagihanMassal extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['keuangan', 'superadmin', 'admin_prodi'];

    public ?int $tahun_id = null;

    public string $jenis = 'UKT';

    public int $nominal = 0;

    public ?int $prodi_id = null;

    public string $angkatan = '';

    public string $jalur = '';

    public string $jatuh_tempo = '';

    public int $cicilan = 1;

    public string $message = '';

    public function generate(): void
    {
        $this->validate([
            'tahun_id' => 'required|exists:tahun_akademik,id',
            'jenis' => 'required|in:UKT,SPP,PMB,lain',
            'nominal' => 'required|integer|min:0',
            'prodi_id' => 'nullable|exists:prodi,id',
            'angkatan' => 'nullable|string|max:4',
            'jalur' => 'nullable|string|max:30',
            'jatuh_tempo' => 'nullable|date',
            'cicilan' => 'required|integer|min:1|max:12',
        ]);

        $result = KeuanganService::generateMassal(
            $this->tahun_id,
            $this->jenis,
            $this->nominal,
            $this->prodi_id,
            $this->angkatan ?: null,
            $this->jalur ?: null,
            $this->jatuh_tempo ?: null,
            $this->cicilan,
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

    public function terapkanDenda(): void
    {
        $this->validate(['tahun_id' => 'required|exists:tahun_akademik,id']);

        $result = KeuanganService::applyDendaOtomatis($this->tahun_id);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'tagihan.terapkan_denda',
            'model' => null,
            'model_id' => null,
            'diff_json' => $result,
            'created_at' => now(),
        ]);

        $this->message = "Denda diterapkan ke {$result['updated']} tagihan (total Rp ".number_format($result['total_denda'], 0, ',', '.').').';
    }

    public function render()
    {
        return view('livewire.keuangan.tagihan-massal', [
            'tahuns' => TahunAkademik::orderByDesc('id')->get(),
            'prodis' => Prodi::orderBy('nama')->get(),
            'tunggakan' => $this->tahun_id
                ? KeuanganService::tunggakanSummary($this->tahun_id)
                : KeuanganService::tunggakanSummary(),
        ]);
    }
}
