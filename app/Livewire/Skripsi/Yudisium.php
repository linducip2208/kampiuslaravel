<?php

namespace App\Livewire\Skripsi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\Sidang;
use App\Models\SkripsiJudul;
use App\Models\Surat;
use App\Models\WisudaBatch;
use App\Models\WisudaPeserta;
use App\Services\AkademikService;
use App\Services\KeuanganService;
use App\Services\SuratNomorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Yudisium extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $search = '';

    public string $message = '';

    public ?int $batchId = null;

    public string $batchNama = '';

    public string $batchPeriode = '';

    public string $batchTanggal = '';

    public string $batchLokasi = '';

    public function luluskan(int $mahasiswaId): void
    {
        $mhs = Mahasiswa::with('user')->findOrFail($mahasiswaId);

        if (KeuanganService::hasTunggakan($mhs->id)) {
            $this->addError('yudisium', "NIM {$mhs->nim} masih punya tunggakan.");

            return;
        }

        $ipk = AkademikService::ringkasan($mhs->id)['ipk'];

        if ($ipk < 2.0) {
            $this->addError('yudisium', "IPK NIM {$mhs->nim} ({$ipk}) di bawah 2.00.");

            return;
        }

        $sidangLulus = Sidang::where('status', 'lulus')
            ->whereIn('judul_id', SkripsiJudul::where('mahasiswa_id', $mhs->id)->pluck('id'))
            ->exists();

        if (! $sidangLulus) {
            $this->addError('yudisium', "NIM {$mhs->nim} belum lulus sidang.");

            return;
        }

        DB::transaction(function () use ($mhs) {
            $mhs->update(['status' => 'lulus']);

            $skl = Surat::firstOrCreate(
                ['mahasiswa_id' => $mhs->id, 'jenis' => 'keterangan_lulus'],
                ['keperluan' => 'Surat Keterangan Lulus (SKL)', 'status' => 'selesai']
            );
            if (! $skl->nomor_surat) {
                SuratNomorService::assignIfEmpty($skl);
            }
            $skl->update(['status' => 'selesai']);

            Alumni::firstOrCreate(
                ['mahasiswa_id' => $mhs->id],
                ['tahun_lulus' => (int) now()->year]
            );

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'mahasiswa.lulus',
                'model' => Mahasiswa::class,
                'model_id' => $mhs->id,
            ]);
        });

        $this->message = "NIM {$mhs->nim} dinyatakan lulus. SKL diterbitkan & profil alumni dibuat.";
    }

    public function buatBatch(): void
    {
        $this->validate([
            'batchNama' => 'required|string|max:100',
            'batchPeriode' => 'required|string|max:20',
            'batchTanggal' => 'nullable|date',
            'batchLokasi' => 'nullable|string|max:100',
        ]);

        $batch = WisudaBatch::create([
            'nama' => trim($this->batchNama),
            'periode' => trim($this->batchPeriode),
            'tanggal_wisuda' => $this->batchTanggal ?: null,
            'lokasi' => $this->batchLokasi ?: null,
            'status' => 'dibuka',
        ]);

        $this->reset(['batchNama', 'batchPeriode', 'batchTanggal', 'batchLokasi']);
        $this->batchId = $batch->id;
        $this->message = "Batch wisuda {$batch->nama} dibuka.";
    }

    public function daftarkanWisuda(int $mahasiswaId): void
    {
        if (! $this->batchId) {
            $this->addError('yudisium', 'Pilih batch wisuda terlebih dahulu.');

            return;
        }

        $batch = WisudaBatch::findOrFail($this->batchId);
        if ($batch->status !== 'dibuka') {
            $this->addError('yudisium', "Batch {$batch->nama} sudah {$batch->status}.");

            return;
        }

        $mhs = Mahasiswa::findOrFail($mahasiswaId);
        if ($mhs->status !== 'lulus') {
            $this->addError('yudisium', "NIM {$mhs->nim} belum diyudisium.");

            return;
        }

        try {
            $hasil = DB::transaction(function () use ($mhs) {
                $batch = WisudaBatch::whereKey($this->batchId)->lockForUpdate()->firstOrFail();
                if ($batch->status !== 'dibuka') {
                    throw new \RuntimeException("Batch {$batch->nama} sudah {$batch->status}.");
                }
                if ($batch->peserta()->count() >= (int) $batch->kuota) {
                    throw new \RuntimeException("Kuota batch {$batch->nama} penuh.");
                }

                $noKursi = 'K-'.str_pad((string) ($batch->peserta()->count() + 1), 3, '0', STR_PAD_LEFT);

                $peserta = WisudaPeserta::firstOrCreate(
                    ['batch_id' => $batch->id, 'mahasiswa_id' => $mhs->id],
                    [
                        'no_kursi' => $noKursi,
                        'status_toga' => 'belum',
                        'no_ijazah' => 'IJZ/'.now()->year.'/'.str_pad((string) $mhs->id, 6, '0', STR_PAD_LEFT),
                        'pin_ijazah' => strtoupper(Str::random(8)),
                        'status_skl' => 'selesai',
                    ]
                );

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'wisuda.daftar',
                    'model' => WisudaPeserta::class,
                    'model_id' => $mhs->id,
                ]);

                return ['batch' => $batch->nama, 'kursi' => $peserta->no_kursi ?? $noKursi, 'nim' => $mhs->nim];
            });
        } catch (\RuntimeException $e) {
            $this->addError('yudisium', $e->getMessage());

            return;
        }

        $this->message = "NIM {$hasil['nim']} masuk batch {$hasil['batch']} kursi {$hasil['kursi']}. PIN ijazah diterbitkan.";
    }

    public function updateToga(int $pesertaId, string $status): void
    {
        if (! in_array($status, ['belum', 'pinjam', 'kembali'], true)) {
            $this->addError('yudisium', 'Status toga tidak valid.');

            return;
        }

        $peserta = WisudaPeserta::findOrFail($pesertaId);
        $peserta->update(['status_toga' => $status]);
        $this->message = "Toga kursi {$peserta->no_kursi} → {$status}.";
    }

    public function tutupBatch(): void
    {
        if (! $this->batchId) {
            return;
        }

        $batch = WisudaBatch::findOrFail($this->batchId);
        $batch->update(['status' => 'ditutup']);
        $this->message = "Batch {$batch->nama} ditutup.";
    }

    public function render()
    {
        $query = Mahasiswa::with(['user', 'prodi'])
            ->where('status', '!=', 'lulus')
            ->when($this->search !== '', fn ($q) => $q->where('nim', 'like', '%'.$this->search.'%'));

        $lulus = Mahasiswa::with(['user', 'prodi'])
            ->where('status', 'lulus')
            ->when($this->search !== '', fn ($q) => $q->where('nim', 'like', '%'.$this->search.'%'))
            ->latest('id')
            ->take(20)
            ->get();

        $peserta = $this->batchId
            ? WisudaPeserta::with(['mahasiswa.user', 'mahasiswa.prodi'])
                ->where('batch_id', $this->batchId)
                ->latest('id')
                ->paginate(10, pageName: 'pesertaPage')
            : null;

        return view('livewire.skripsi.yudisium', [
            'mahasiswa' => $query->latest('id')->paginate(10),
            'batches' => WisudaBatch::latest('id')->get(),
            'lulusan' => $lulus,
            'peserta' => $peserta,
        ]);
    }
}
