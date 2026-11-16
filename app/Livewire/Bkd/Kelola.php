<?php

namespace App\Livewire\Bkd;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\BebanDosen;
use App\Models\Dosen;
use App\Models\TahunAkademik;
use App\Services\BkdService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * M-053: BKD lanjut — dosen mengajukan beban, admin menyetujui/menolak,
 * sinkronisasi mengajar dari kelas, rekap SKS, dan SK mengajar.
 */
class Kelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = [];

    public string $kategori = 'pendidikan';

    public string $uraian = '';

    public int $sks = 2;

    public string $bukti = '';

    public ?int $tahunId = null;

    public string $message = '';

    protected function authorizeBkd(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi']), 403);
    }

    protected function dosenId(): ?int
    {
        return Dosen::where('user_id', Auth::id())->value('id');
    }

    protected function isAdmin(): bool
    {
        return (bool) Auth::user()?->hasAnyRole(['superadmin', 'admin_prodi']);
    }

    public function ajukan(): void
    {
        $this->authorizeBkd();
        abort_unless($this->dosenId() || $this->isAdmin(), 403);

        $this->validate([
            'kategori' => 'required|in:pendidikan,penelitian,pengabdian,penunjang',
            'uraian' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:20',
            'bukti' => 'nullable|string|max:255',
            'tahunId' => 'required|exists:tahun_akademik,id',
        ]);

        BebanDosen::create([
            'dosen_id' => $this->dosenId() ?? Dosen::orderBy('id')->value('id'),
            'tahun_akademik_id' => $this->tahunId,
            'kategori' => $this->kategori,
            'uraian' => $this->uraian,
            'sks' => $this->sks,
            'bukti' => $this->bukti ?: null,
            'status' => 'diajukan',
        ]);

        $this->reset(['uraian', 'sks', 'bukti']);
        $this->message = 'Beban diajukan. Menunggu verifikasi admin.';
    }

    public function sinkron(): void
    {
        $this->authorizeBkd();
        abort_unless($this->dosenId() && $this->tahunId, 422, 'Pilih tahun akademik.');

        $n = BkdService::sinkronMengajarDariKelas($this->dosenId(), $this->tahunId);
        $this->message = "Sinkronisasi selesai: {$n} beban mengajar ditambahkan.";
    }

    public function setujui(int $id): void
    {
        abort_unless($this->isAdmin(), 403);
        $beban = BebanDosen::findOrFail($id);
        $beban->update(['status' => 'disetujui']);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'bkd.disetujui',
            'model' => BebanDosen::class, 'model_id' => $beban->id, 'created_at' => now(),
        ]);

        $this->message = 'Beban disetujui.';
    }

    public function tolak(int $id): void
    {
        abort_unless($this->isAdmin(), 403);
        $beban = BebanDosen::findOrFail($id);
        $beban->update(['status' => 'ditolak']);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'bkd.ditolak',
            'model' => BebanDosen::class, 'model_id' => $beban->id, 'created_at' => now(),
        ]);

        $this->message = 'Beban ditolak.';
    }

    public function terbitkanSk(): void
    {
        abort_unless($this->isAdmin() && $this->tahunId, 422);
        $dosenId = $this->dosenId() ?? Dosen::orderBy('id')->value('id');
        abort_unless($dosenId, 422, 'Data dosen tidak ditemukan.');

        $sk = BkdService::generateSk($dosenId, $this->tahunId);
        $this->message = "SK terbit: {$sk->nomor} ({$sk->total_sks} SKS).";
    }

    public function render()
    {
        $this->tahunId ??= TahunAkademik::aktif()->value('id') ?? TahunAkademik::orderByDesc('id')->value('id');

        $query = BebanDosen::with(['dosen.user'])
            ->when($this->tahunId, fn ($q) => $q->where('tahun_akademik_id', $this->tahunId))
            ->when(! $this->isAdmin() && $this->dosenId(), fn ($q) => $q->where('dosen_id', $this->dosenId()))
            // Non-admin tanpa baris dosen (mis. kaprodi murni struktural):
            // tidak mewarisi daftar semua beban (anti-bocor lintas data).
            ->when(! $this->isAdmin() && ! $this->dosenId(), fn ($q) => $q->whereRaw('0 = 1'));

        return view('livewire.bkd.kelola', [
            'bebans' => $query->latest('id')->paginate(10),
            'tahuns' => TahunAkademik::orderByDesc('id')->get(),
            'rekap' => ($this->dosenId() && $this->tahunId)
                ? BkdService::rekapKategori($this->dosenId(), $this->tahunId)
                : [],
            'totalSks' => ($this->dosenId() && $this->tahunId)
                ? BkdService::totalSks($this->dosenId(), $this->tahunId)
                : 0,
            'isAdmin' => $this->isAdmin(),
            'rekapProdi' => $this->isAdmin() && $this->tahunId
                ? BkdService::rekapPerProdi($this->tahunId)
                : [],
        ]);
    }
}
