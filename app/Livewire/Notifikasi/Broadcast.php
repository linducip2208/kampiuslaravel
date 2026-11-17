<?php

namespace App\Livewire\Notifikasi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\BroadcastTemplate;
use App\Models\PmbPendaftar;
use App\Services\NotifikasiService;
use App\Services\WaService;
use Livewire\Component;

class Broadcast extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi', 'keuangan', 'pimpinan'];

    public string $role = 'mahasiswa';

    public string $judul = '';

    public string $isi = '';

    public string $message = '';

    public ?int $templateId = null;

    public string $templateNama = '';

    public string $waMessage = '';

    public function pakaiTemplate(): void
    {
        $this->validate(['templateId' => 'required|exists:broadcast_templates,id']);

        $tpl = BroadcastTemplate::findOrFail($this->templateId);
        $this->judul = $tpl->judul;
        $this->isi = $tpl->isi;
        $this->role = $tpl->role_target;
        $this->message = 'Template "'.$tpl->nama.'" dimuat.';
    }

    public function simpanTemplate(): void
    {
        $this->validate([
            'templateNama' => 'required|string|max:100',
            'judul' => 'required|string|max:150',
            'isi' => 'required|string|max:1000',
            'role' => 'required|in:superadmin,admin_prodi,keuangan,dosen,mahasiswa,pimpinan',
        ]);

        BroadcastTemplate::updateOrCreate(
            ['nama' => $this->templateNama],
            ['judul' => $this->judul, 'isi' => $this->isi, 'role_target' => $this->role]
        );

        $this->reset('templateNama');
        $this->message = 'Template tersimpan.';
    }

    public function send(NotifikasiService $notif): void
    {
        $this->validate([
            'role' => 'required|in:superadmin,admin_prodi,keuangan,dosen,mahasiswa,pimpinan',
            'judul' => 'required|string|max:150',
            'isi' => 'required|string|max:1000',
        ]);

        $count = $notif->broadcast($this->role, $this->judul, $this->isi);

        $this->reset(['judul', 'isi']);
        $this->message = "Terkirim ke {$count} pengguna (role {$this->role}).";
    }

    /** Broadcast WA follow-up ke pendaftar PMB yang belum difollow-up (CRM). */
    public function kirimWa(WaService $wa): void
    {
        $this->validate(['waMessage' => 'nullable|string|max:1000']);

        $simulasi = WaService::isSimulated();

        if (trim($this->waMessage) !== '') {
            $terkirim = 0;
            $simulasiCount = 0;

            PmbPendaftar::query()
                ->whereIn('status', [PmbPendaftar::STATUS_DIAJUKAN, PmbPendaftar::STATUS_DIVERIFIKASI])
                ->where(function ($q): void {
                    $q->whereNull('last_follow_up_at')->orWhere('last_follow_up_at', '<', now()->subDays(3));
                })
                ->orderBy('id')
                ->limit(100)
                ->chunk(50, function ($items) use ($wa, $simulasi, &$terkirim, &$simulasiCount): void {
                    foreach ($items as $pendaftar) {
                        if ($wa->send((string) $pendaftar->hp, $this->waMessage)) {
                            if ($simulasi) {
                                $simulasiCount++;
                            } else {
                                $pendaftar->update(['last_follow_up_at' => now()]);
                                $terkirim++;
                            }
                        }
                    }
                });

            $this->reset('waMessage');
            $this->message = $simulasi
                ? "WA terkirim ke 0 pendaftar, {$simulasiCount} simulasi (mode simulasi — gateway WA belum dikonfigurasi)."
                : "WA terkirim ke {$terkirim} pendaftar.";
        } else {
            $count = $wa->broadcastFollowUp(100);
            $this->message = $simulasi
                ? "WA follow-up terkirim ke 0 pendaftar, {$count} simulasi (mode simulasi — gateway WA belum dikonfigurasi)."
                : "WA follow-up terkirim ke {$count} pendaftar.";
        }
    }

    public function render()
    {
        return view('livewire.notifikasi.broadcast', [
            'templates' => BroadcastTemplate::orderBy('nama')->get(),
        ]);
    }
}
