<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\Prodi;
use App\Models\Ruang;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teknik = Fakultas::firstOrCreate(
            ['kode' => 'FT'],
            ['nama' => 'Fakultas Teknik']
        );

        $ekonomi = Fakultas::firstOrCreate(
            ['kode' => 'FE'],
            ['nama' => 'Fakultas Ekonomi']
        );

        $prodis = [
            ['fakultas_id' => $teknik->id, 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'kode' => 'TI'],
            ['fakultas_id' => $teknik->id, 'nama' => 'Teknik Elektro', 'jenjang' => 'S1', 'kode' => 'TE'],
            ['fakultas_id' => $ekonomi->id, 'nama' => 'Manajemen', 'jenjang' => 'S1', 'kode' => 'MJ'],
            ['fakultas_id' => $ekonomi->id, 'nama' => 'Akuntansi', 'jenjang' => 'S1', 'kode' => 'AK'],
        ];

        foreach ($prodis as $data) {
            Prodi::firstOrCreate(
                ['kode' => $data['kode']],
                $data + ['slug' => Str::slug($data['nama'])]
            );
        }

        $tahun = TahunAkademik::firstOrCreate(
            ['tahun' => '2026/2027', 'semester' => 'ganjil'],
            ['aktif' => true]
        );

        foreach (['A-101', 'A-102', 'B-201', 'B-202'] as $i => $namaRuang) {
            Ruang::firstOrCreate(
                ['nama' => $namaRuang],
                ['gedung' => 'Gedung '.chr(65 + intdiv($i, 2)), 'kapasitas' => 40]
            );
        }

        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@kampus.ac.id'],
            ['name' => 'Superadmin', 'password' => Hash::make('password')]
        );
        $superadmin->assignRole('superadmin');

        $prodiTi = Prodi::where('kode', 'TI')->firstOrFail();
        $ruang = Ruang::first();

        $pengantar = Matakuliah::firstOrCreate(
            ['kode' => 'TIF101'],
            [
                'prodi_id' => $prodiTi->id,
                'nama' => 'Pengantar Informatika',
                'sks' => 2,
                'semester' => 1,
            ]
        );

        Matakuliah::firstOrCreate(
            ['kode' => 'TIF102'],
            [
                'prodi_id' => $prodiTi->id,
                'nama' => 'Algoritma dan Pemrograman',
                'sks' => 3,
                'semester' => 1,
                'prasyarat_id' => $pengantar->id,
            ]
        );

        if (app()->environment('local', 'development')) {
            $dosenUser = User::factory()->create(['email' => 'dosen@kampus.ac.id']);
            $dosenUser->assignRole('dosen');
            $dosen = Dosen::factory()->create([
                'user_id' => $dosenUser->id,
                'prodi_id' => $prodiTi->id,
            ]);

            $mhsUser = User::factory()->create(['email' => 'mahasiswa@kampus.ac.id']);
            $mhsUser->assignRole('mahasiswa');
            Mahasiswa::factory()->create([
                'user_id' => $mhsUser->id,
                'prodi_id' => $prodiTi->id,
                'dosen_pa_id' => $dosen->id,
            ]);

            Kelas::factory()->create([
                'matakuliah_id' => $pengantar->id,
                'tahun_akademik_id' => $tahun->id,
                'dosen_id' => $dosen->id,
                'ruang_id' => $ruang?->id,
            ]);
        }
    }
}
