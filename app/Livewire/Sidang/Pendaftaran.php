<?php

namespace App\Livewire\Sidang;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\Ruang;
use App\Models\Sidang as SidangModel;
use App\Models\SkripsiJudul;
use App\Services\JadwalService;
use App\Services\KalenderService;
use App\Services\KeuanganService;
use App\Services\SidangSyaratService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Pendaftaran extends Component
{
    use RequiresRoles;

    /** Otorisasi owner-scope dicek manual di mount(). */
    public array $allowedRoles = [];

    public ?int $judul_id = null;

    public string $jenis = 'proposal';

    public string $tanggal = '';

    public ?int $ruang_id = null;

    public string $message = '';

    public string $error = '';

    /** @var array<int, array{label: string, ok: bool, keras: bool}> */
    public array $syarat = [];

    /** @var array<int, array{jam: string, ruang: array<int, string>}> */
    public array $jadwalVisual = [];

    public function updatedJudulId(): void
    {
        $this->refreshSyarat();
    }

    public function updatedJenis(): void
    {
        $this->refreshSyarat();
    }

    public function refreshSyarat(): void
    {
        if (! $this->judul_id) {
            $this->syarat = [];

            return;
        }

        $judul = SkripsiJudul::find($this->judul_id);
        if (! $judul) {
            $this->syarat = [];

            return;
        }

        $this->syarat = SidangSyaratService::check($judul, $this->jenis)['items'];
    }

    public static function ruangBentrok(?int $ruangId, string $tanggal, ?int $kecualiId = null): bool
    {
        if (! $ruangId || $tanggal === '') {
            return false;
        }

        try {
            $waktu = new \DateTime($tanggal);
        } catch (\Throwable) {
            return false;
        }

        $batas = (clone $waktu)->modify('-2 hours');
        $atas = (clone $waktu)->modify('+2 hours');

        return SidangModel::where('ruang_id', $ruangId)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->whereBetween('tanggal', [$batas->format('Y-m-d H:i:s'), $atas->format('Y-m-d H:i:s')])
            ->exists();
    }

    public function save(): void
    {
        $this->validate([
            'judul_id' => 'required|exists:skripsi_judul,id',
            'jenis' => 'required|in:proposal,hasil,tutup',
            'tanggal' => 'required|date',
            'ruang_id' => 'nullable|exists:ruang,id',
        ]);

        $judul = SkripsiJudul::findOrFail($this->judul_id);

        if (KeuanganService::isBlockedForSidang($judul->mahasiswa_id)) {
            $this->error = 'Pendaftaran ditolak: mahasiswa memiliki tunggakan.';
            $this->message = '';

            return;
        }

        if (! KalenderService::isOpen('sidang')) {
            $this->error = KalenderService::closedMessage('sidang');
            $this->message = '';

            return;
        }

        $cek = SidangSyaratService::check($judul, $this->jenis);
        $this->syarat = $cek['items'];

        if (! $cek['keras_lolos']) {
            $this->error = 'Pendaftaran ditolak: pembimbing belum ditetapkan atau judul belum disetujui.';
            $this->message = '';

            return;
        }

        if (self::ruangBentrok($this->ruang_id, $this->tanggal)) {
            $this->error = 'Pendaftaran ditolak: ruang sudah dibooking sidang lain pada rentang ±2 jam.';
            $this->message = '';

            return;
        }

        // Kuliah di ruang yang sama diprioritaskan: cek jadwal mingguan + booking fasilitas.
        try {
            $waktu = new \DateTime($this->tanggal);
            $mulai = $waktu->format('H:i');
            $selesai = (clone $waktu)->modify('+2 hours')->format('H:i');
            $hari = JadwalService::hariDariTanggal($waktu->format('Y-m-d'));

            if ($this->ruang_id && JadwalService::ruangBentrok($this->ruang_id, $hari, $mulai, $selesai)) {
                $this->error = 'Pendaftaran ditolak: ruang dipakai jadwal kuliah pada jam tersebut.';
                $this->message = '';

                return;
            }
        } catch (\Throwable) {
            // Tanggal tak valid sudah ditolak validasi; abaikan cek tambahan.
        }

        $sidang = SidangModel::create([
            'judul_id' => $this->judul_id,
            'jenis' => $this->jenis,
            'tanggal' => $this->tanggal,
            'ruang_id' => $this->ruang_id,
            'status' => 'terdaftar',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'sidang.didaftarkan',
            'model' => SidangModel::class,
            'model_id' => $sidang->id,
            'created_at' => now(),
        ]);

        $this->reset(['judul_id', 'tanggal', 'ruang_id']);
        $this->error = '';
        $peringatan = implode(' ', $cek['warnings']);
        $this->message = trim('Sidang berhasil didaftarkan. '.$peringatan);
        $this->syarat = [];
    }

    public function render()
    {
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');
        $juduls = SkripsiJudul::when($mahasiswaId, fn ($q) => $q->where('mahasiswa_id', $mahasiswaId))
            ->whereIn('status', ['disetujui', 'sidang'])
            ->latest()->get();

        $ruangs = Ruang::all();

        // Grid visual booking 7 hari ke depan: slot jam 08–16 per ruang.
        $visual = [];
        $sidangMingguan = SidangModel::with('ruang')
            ->whereBetween('tanggal', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->get()
            ->groupBy(fn ($s) => substr((string) $s->tanggal, 0, 13));

        foreach (range(0, 6) as $hari) {
            $tanggal = now()->addDays($hari)->format('Y-m-d');
            foreach ([8, 10, 13, 15] as $jam) {
                $kunci = $tanggal.' '.str_pad((string) $jam, 2, '0', STR_PAD_LEFT);
                $baris = ['jam' => $kunci.':00', 'ruang' => []];
                foreach ($ruangs as $ruang) {
                    $terisi = $sidangMingguan->get($kunci)?->firstWhere('ruang_id', $ruang->id);
                    $baris['ruang'][$ruang->id] = $terisi ? ('Terisi ('.$terisi->jenis.')') : 'Kosong';
                }
                $visual[] = $baris;
            }
        }
        $this->jadwalVisual = $visual;

        return view('livewire.sidang.pendaftaran', [
            'juduls' => $juduls,
            'ruangs' => $ruangs,
            'sidangs' => SidangModel::with(['judul', 'ruang'])->latest()->paginate(10),
        ]);
    }
}
