<?php

namespace App\Livewire\Skripsi;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\SkripsiJudul;
use App\Models\SkripsiTopik;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class BankTopik extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public string $judul = '';

    public string $deskripsi = '';

    public string $bidang = '';

    public int $kuota = 1;

    public string $cari = '';

    public string $message = '';

    public function save(): void
    {
        $this->authorizeRoles(['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi']);

        $this->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'bidang' => 'nullable|string|max:100',
            'kuota' => 'required|integer|min:1|max:20',
        ]);

        $dosen = Dosen::where('user_id', Auth::id())->first();
        abort_unless($dosen || Auth::user()->hasAnyRole(['superadmin', 'admin_prodi']), 403);

        $topik = SkripsiTopik::create([
            'dosen_id' => $dosen?->id ?? Dosen::query()->value('id') ?? 0,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi ?: null,
            'bidang' => $this->bidang ?: null,
            'kuota' => $this->kuota,
            'status' => 'buka',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'skripsi.topik.dibuat',
            'model' => SkripsiTopik::class,
            'model_id' => $topik->id,
            'created_at' => now(),
        ]);

        $this->reset(['judul', 'deskripsi', 'bidang', 'kuota']);
        $this->kuota = 1;
        $this->message = 'Topik berhasil ditambahkan ke bank topik.';
    }

    public function ambil(int $topikId): void
    {
        $this->authorizeRoles(['mahasiswa', 'superadmin', 'admin_prodi']);

        $topik = SkripsiTopik::findOrFail($topikId);
        abort_unless($topik->isTersedia(), 422, 'Kuota topik sudah habis.');

        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();

        $pengajuan = SkripsiJudul::create([
            'mahasiswa_id' => $mahasiswa->id,
            'judul' => $topik->judul,
            'deskripsi' => $topik->deskripsi,
            'status' => 'diajukan',
            'topik_id' => $topik->id,
        ]);

        $topik->increment('diambil');
        if ($topik->fresh()->diambil >= $topik->kuota) {
            $topik->update(['status' => 'tutup']);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'skripsi.topik.diambil',
            'model' => SkripsiJudul::class,
            'model_id' => $pengajuan->id,
            'created_at' => now(),
        ]);

        $this->message = 'Topik berhasil diambil sebagai pengajuan judul.';
    }

    public function tutup(int $topikId): void
    {
        $this->authorizeRoles(['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi']);

        $topik = SkripsiTopik::findOrFail($topikId);
        $topik->update(['status' => 'tutup']);
        $this->message = 'Topik ditutup.';
    }

    protected function authorizeRoles(array $roles): void
    {
        abort_unless(Auth::user()?->hasAnyRole($roles), 403);
    }

    public function render()
    {
        $topiks = SkripsiTopik::with('dosen.user')
            ->when($this->cari !== '', fn ($q) => $q->where('judul', 'like', '%'.$this->cari.'%')
                ->orWhere('bidang', 'like', '%'.$this->cari.'%'))
            ->latest()->paginate(10);

        return view('livewire.skripsi.bank-topik', ['topiks' => $topiks]);
    }
}
