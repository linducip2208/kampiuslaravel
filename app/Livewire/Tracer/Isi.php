<?php

namespace App\Livewire\Tracer;

use App\Models\Alumni;
use App\Models\Mahasiswa;
use App\Models\TracerAlumni;
use Livewire\Component;

/**
 * M-055: kuesioner tracer publik — verifikasi NIM terdaftar sebagai alumni.
 * Tanpa RequiresRoles (komponen publik sejati, pola Lamar).
 */
class Isi extends Component
{
    public string $nim = '';

    public string $status_kerja = 'bekerja';

    public string $perusahaan = '';

    public string $jabatan = '';

    public string $rentang_gaji = '';

    public string $masukan = '';

    public string $message = '';

    public function kirim(): void
    {
        $this->validate([
            'nim' => 'required|string',
            'status_kerja' => 'required|in:bekerja,wirausaha,kuliah_lanjut,mencari_kerja,lainnya',
            'perusahaan' => 'nullable|string|max:150',
            'jabatan' => 'nullable|string|max:150',
            'rentang_gaji' => 'nullable|string|max:60',
            'masukan' => 'nullable|string|max:2000',
        ]);

        $mhs = Mahasiswa::where('nim', trim($this->nim))->first();

        if (! $mhs) {
            $this->addError('nim', 'NIM tidak terdaftar.');

            return;
        }

        $alumni = Alumni::where('mahasiswa_id', $mhs->id)->first();

        if (! $alumni) {
            $this->addError('nim', 'NIM belum tercatat sebagai alumni.');

            return;
        }

        TracerAlumni::updateOrCreate(
            ['mahasiswa_id' => $mhs->id],
            [
                'tahun_lulus' => $alumni->tahun_lulus,
                'status_kerja' => $this->status_kerja,
                'perusahaan' => $this->perusahaan ?: null,
                'jabatan' => $this->jabatan ?: null,
                'rentang_gaji' => $this->rentang_gaji ?: null,
                'masukan' => $this->masukan ?: null,
            ]
        );

        $this->reset(['nim', 'perusahaan', 'jabatan', 'rentang_gaji', 'masukan']);
        $this->message = 'Terima kasih! Jawaban tracer tersimpan.';
    }

    public function render()
    {
        return view('livewire.tracer.isi');
    }
}
