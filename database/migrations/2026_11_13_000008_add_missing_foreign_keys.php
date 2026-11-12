<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi FK yang terlewat karena urutan migrasi antar-modul
 * (forward-only; tidak mengubah data).
 */
return new class extends Migration
{
    /** @var array<int, array{table: string, column: string, ref: string, on: string}> */
    private array $keys = [
        ['table' => 'pmb_gelombangs', 'column' => 'tahun_akademik_id', 'ref' => 'tahun_akademik', 'on' => 'null'],
        ['table' => 'pmb_pendaftars', 'column' => 'prodi1_id', 'ref' => 'prodi', 'on' => 'null'],
        ['table' => 'pmb_pendaftars', 'column' => 'prodi2_id', 'ref' => 'prodi', 'on' => 'null'],
        ['table' => 'krs', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'cascade'],
        ['table' => 'krs', 'column' => 'tahun_akademik_id', 'ref' => 'tahun_akademik', 'on' => 'null'],
        ['table' => 'krs_detail', 'column' => 'kelas_id', 'ref' => 'kelas', 'on' => 'null'],
        ['table' => 'pertemuan', 'column' => 'kelas_id', 'ref' => 'kelas', 'on' => 'restrict'],
        ['table' => 'materi', 'column' => 'pertemuan_id', 'ref' => 'pertemuan', 'on' => 'cascade'],
        ['table' => 'tugas', 'column' => 'kelas_id', 'ref' => 'kelas', 'on' => 'null'],
        ['table' => 'tugas', 'column' => 'pertemuan_id', 'ref' => 'pertemuan', 'on' => 'null'],
        ['table' => 'tugas_kumpul', 'column' => 'tugas_id', 'ref' => 'tugas', 'on' => 'cascade'],
        ['table' => 'tugas_kumpul', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'cascade'],
        ['table' => 'quiz', 'column' => 'kelas_id', 'ref' => 'kelas', 'on' => 'restrict'],
        ['table' => 'quiz_soal', 'column' => 'quiz_id', 'ref' => 'quiz', 'on' => 'cascade'],
        ['table' => 'quiz_jawaban', 'column' => 'quiz_soal_id', 'ref' => 'quiz_soal', 'on' => 'cascade'],
        ['table' => 'quiz_jawaban', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'cascade'],
        ['table' => 'absensi', 'column' => 'pertemuan_id', 'ref' => 'pertemuan', 'on' => 'cascade'],
        ['table' => 'absensi', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'cascade'],
        ['table' => 'jurnal_dosen', 'column' => 'pertemuan_id', 'ref' => 'pertemuan', 'on' => 'cascade'],
        ['table' => 'nilai', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'restrict'],
        ['table' => 'nilai', 'column' => 'kelas_id', 'ref' => 'kelas', 'on' => 'restrict'],
        ['table' => 'edom', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'cascade'],
        ['table' => 'edom', 'column' => 'kelas_id', 'ref' => 'kelas', 'on' => 'cascade'],
        ['table' => 'edom', 'column' => 'tahun_akademik_id', 'ref' => 'tahun_akademik', 'on' => 'null'],
        ['table' => 'skripsi_judul', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'restrict'],
        ['table' => 'skripsi_judul', 'column' => 'pembimbing1_id', 'ref' => 'dosen', 'on' => 'null'],
        ['table' => 'skripsi_judul', 'column' => 'pembimbing2_id', 'ref' => 'dosen', 'on' => 'null'],
        ['table' => 'bimbingan', 'column' => 'dosen_id', 'ref' => 'dosen', 'on' => 'null'],
        ['table' => 'sidang', 'column' => 'ruang_id', 'ref' => 'ruang', 'on' => 'null'],
        ['table' => 'sidang_penguji', 'column' => 'dosen_id', 'ref' => 'dosen', 'on' => 'restrict'],
        ['table' => 'repository', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'restrict'],
        ['table' => 'tagihan', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'restrict'],
        ['table' => 'tagihan', 'column' => 'tahun_akademik_id', 'ref' => 'tahun_akademik', 'on' => 'null'],
        ['table' => 'surat', 'column' => 'mahasiswa_id', 'ref' => 'mahasiswa', 'on' => 'restrict'],
        ['table' => 'pengumuman', 'column' => 'prodi_id', 'ref' => 'prodi', 'on' => 'null'],
        ['table' => 'pembayaran', 'column' => 'verified_by', 'ref' => 'users', 'on' => 'null'],
    ];

    public function up(): void
    {
        foreach ($this->keys as $key) {
            if (! Schema::hasTable($key['table'])) {
                continue;
            }

            Schema::table($key['table'], function (Blueprint $table) use ($key) {
                $fk = $table->foreign($key['column'])->references('id')->on($key['ref']);

                match ($key['on']) {
                    'cascade' => $fk->cascadeOnDelete(),
                    'null' => $fk->nullOnDelete(),
                    default => $fk->restrictOnDelete(),
                };
            });
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $key) {
            if (! Schema::hasTable($key['table'])) {
                continue;
            }

            Schema::table($key['table'], function (Blueprint $table) use ($key) {
                $table->dropForeign([$key['column']]);
            });
        }
    }
};
