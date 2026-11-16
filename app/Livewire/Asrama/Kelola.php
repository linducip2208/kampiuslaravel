<?php

namespace App\Livewire\Asrama;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AsramaHuni;
use App\Models\AsramaKamar;
use App\Models\AuditLog;
use App\Models\Mahasiswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Ide bank: alokasi kamar asrama + iuran (tagihan jenis asrama).
 * 1 bed 1 penghuni (kapasitas), 1 mahasiswa 1 kamar (unique).
 */
class Kelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $gedung = '';

    public string $kode = '';

    public int $kapasitas = 2;

    public int $iuran = 0;

    public string $nimHuni = '';

    public ?int $kamarHuniId = null;

    public string $message = '';

    public function simpanKamar(): void
    {
        $this->validate([
            'gedung' => 'required|string|max:50',
            'kode' => 'required|string|max:20|unique:asrama_kamar,kode',
            'kapasitas' => 'required|integer|min:1|max:8',
            'iuran' => 'required|integer|min:0',
        ]);

        AsramaKamar::create([
            'gedung' => $this->gedung, 'kode' => $this->kode,
            'kapasitas' => $this->kapasitas, 'iuran' => $this->iuran,
        ]);

        $this->reset(['gedung', 'kode', 'kapasitas', 'iuran']);
        $this->kapasitas = 2;
        $this->message = 'Kamar tersimpan.';
    }

    public function hunikan(): void
    {
        $this->validate([
            'nimHuni' => 'required|string',
            'kamarHuniId' => 'required|exists:asrama_kamar,id',
        ]);

        $mhs = Mahasiswa::where('nim', trim($this->nimHuni))->first();

        if (! $mhs) {
            $this->addError('nimHuni', 'NIM tidak ditemukan.');

            return;
        }

        if (AsramaHuni::where('mahasiswa_id', $mhs->id)->exists()) {
            $this->addError('nimHuni', 'Mahasiswa sudah menghuni kamar lain.');

            return;
        }

        $kamar = AsramaKamar::findOrFail($this->kamarHuniId);

        if ($kamar->penuh()) {
            $this->addError('nimHuni', "Kamar {$kamar->kode} sudah penuh.");

            return;
        }

        AsramaHuni::create([
            'kamar_id' => $kamar->id, 'mahasiswa_id' => $mhs->id,
            'masuk_at' => today()->toDateString(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'asrama.huni',
            'model' => AsramaHuni::class, 'model_id' => $mhs->id,
            'diff_json' => ['kamar' => $kamar->kode], 'created_at' => now(),
        ]);

        $this->reset(['nimHuni', 'kamarHuniId']);
        $this->message = "NIM {$mhs->nim} dihuni di {$kamar->kode}.";
    }

    public function keluarkan(int $huniId): void
    {
        $huni = AsramaHuni::with('mahasiswa')->findOrFail($huniId);
        $nim = $huni->mahasiswa->nim ?? "#{$huni->mahasiswa_id}";
        $huni->delete();

        AuditLog::create([
            'user_id' => Auth::id(), 'action' => 'asrama.keluar',
            'model' => AsramaHuni::class, 'model_id' => $huniId,
            'created_at' => now(),
        ]);

        $this->message = "NIM {$nim} dikeluarkan dari asrama.";
    }

    /** Tarik iuran asrama periode berjalan untuk semua penghuni aktif. */
    public function tarikIuran(): void
    {
        $tahunId = TahunAkademik::aktif()->value('id') ?? TahunAkademik::orderByDesc('id')->value('id');
        abort_unless($tahunId, 422, 'Belum ada tahun akademik.');

        $dibuat = 0;

        DB::transaction(function () use ($tahunId, &$dibuat): void {
            foreach (AsramaHuni::with('kamar')->cursor() as $huni) {
                $nominal = (int) ($huni->kamar->iuran ?? 0);

                if ($nominal <= 0) {
                    continue;
                }

                $tagihan = Tagihan::firstOrCreate(
                    ['mahasiswa_id' => $huni->mahasiswa_id, 'tahun_akademik_id' => $tahunId, 'jenis' => 'asrama'],
                    ['nominal' => $nominal, 'terbayar' => 0, 'status' => 'belum_bayar']
                );

                if ($tagihan->wasRecentlyCreated) {
                    $dibuat++;
                }
            }
        });

        $this->message = "Iuran asrama ditarik: {$dibuat} tagihan baru.";
    }

    public function render(): View
    {
        return view('livewire.asrama.kelola', [
            'kamars' => AsramaKamar::withCount('penghuni')->orderBy('gedung')->orderBy('kode')->get(),
            'hunian' => AsramaHuni::with(['kamar', 'mahasiswa.user'])->latest('id')->paginate(10),
        ]);
    }
}
