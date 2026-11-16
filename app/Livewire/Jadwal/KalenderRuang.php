<?php

namespace App\Livewire\Jadwal;

use App\Models\Kelas;
use App\Models\PeminjamanRuang;
use App\Models\Ruang;
use App\Services\JadwalService;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Kalender mingguan pemakaian ruang: jadwal kuliah + booking disetujui/diajukan.
 * Baca-saja (pengajuan tetap via PinjamRuang).
 *
 * @property array<int, array{tanggal: string, label: string, hari: string}> $minggu
 */
class KalenderRuang extends Component
{
    /** Tanggal acuan minggu berjalan (Y-m-d); default Senin pekan ini. */
    public string $pekan = '';

    public ?int $ruangId = null;

    public function mount(?string $pekan = null): void
    {
        $awal = $pekan ? Carbon::parse($pekan) : Carbon::now('Asia/Jakarta');
        $this->pekan = $awal->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function geser(int $minggu): void
    {
        $this->pekan = Carbon::parse($this->pekan)->addWeeks($minggu)->toDateString();
    }

    /** @return array<int, array{tanggal: string, label: string, hari: string}> */
    public function getMingguProperty(): array
    {
        $senin = Carbon::parse($this->pekan);
        $out = [];

        for ($i = 0; $i < 7; $i++) {
            $tgl = (clone $senin)->addDays($i);
            $out[] = [
                'tanggal' => $tgl->toDateString(),
                'label' => $tgl->translatedFormat('D, d M'),
                'hari' => JadwalService::hariDariTanggal($tgl->toDateString()),
            ];
        }

        return $out;
    }

    public function render()
    {
        $ruangs = Ruang::orderBy('nama')->get();
        $ruangIds = $this->ruangId ? [$this->ruangId] : $ruangs->pluck('id')->all();

        $kuliah = Kelas::with('matakuliah')
            ->whereIn('ruang_id', $ruangIds)
            ->whereNotNull('hari')
            ->get()
            ->groupBy(fn ($k) => $k->ruang_id.'|'.$k->hari);

        $tanggalList = array_column($this->minggu, 'tanggal');
        $dari = min($tanggalList);
        $sampai = max($tanggalList);

        $booking = PeminjamanRuang::with('user')
            ->whereIn('ruang_id', $ruangIds)
            // whereDate, bukan whereIn mentah: SQLite menyimpan kolom
            // date sebagai 'Y-m-d H:i:s' sehingga perbandingan string gagal.
            ->whereDate('tanggal', '>=', $dari)
            ->whereDate('tanggal', '<=', $sampai)
            ->whereIn('status', [PeminjamanRuang::STATUS_DIAJUKAN, PeminjamanRuang::STATUS_DISETUJUI])
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy(fn ($b) => $b->ruang_id.'|'.($b->tanggal instanceof \DateTimeInterface ? $b->tanggal->format('Y-m-d') : (string) $b->tanggal));

        return view('livewire.jadwal.kalender-ruang', compact('ruangs', 'kuliah', 'booking'));
    }
}
