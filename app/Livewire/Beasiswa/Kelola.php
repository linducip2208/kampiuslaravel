<?php

namespace App\Livewire\Beasiswa;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Beasiswa;
use App\Models\Mahasiswa;
use App\Models\MahasiswaBeasiswa;
use Livewire\Component;
use Livewire\WithPagination;

class Kelola extends Component
{
    use RequiresRoles;
    use WithPagination;

    public array $allowedRoles = ['keuangan', 'superadmin', 'admin_prodi'];

    public ?int $editId = null;

    public string $nama = '';

    public string $jenis = 'persen';

    public int $nilai = 0;

    public ?int $kuota = null;

    public bool $aktif = true;

    public ?int $assignBeasiswaId = null;

    public string $assignNim = '';

    public ?int $assignTahunId = null;

    public string $message = '';

    public function simpan(): void
    {
        $this->validate([
            'nama' => 'required|string|max:100',
            'jenis' => 'required|in:persen,nominal',
            'nilai' => 'required|integer|min:1',
            'kuota' => 'nullable|integer|min:1',
        ]);

        Beasiswa::updateOrCreate(['id' => $this->editId ?? 0], [
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'nilai' => $this->nilai,
            'kuota' => $this->kuota,
            'aktif' => $this->aktif,
        ]);

        $this->reset(['editId', 'nama', 'jenis', 'nilai', 'kuota', 'aktif']);
        $this->message = 'Beasiswa tersimpan.';
    }

    public function edit(int $id): void
    {
        $b = Beasiswa::findOrFail($id);
        $this->editId = $b->id;
        $this->nama = $b->nama;
        $this->jenis = $b->jenis;
        $this->nilai = (int) $b->nilai;
        $this->kuota = $b->kuota;
        $this->aktif = (bool) $b->aktif;
    }

    public function hapus(int $id): void
    {
        try {
            Beasiswa::findOrFail($id)->delete();
            $this->message = 'Beasiswa dihapus.';
        } catch (\Throwable) {
            $this->message = 'Beasiswa dipakai data lain, nonaktifkan saja.';
        }
    }

    public function assign(): void
    {
        $this->validate([
            'assignBeasiswaId' => 'required|exists:beasiswa,id',
            'assignNim' => 'required|string',
            'assignTahunId' => 'nullable|exists:tahun_akademik,id',
        ]);

        $mhs = Mahasiswa::where('nim', trim($this->assignNim))->firstOrFail();

        $beasiswa = Beasiswa::findOrFail($this->assignBeasiswaId);

        // M-018: tegakkan kuota — kuota habis → tolak (bukan diam-diam over-assign).
        if ($beasiswa->kuota !== null) {
            $terpakai = MahasiswaBeasiswa::where('beasiswa_id', $beasiswa->id)->count();
            $sudahPunya = MahasiswaBeasiswa::where('beasiswa_id', $beasiswa->id)
                ->where('mahasiswa_id', $mhs->id)
                ->where('tahun_akademik_id', $this->assignTahunId)
                ->exists();
            if (! $sudahPunya && $terpakai >= (int) $beasiswa->kuota) {
                $this->addError('assignBeasiswaId', "Kuota beasiswa {$beasiswa->nama} habis ({$terpakai}/{$beasiswa->kuota}).");
                $this->message = "Kuota beasiswa {$beasiswa->nama} habis.";

                return;
            }
        }

        MahasiswaBeasiswa::updateOrCreate(
            ['mahasiswa_id' => $mhs->id, 'beasiswa_id' => $this->assignBeasiswaId, 'tahun_akademik_id' => $this->assignTahunId],
            ['status' => 'aktif']
        );

        $this->reset(['assignBeasiswaId', 'assignNim', 'assignTahunId']);
        $this->message = "Beasiswa ditetapkan untuk NIM {$mhs->nim}. Berlaku pada generate tagihan berikutnya.";
    }

    public function render()
    {
        return view('livewire.beasiswa.kelola', [
            'beasiswas' => Beasiswa::latest('id')->paginate(10),
        ]);
    }
}
