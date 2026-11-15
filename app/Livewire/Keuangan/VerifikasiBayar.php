<?php

namespace App\Livewire\Keuangan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\JurnalKeuangan;
use App\Models\Pembayaran;
use App\Services\KeuanganService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class VerifikasiBayar extends Component
{
    use RequiresRoles, WithFileUploads;

    public array $allowedRoles = ['keuangan', 'kasir', 'akuntansi', 'superadmin'];

    public string $statusFilter = 'pending';

    public string $message = '';

    public $fileCsv;

    public string $refundReason = '';

    /** @var array{matched?: int, selisih?: array, tak_dikenal?: array} */
    public array $rekonsiliasi = [];

    public function verify(int $id): void
    {
        KeuanganService::verifyPembayaran($id, (int) Auth::id(), true);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pembayaran.verified',
            'model' => Pembayaran::class,
            'model_id' => $id,
            'created_at' => now(),
        ]);

        $this->message = 'Pembayaran diverifikasi.';
    }

    public function reject(int $id): void
    {
        KeuanganService::verifyPembayaran($id, (int) Auth::id(), false);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pembayaran.rejected',
            'model' => Pembayaran::class,
            'model_id' => $id,
            'created_at' => now(),
        ]);

        $this->message = 'Pembayaran ditolak.';
    }

    public function refund(int $id): void
    {
        $ok = KeuanganService::refundPembayaran($id, (int) Auth::id(), $this->refundReason ?: null);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pembayaran.refunded',
            'model' => Pembayaran::class,
            'model_id' => $id,
            'created_at' => now(),
        ]);

        $this->refundReason = '';
        $this->message = $ok ? 'Refund berhasil: terbayar dikurangi + jurnal reversal.' : 'Refund gagal: hanya pembayaran verified yang belum direfund.';
    }

    /** Impor CSV mutasi bank (reference,nominal) untuk rekonsiliasi. */
    public function imporRekonsiliasi(): void
    {
        $this->validate([
            'fileCsv' => 'required|file|max:2048|mimes:csv,txt',
        ]);

        $content = (string) file_get_contents($this->fileCsv->getRealPath());
        $this->rekonsiliasi = KeuanganService::rekonsiliasiCsv($content);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'pembayaran.rekonsiliasi',
            'model' => Pembayaran::class,
            'model_id' => 0,
            'created_at' => now(),
        ]);

        $this->reset('fileCsv');
        $this->message = 'Rekonsiliasi selesai: '.$this->rekonsiliasi['matched'].' cocok, '
            .count($this->rekonsiliasi['selisih']).' selisih, '
            .count($this->rekonsiliasi['tak_dikenal']).' tak dikenal.';
    }

    public function render()
    {
        return view('livewire.keuangan.verifikasi-bayar', [
            'pembayarans' => Pembayaran::with(['tagihan.mahasiswa', 'tagihan.tahunAkademik'])
                ->when($this->statusFilter !== 'semua', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()->paginate(10),
            'jurnal' => JurnalKeuangan::with('pembayaran.tagihan')->latest()->take(10)->get(),
        ]);
    }
}
