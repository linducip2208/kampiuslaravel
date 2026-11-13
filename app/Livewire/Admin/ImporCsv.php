<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Nilai;
use App\Models\Prodi;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImporCsv extends Component
{
    use RequiresRoles;
    use WithFileUploads;

    public array $allowedRoles = ['superadmin', 'admin_prodi'];

    public string $jenis = 'mahasiswa';

    public $file;

    public string $message = '';

    /** @var list<string> */
    public array $gagalBaris = [];

    public function impor(): void
    {
        $this->validate(['file' => 'required|file|max:5120|mimes:csv,txt']);

        $path = $this->file->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->addError('file', 'File tidak bisa dibaca.');

            return;
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle, 0, ';') ?: []);
        $ok = 0;
        $gagal = [];

        DB::transaction(function () use ($handle, $header, &$ok, &$gagal) {
            $baris = 1;
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $baris++;
                $data = array_combine($header, array_pad($row, count($header), ''));

                try {
                    match ($this->jenis) {
                        'matakuliah' => $this->imporMk($data),
                        'dosen' => $this->imporDosen($data),
                        'krs' => $this->imporKrs($data),
                        'nilai' => $this->imporNilai($data),
                        'tagihan' => $this->imporTagihan($data),
                        default => $this->imporMahasiswa($data),
                    };
                    $ok++;
                } catch (\Throwable $e) {
                    $gagal[] = "Baris {$baris}: ".$e->getMessage();
                }
            }
        });

        fclose($handle);
        $this->gagalBaris = array_slice($gagal, 0, 20);
        $this->message = "Impor selesai: {$ok} berhasil, ".count($gagal).' gagal.';
        $this->reset('file');
    }

    /** @param array<string, string> $data */
    private function imporMahasiswa(array $data): void
    {
        $nim = trim($data['nim'] ?? '');
        $nama = trim($data['nama'] ?? '');
        $email = trim($data['email'] ?? '');
        $prodiKode = trim($data['prodi_kode'] ?? '');

        if ($nim === '' || $nama === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $prodiKode === '') {
            throw new \RuntimeException('nim/nama/email/prodi_kode wajib valid.');
        }

        $prodi = Prodi::where('kode', $prodiKode)->firstOrFail();

        if (Mahasiswa::where('nim', $nim)->exists() || User::where('email', $email)->exists()) {
            throw new \RuntimeException("NIM/email {$nim} sudah terdaftar.");
        }

        $user = User::create(['name' => $nama, 'email' => $email, 'password' => Hash::make(Str::random(12))]);
        $user->assignRole('mahasiswa');

        Mahasiswa::create([
            'user_id' => $user->id,
            'nim' => $nim,
            'prodi_id' => $prodi->id,
            'angkatan' => (int) ($data['angkatan'] ?? now()->year),
            'status' => 'aktif',
        ]);
    }

    /** @param array<string, string> $data */
    private function imporMk(array $data): void
    {
        $kode = trim($data['kode'] ?? '');
        $nama = trim($data['nama'] ?? '');
        $prodiKode = trim($data['prodi_kode'] ?? '');

        if ($kode === '' || $nama === '' || $prodiKode === '') {
            throw new \RuntimeException('kode/nama/prodi_kode wajib diisi.');
        }

        $prodi = Prodi::where('kode', $prodiKode)->firstOrFail();

        Matakuliah::updateOrCreate(
            ['kode' => $kode, 'prodi_id' => $prodi->id],
            ['nama' => $nama, 'sks' => max(1, (int) ($data['sks'] ?? 2)), 'semester' => max(1, (int) ($data['semester'] ?? 1))]
        );
    }

    /** @param array<string, string> $data */
    private function imporDosen(array $data): void
    {
        $nidn = trim($data['nidn'] ?? '');
        $nama = trim($data['nama'] ?? '');
        $email = trim($data['email'] ?? '');
        $prodiKode = trim($data['prodi_kode'] ?? '');

        if ($nidn === '' || $nama === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $prodiKode === '') {
            throw new \RuntimeException('nidn/nama/email/prodi_kode wajib valid.');
        }

        $prodi = Prodi::where('kode', $prodiKode)->firstOrFail();

        if (Dosen::where('nidn', $nidn)->exists() || User::where('email', $email)->exists()) {
            throw new \RuntimeException("NIDN/email {$nidn} sudah terdaftar.");
        }

        $user = User::create(['name' => $nama, 'email' => $email, 'password' => Hash::make(Str::random(12))]);
        $user->assignRole('dosen');

        Dosen::create([
            'user_id' => $user->id,
            'nidn' => $nidn,
            'prodi_id' => $prodi->id,
            'status' => 'aktif',
        ]);
    }

    /** @param array<string, string> $data */
    private function imporKrs(array $data): void
    {
        $nim = trim($data['nim'] ?? '');
        $kelasId = (int) trim($data['kelas_id'] ?? '');

        if ($nim === '' || $kelasId <= 0) {
            throw new \RuntimeException('nim/kelas_id wajib diisi.');
        }

        $mahasiswa = Mahasiswa::where('nim', $nim)->firstOrFail();
        $kelas = Kelas::findOrFail($kelasId);
        $tahun = $this->resolveTahun($data);

        $krs = Krs::firstOrCreate(
            ['mahasiswa_id' => $mahasiswa->id, 'tahun_akademik_id' => $tahun->id],
            ['status' => Krs::STATUS_DIAJUKAN, 'total_sks' => 0]
        );

        KrsDetail::firstOrCreate(
            ['krs_id' => $krs->id, 'kelas_id' => $kelas->id],
            ['sks' => (int) ($kelas->matakuliah->sks ?? 2), 'status' => 'aktif']
        );

        $krs->update(['total_sks' => (int) $krs->details()->sum('sks')]);
    }

    /** @param array<string, string> $data */
    private function imporNilai(array $data): void
    {
        $nim = trim($data['nim'] ?? '');
        $kelasId = (int) trim($data['kelas_id'] ?? '');

        if ($nim === '' || $kelasId <= 0) {
            throw new \RuntimeException('nim/kelas_id wajib diisi.');
        }

        $mahasiswa = Mahasiswa::where('nim', $nim)->firstOrFail();
        Kelas::findOrFail($kelasId);

        $nilai = Nilai::firstOrNew([
            'mahasiswa_id' => $mahasiswa->id,
            'kelas_id' => $kelasId,
        ]);

        if ($nilai->exists && (bool) $nilai->is_locked) {
            throw new \RuntimeException("Nilai {$nim} kelas {$kelasId} terkunci.");
        }

        foreach (['tugas', 'uts', 'uas', 'kehadiran'] as $komponen) {
            $raw = trim($data[$komponen] ?? '');
            if ($raw !== '') {
                $angka = (float) $raw;
                if ($angka < 0 || $angka > 100) {
                    throw new \RuntimeException("{$komponen} harus 0-100.");
                }
                $nilai->{$komponen} = $angka;
            }
        }

        $nilai->recalculate();
        $nilai->save();
    }

    /** @param array<string, string> $data */
    private function imporTagihan(array $data): void
    {
        $nim = trim($data['nim'] ?? '');
        $jenis = trim($data['jenis'] ?? 'UKT');
        $nominal = (int) trim($data['nominal'] ?? '');

        if ($nim === '' || $nominal <= 0) {
            throw new \RuntimeException('nim/nominal wajib valid.');
        }

        if (! in_array($jenis, ['UKT', 'SPP', 'PMB', 'lain'], true)) {
            throw new \RuntimeException('jenis harus UKT/SPP/PMB/lain.');
        }

        $mahasiswa = Mahasiswa::where('nim', $nim)->firstOrFail();
        $tahun = $this->resolveTahun($data);

        $tagihan = Tagihan::firstOrNew([
            'mahasiswa_id' => $mahasiswa->id,
            'tahun_akademik_id' => $tahun->id,
            'jenis' => $jenis,
        ]);

        // Jangan timpa tagihan yang sudah ada pembayaran/terbayar (anti-korupsi data keuangan).
        if ($tagihan->exists && ((int) ($tagihan->terbayar ?? 0) > 0 || $tagihan->pembayarans()->exists())) {
            throw new \RuntimeException("Tagihan {$nim} {$jenis} sudah memiliki pembayaran — nominal tidak ditimpa.");
        }

        $tagihan->nominal = $nominal;
        if (! $tagihan->exists) {
            $tagihan->terbayar = 0;
            $tagihan->status = 'belum_bayar';
        }
        $tagihan->save();
    }

    /** @param array<string, string> $data */
    private function resolveTahun(array $data): TahunAkademik
    {
        $tahun = trim($data['tahun'] ?? '');
        $semester = trim($data['semester'] ?? 'ganjil');

        if ($tahun === '' || ! in_array($semester, ['ganjil', 'genap'], true)) {
            throw new \RuntimeException('tahun/semester wajib valid (semester: ganjil/genap).');
        }

        return TahunAkademik::firstOrCreate(
            ['tahun' => $tahun, 'semester' => $semester],
            ['aktif' => false]
        );
    }

    public function render()
    {
        return view('livewire.admin.impor-csv');
    }
}
