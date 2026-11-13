<?php

namespace App\Livewire\Pmb;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Mahasiswa;
use App\Models\PmbGelombang;
use App\Models\PmbNilaiSeleksi;
use App\Models\PmbPendaftar;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Verifikasi berkas pendaftar PMB oleh admin + scoring CBT/wawancara.
 */
class Verifikasi extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public ?int $selectedId = null;

    public string $catatan = '';

    public ?float $nilaiCbt = null;

    public ?float $nilaiWawancara = null;

    public string $catatanNilai = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function ubahStatus(int $id, string $status): void
    {
        if (! in_array($status, PmbPendaftar::STATUSES, true)) {
            return;
        }

        $this->validate([
            'catatan' => [
                Rule::requiredIf(in_array($status, [PmbPendaftar::STATUS_DITOLAK, PmbPendaftar::STATUS_TIDAK], true)),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $pendaftar = PmbPendaftar::query()->with('gelombang')->findOrFail($id);

        if ($status === PmbPendaftar::STATUS_LULUS && $pendaftar->gelombang && $pendaftar->gelombang->kuotaLulusPenuh() && $pendaftar->status !== PmbPendaftar::STATUS_LULUS) {
            $this->addError('selectedId', 'Kuota kelulusan gelombang ini sudah penuh.');

            return;
        }

        DB::transaction(function () use ($pendaftar, $status): void {
            $p = PmbPendaftar::whereKey($pendaftar->id)->lockForUpdate()->firstOrFail();
            $g = $p->gelombang_id ? PmbGelombang::whereKey($p->gelombang_id)->lockForUpdate()->first() : null;
            if ($status === PmbPendaftar::STATUS_LULUS && $g && $g->kuotaLulusPenuh() && $p->status !== PmbPendaftar::STATUS_LULUS) {
                abort(422, 'Kuota kelulusan gelombang ini sudah penuh.');
            }
            $p->update([
                'status' => $status,
                'catatan_verifikasi' => $this->catatan ?: $p->catatan_verifikasi,
            ]);
        });

        $this->reset(['selectedId', 'catatan']);
        session()->flash('info', "Status {$pendaftar->no_daftar} diubah menjadi {$status}.");
    }

    public function pilih(int $id): void
    {
        $pendaftar = PmbPendaftar::query()->with('nilaiSeleksi')->findOrFail($id);
        $this->selectedId = $pendaftar->id;
        $this->catatan = (string) ($pendaftar->catatan_verifikasi ?? '');
        $this->nilaiCbt = $pendaftar->nilaiSeleksi->firstWhere('jenis', PmbNilaiSeleksi::JENIS_CBT)?->nilai;
        $this->nilaiWawancara = $pendaftar->nilaiSeleksi->firstWhere('jenis', PmbNilaiSeleksi::JENIS_WAWANCARA)?->nilai;
        $this->catatanNilai = '';
    }

    /**
     * Simpan skor CBT / wawancara (0-100). Upsert per (pendaftar, jenis).
     */
    public function simpanNilai(int $id, string $jenis): void
    {
        if (! in_array($jenis, PmbNilaiSeleksi::JENIS, true)) {
            return;
        }

        $field = $jenis === PmbNilaiSeleksi::JENIS_CBT ? 'nilaiCbt' : 'nilaiWawancara';

        $this->validate([
            $field => ['required', 'numeric', 'min:0', 'max:100'],
            'catatanNilai' => ['nullable', 'string', 'max:1000'],
        ]);

        $pendaftar = PmbPendaftar::query()->findOrFail($id);

        PmbNilaiSeleksi::query()->updateOrCreate(
            ['pendaftar_id' => $pendaftar->id, 'jenis' => $jenis],
            [
                'nilai' => (float) $this->{$field},
                'catatan' => $this->catatanNilai ?: null,
                'penilai_id' => Auth::id(),
            ]
        );

        session()->flash('info', "Nilai {$jenis} {$pendaftar->no_daftar} disimpan: {$this->{$field}}.");
    }

    /**
     * Tentukan kelulusan dari rata-rata CBT+wawancara (ambang 60).
     */
    public function tentukanKelulusan(int $id, float $ambang = 60.0): void
    {
        $pendaftar = PmbPendaftar::query()->with(['gelombang', 'nilaiSeleksi'])->findOrFail($id);

        $rata = $pendaftar->nilaiAkhir();

        if ($rata === null) {
            $this->addError('selectedId', 'Belum ada nilai seleksi untuk pendaftar ini.');

            return;
        }

        if ($rata >= $ambang && $pendaftar->gelombang && $pendaftar->gelombang->kuotaLulusPenuh() && $pendaftar->status !== PmbPendaftar::STATUS_LULUS) {
            $this->addError('selectedId', 'Kuota kelulusan gelombang ini sudah penuh.');

            return;
        }

        DB::transaction(function () use ($pendaftar, $rata, $ambang): void {
            $p = PmbPendaftar::whereKey($pendaftar->id)->lockForUpdate()->firstOrFail();
            $g = $p->gelombang_id ? PmbGelombang::whereKey($p->gelombang_id)->lockForUpdate()->first() : null;
            $statusBaru = $rata >= $ambang ? PmbPendaftar::STATUS_LULUS : PmbPendaftar::STATUS_TIDAK;
            if ($statusBaru === PmbPendaftar::STATUS_LULUS && $g && $g->kuotaLulusPenuh() && $p->status !== PmbPendaftar::STATUS_LULUS) {
                abort(422, 'Kuota kelulusan gelombang ini sudah penuh.');
            }
            $p->update([
                'status' => $statusBaru,
                'catatan_verifikasi' => "Rata-rata seleksi: {$rata} (ambang {$ambang}).",
            ]);
            $pendaftar->status = $statusBaru;
        });

        session()->flash('info', "Kelulusan {$pendaftar->no_daftar} ditentukan: {$pendaftar->status} (rata-rata {$rata}).");
    }

    /**
     * Daftar ulang: pendaftar lulus -> akun user + data mahasiswa (NIM)
     * + tagihan awal. Idempotent: tolak bila email sudah dipakai akun lain.
     */
    public function daftarUlang(int $id): void
    {
        $pendaftar = PmbPendaftar::query()->with('gelombang')->findOrFail($id);

        if ($pendaftar->status !== PmbPendaftar::STATUS_LULUS) {
            $this->addError('selectedId', 'Hanya pendaftar berstatus lulus yang bisa daftar ulang.');

            return;
        }

        if ($pendaftar->user_id) {
            $this->addError('selectedId', 'Pendaftar ini sudah memiliki akun.');

            return;
        }

        $taken = User::query()->where('email', $pendaftar->email)->exists();

        if ($taken) {
            $this->addError('selectedId', 'Email sudah dipakai akun lain. Minta pendaftar memakai email berbeda.');

            return;
        }

        $result = DB::transaction(function () use ($pendaftar) {
            $user = User::query()->create([
                'name' => $pendaftar->nama,
                'email' => $pendaftar->email,
                'password' => str()->random(12),
            ]);
            $user->assignRole('mahasiswa');

            $angkatan = (int) now()->format('Y');
            $seq = (int) (Mahasiswa::query()->where('angkatan', $angkatan)->max('id') ?? 0) + 1;
            $nim = $angkatan.sprintf('%02d', (int) $pendaftar->prodi1_id).sprintf('%04d', $seq);

            $mahasiswa = Mahasiswa::query()->create([
                'user_id' => $user->id,
                'nim' => $nim,
                'prodi_id' => $pendaftar->prodi1_id,
                'angkatan' => $angkatan,
                'status' => 'aktif',
            ]);

            $tagihan = null;
            $biaya = (int) ($pendaftar->gelombang->biaya ?? 0);
            if ($biaya > 0) {
                $tagihan = Tagihan::query()->firstOrCreate(
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'tahun_akademik_id' => $pendaftar->gelombang->tahun_akademik_id,
                        'jenis' => 'PMB',
                    ],
                    ['nominal' => $biaya, 'terbayar' => 0, 'status' => 'belum_bayar']
                );
            }

            $pendaftar->update(['user_id' => $user->id]);

            return compact('user', 'mahasiswa', 'tagihan');
        });

        $this->reset(['selectedId', 'catatan', 'nilaiCbt', 'nilaiWawancara', 'catatanNilai']);
        session()->flash('info', "Daftar ulang OK: NIM {$result['mahasiswa']->nim} ({$result['user']->email}).".
            ($result['tagihan'] ? ' Tagihan awal Rp '.number_format($result['tagihan']->nominal, 0, ',', '.') : ''));
    }

    public function render(): View
    {
        $pendaftars = PmbPendaftar::query()
            ->with(['gelombang', 'nilaiSeleksi', 'prodi1'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('no_daftar', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        return view('livewire.pmb.verifikasi', [
            'pendaftars' => $pendaftars,
            'statuses' => PmbPendaftar::STATUSES,
        ]);
    }
}
