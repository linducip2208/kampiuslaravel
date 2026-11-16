<?php

namespace App\Livewire\Sidang;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\AuditLog;
use App\Models\Dosen;
use App\Models\Sidang as SidangModel;
use App\Models\SidangPenguji;
use App\Services\BeritaAcaraService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NilaiSidang extends Component
{
    use RequiresRoles;

    public array $allowedRoles = ['dosen', 'kaprodi', 'kajur', 'superadmin', 'admin_prodi'];

    public ?int $sidang_id = null;

    public ?int $dosen_id = null;

    /** Nama penguji eksternal (non-dosen); salah satu dari dosen/nama wajib. */
    public string $namaEksternal = '';

    public string $peran = 'penguji';

    public ?float $nilai = null;

    public string $status = 'lulus';

    public string $message = '';

    public ?float $rataRata = null;

    public string $hurufMutu = '-';

    public ?string $beritaAcara = null;

    public function addPenguji(): void
    {
        $this->validate([
            'sidang_id' => 'required|exists:sidang,id',
            'dosen_id' => 'nullable|exists:dosen,id',
            'namaEksternal' => 'nullable|string|max:150',
            'peran' => 'required|string|max:30',
            'nilai' => 'nullable|numeric|min:0|max:100',
        ]);

        if (! $this->dosen_id && trim($this->namaEksternal) === '') {
            $this->addError('dosen_id', 'Pilih dosen internal atau isi nama penguji eksternal.');

            return;
        }

        if ($this->dosen_id) {
            SidangPenguji::updateOrCreate(
                ['sidang_id' => $this->sidang_id, 'dosen_id' => $this->dosen_id],
                ['peran' => $this->peran, 'nilai' => $this->nilai, 'nama_eksternal' => null]
            );
        } else {
            // Eksternal: cocokkan by nama (unique DB hanya (sidang,dosen)).
            $nama = trim($this->namaEksternal);
            $ada = SidangPenguji::where('sidang_id', $this->sidang_id)
                ->where('nama_eksternal', $nama)
                ->first();

            if ($ada) {
                $ada->update(['peran' => $this->peran, 'nilai' => $this->nilai]);
            } else {
                SidangPenguji::create([
                    'sidang_id' => $this->sidang_id,
                    'dosen_id' => null,
                    'nama_eksternal' => $nama,
                    'peran' => $this->peran,
                    'nilai' => $this->nilai,
                ]);
            }
        }

        $this->reset(['dosen_id', 'namaEksternal', 'nilai']);
        $this->message = 'Nilai penguji tersimpan.';
        $this->recalc();
    }

    public function recalc(): void
    {
        if (! $this->sidang_id) {
            return;
        }
        $avg = SidangPenguji::where('sidang_id', $this->sidang_id)->avg('nilai');
        if ($avg !== null) {
            $nilai = round((float) $avg, 2);
            SidangModel::where('id', $this->sidang_id)->update(['nilai' => $nilai]);
            $this->rataRata = $nilai;
            $this->hurufMutu = BeritaAcaraService::hurufMutu($nilai);
        }
    }

    public function setStatus(): void
    {
        $this->validate([
            'sidang_id' => 'required|exists:sidang,id',
            'status' => 'required|in:lulus,revisi,gagal,terdaftar',
        ]);

        $berita = null;
        DB::transaction(function () use (&$berita): void {
            $sidang = SidangModel::whereKey($this->sidang_id)->lockForUpdate()->firstOrFail();
            $sidang->update(['status' => $this->status]);
            $avg = SidangPenguji::where('sidang_id', $sidang->id)->avg('nilai');
            if ($avg !== null) {
                $nilai = round((float) $avg, 2);
                $sidang->update(['nilai' => $nilai]);
                $this->rataRata = $nilai;
                $this->hurufMutu = BeritaAcaraService::hurufMutu($nilai);
            }
            $sidang->refresh();

            if ($this->status === 'lulus') {
                $sidang->judul()->update(['status' => 'lulus']);
                $berita = BeritaAcaraService::generate($sidang->fresh());
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'sidang.nilai.'.$this->status,
                'model' => SidangModel::class,
                'model_id' => $sidang->id,
                'created_at' => now(),
            ]);
        });
        $this->beritaAcara = $berita;

        $this->message = 'Status sidang diperbarui.';
    }

    public function render()
    {
        return view('livewire.sidang.nilai-sidang', [
            'sidangs' => SidangModel::with(['judul', 'pengujis.dosen'])->latest()->paginate(10),
            'dosens' => Dosen::where('status', 'aktif')->get(),
        ]);
    }
}
