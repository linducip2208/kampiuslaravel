<?php

namespace App\Livewire\Kemahasiswaan;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\CutiPengajuan;
use App\Models\Mahasiswa;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Cuti extends Component
{
    use RequiresRoles;
    use WithPagination;

    /** @var list<string> */
    public array $allowedRoles = ['mahasiswa', 'superadmin', 'admin_prodi'];

    public ?int $tahunAkademikId = null;

    public string $alasan = '';

    public string $search = '';

    public string $statusFilter = '';

    public ?int $mahasiswaKelolaId = null;

    public string $statusBaru = 'aktif';

    public ?int $prodiTujuanId = null;

    public string $catatan = '';

    public string $message = '';

    public function ajukan(): void
    {
        $this->authorizeMahasiswa();

        $this->validate([
            'tahunAkademikId' => ['required', 'integer', 'exists:tahun_akademik,id'],
            'alasan' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();

        if (! $mahasiswa->isAktif()) {
            $this->addError('alasan', 'Hanya mahasiswa berstatus aktif yang dapat mengajukan cuti.');

            return;
        }

        $pending = CutiPengajuan::where('mahasiswa_id', $mahasiswa->id)
            ->where('status', CutiPengajuan::STATUS_DIAJUKAN)
            ->exists();

        if ($pending) {
            $this->addError('alasan', 'Masih ada pengajuan cuti yang menunggu persetujuan.');

            return;
        }

        $pengajuan = CutiPengajuan::create([
            'mahasiswa_id' => $mahasiswa->id,
            'tahun_akademik_id' => $this->tahunAkademikId,
            'alasan' => $this->alasan,
            'status' => CutiPengajuan::STATUS_DIAJUKAN,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'cuti.diajukan',
            'model' => CutiPengajuan::class,
            'model_id' => $pengajuan->id,
        ]);

        $this->reset(['alasan']);
        $this->message = 'Pengajuan cuti berhasil dikirim. Menunggu persetujuan admin prodi.';
    }

    public function setujui(int $id): void
    {
        $this->authorizeAdmin();

        $this->validate(['catatan' => ['nullable', 'string', 'max:2000']]);

        $pengajuan = CutiPengajuan::with('mahasiswa')->findOrFail($id);

        if ($pengajuan->status !== CutiPengajuan::STATUS_DIAJUKAN) {
            $this->addError('approval', 'Pengajuan sudah diproses sebelumnya.');

            return;
        }

        $pengajuan->update([
            'status' => CutiPengajuan::STATUS_DISETUJUI,
            'catatan_admin' => $this->catatan ?: null,
            'decided_by' => Auth::id(),
            'decided_at' => now(),
        ]);

        $pengajuan->mahasiswa->update(['status' => Mahasiswa::STATUS_CUTI]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'cuti.disetujui',
            'model' => CutiPengajuan::class,
            'model_id' => $pengajuan->id,
        ]);

        $this->reset(['catatan']);
        $this->message = "Cuti NIM {$pengajuan->mahasiswa->nim} disetujui.";
    }

    public function tolak(int $id): void
    {
        $this->authorizeAdmin();

        $this->validate(['catatan' => ['nullable', 'string', 'max:2000']]);

        $pengajuan = CutiPengajuan::with('mahasiswa')->findOrFail($id);

        if ($pengajuan->status !== CutiPengajuan::STATUS_DIAJUKAN) {
            $this->addError('approval', 'Pengajuan sudah diproses sebelumnya.');

            return;
        }

        $pengajuan->update([
            'status' => CutiPengajuan::STATUS_DITOLAK,
            'catatan_admin' => $this->catatan ?: null,
            'decided_by' => Auth::id(),
            'decided_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'cuti.ditolak',
            'model' => CutiPengajuan::class,
            'model_id' => $pengajuan->id,
        ]);

        $this->reset(['catatan']);
        $this->message = "Cuti NIM {$pengajuan->mahasiswa->nim} ditolak.";
    }

    public function ubahStatus(int $mahasiswaId): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'statusBaru' => ['required', Rule::in(Mahasiswa::STATUSES)],
        ]);

        $mhs = Mahasiswa::findOrFail($mahasiswaId);
        $mhs->update(['status' => $this->statusBaru]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'mahasiswa.status.'.$this->statusBaru,
            'model' => Mahasiswa::class,
            'model_id' => $mhs->id,
        ]);

        $this->message = "Status NIM {$mhs->nim} diubah menjadi {$this->statusBaru}.";
    }

    public function pindahProdi(int $mahasiswaId): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'prodiTujuanId' => ['required', 'integer', 'exists:prodi,id'],
        ]);

        $mhs = Mahasiswa::findOrFail($mahasiswaId);
        $mhs->update(['prodi_id' => $this->prodiTujuanId]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'mahasiswa.pindah_prodi',
            'model' => Mahasiswa::class,
            'model_id' => $mhs->id,
        ]);

        $this->reset(['prodiTujuanId']);
        $this->message = "NIM {$mhs->nim} dipindahkan ke prodi tujuan.";
    }

    public function render(): View
    {
        $isAdmin = Auth::check() && Auth::user()->hasAnyRole(['superadmin', 'admin_prodi']);
        $mahasiswaId = Mahasiswa::where('user_id', Auth::id())->value('id');

        $riwayat = $mahasiswaId
            ? CutiPengajuan::with('tahunAkademik')->where('mahasiswa_id', $mahasiswaId)->latest()->get()
            : collect();

        $antrean = $isAdmin
            ? CutiPengajuan::with(['mahasiswa.user', 'mahasiswa.prodi', 'tahunAkademik'])
                ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->search !== '', fn ($q) => $q->whereHas('mahasiswa', fn ($m) => $m->where('nim', 'like', '%'.$this->search.'%')))
                ->latest()->paginate(10)
            : null;

        return view('livewire.kemahasiswaan.cuti', [
            'riwayat' => $riwayat,
            'antrean' => $antrean,
            'isAdmin' => $isAdmin,
        ]);
    }

    private function authorizeMahasiswa(): void
    {
        if (! Auth::user()->hasAnyRole(['mahasiswa', 'superadmin', 'admin_prodi'])) {
            abort(403);
        }
    }

    private function authorizeAdmin(): void
    {
        if (! Auth::user()->hasAnyRole(['superadmin', 'admin_prodi'])) {
            abort(403);
        }
    }
}
