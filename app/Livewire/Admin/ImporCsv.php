<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRoles;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Prodi;
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
                    $this->jenis === 'matakuliah' ? $this->imporMk($data) : $this->imporMahasiswa($data);
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

    public function render()
    {
        return view('livewire.admin.impor-csv');
    }
}
