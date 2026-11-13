<?php

namespace App\Livewire\Jadwal;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Kelas;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Services\JadwalService;
use DomainException;
use Livewire\Component;

class Generate extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public ?int $tahun_akademik_id = null;

    public string $message = '';

    public string $error = '';

    /** @var list<string> */
    public array $gagal = [];

    public function generate(): void
    {
        $this->validate(['tahun_akademik_id' => 'required|exists:tahun_akademik,id']);

        try {
            $hasil = JadwalService::generate((int) $this->tahun_akademik_id);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
            $this->message = '';

            return;
        }

        $this->gagal = $hasil['gagal'];
        $this->error = '';
        $this->message = "Berhasil menjadwalkan {$hasil['dijadwalkan']} kelas."
            .($hasil['gagal'] !== [] ? ' '.count($hasil['gagal']).' kelas gagal.' : '');
    }

    public function render()
    {
        return view('livewire.jadwal.generate', [
            'tahuns' => TahunAkademik::orderByDesc('id')->get(['id', 'tahun', 'semester']),
            'jadwal' => Kelas::with(['matakuliah', 'dosen.user', 'ruang'])
                ->when($this->tahun_akademik_id, fn ($q) => $q->where('tahun_akademik_id', $this->tahun_akademik_id))
                ->orderBy('hari')->orderBy('jam_mulai')->paginate(15),
            'ruangCount' => Ruang::count(),
        ]);
    }
}
