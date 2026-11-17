<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index performa batch 26 (aditif, guarded, idempoten).
     *
     * Audit 2026-11-17 terhadap komponen batch ide 6-26:
     * - klinik_kunjungan(mahasiswa_id, tanggal): SUDAH ADA.
     * - asrama_huni: kamar_id + mahasiswa_id FK otomatis terindeks,
     *   mahasiswa_id juga UNIQUE — tidak perlu tambahan.
     * - wali_mahasiswa: user_id FK otomatis terindeks + unique
     *   (user_id, mahasiswa_id) — tidak perlu tambahan.
     * - spmi_siklus: prodi_id FK otomatis terindeks + unique
     *   (prodi_id, tahun, tahap) — tidak perlu tambahan.
     * - sub_cpmk: cpmk_id FK otomatis terindeks + unique
     *   (cpmk_id, kode) — tidak perlu tambahan.
     * - krs_revisi: krs_id FK otomatis terindeks — tidak perlu tambahan.
     * - aset_opname: FK otomatis terindeks + index (aset_id, created_at)
     *   SUDAH ADA — tidak perlu tambahan.
     * - tunjangan_template: role UNIQUE — tidak perlu tambahan.
     * - hutang_dagang(pemasok_id, status), jurnal_umum(tahun_akademik_id,
     *   tanggal), akreditasi_dokumen(prodi_id, status),
     *   lowongan_kerja(status, tgl_tutup): SUDAH ADA.
     *
     * Yang benar-benar belum ada dan dipakai App\Livewire\Admin\Tracer
     * (filter status_kerja + GROUP BY ringkasan + GROUP BY/ORDER BY tren
     * tahun_lulus): tracer_alumni.status_kerja dan tahun_lulus.
     */
    public function up(): void
    {
        $this->addIndex('tracer_alumni', ['status_kerja'], 'tracer_status_kerja_index');
        $this->addIndex('tracer_alumni', ['tahun_lulus'], 'tracer_tahun_lulus_index');
    }

    public function down(): void
    {
        $this->dropIndex('tracer_alumni', 'tracer_tahun_lulus_index');
        $this->dropIndex('tracer_alumni', 'tracer_status_kerja_index');
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name): void {
                $table->index($columns, $name);
            });
        } catch (Throwable) {
            // Index sudah ada (rerun / DB berbeda) — aman diabaikan.
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $table) use ($name): void {
                $table->dropIndex($name);
            });
        } catch (Throwable) {
            // Belum ada — aman diabaikan.
        }
    }
};
