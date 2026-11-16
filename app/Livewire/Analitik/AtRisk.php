<?php

namespace App\Livewire\Analitik;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Notifikasi;
use App\Services\Ai\AtRiskService;
use Livewire\Component;

class AtRisk extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'pimpinan', 'dosen'];

    /** @var list<array{mahasiswa_id: int, nim: string, nama: string, skor: float, level: string}> */
    public array $daftar = [];

    public string $pesan = '';

    public function mount(AtRiskService $svc): void
    {
        $this->daftar = $svc->daftarBerisiko(20);
    }

    /** Kirim notifikasi inbox ke Dosen PA bila mahasiswa berisiko tinggi. */
    public function notifikasiPa(int $mahasiswaId): void
    {
        try {
            $mhs = Mahasiswa::findOrFail($mahasiswaId);
            $paUserId = $mhs->dosen_pa_id
                ? Dosen::where('id', $mhs->dosen_pa_id)->value('user_id')
                : null;

            if (! $paUserId) {
                $this->pesan = "Mahasiswa {$mhs->nim} belum punya Dosen PA.";

                return;
            }

            $namaPa = (string) ($mhs->user->name ?? $mhs->nim);

            Notifikasi::create([
                'user_id' => $paUserId,
                'judul' => 'Mahasiswa bimbingan berisiko tinggi',
                'isi' => "Prediktor at-risk: {$namaPa} ({$mhs->nim}) level tinggi. Segera lakukan konseling akademik.",
            ]);

            $this->pesan = "Notifikasi terkirim ke Dosen PA ({$mhs->nim}).";
        } catch (\Throwable) {
            $this->pesan = 'Gagal mengirim notifikasi.';
        }
    }

    public function render()
    {
        return view('livewire.analitik.at-risk');
    }
}
