<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index performa untuk dashboard & laporan (aditif, aman, guarded).
     */
    public function up(): void
    {
        $this->addIndex('pembayaran', ['status'], 'pembayaran_status_index');
        $this->addIndex('pembayaran', ['verified_at'], 'pembayaran_verified_at_index');
        $this->addIndex('pembayaran', ['status', 'verified_at'], 'pembayaran_status_verified_at_index');
        $this->addIndex('tagihan', ['status'], 'tagihan_status_index');
        $this->addIndex('tagihan', ['mahasiswa_id', 'status'], 'tagihan_mhs_status_index');
        $this->addIndex('krs', ['status'], 'krs_status_index');
        $this->addIndex('krs', ['tahun_akademik_id', 'status'], 'krs_ta_status_index');
        $this->addIndex('mahasiswa', ['status'], 'mahasiswa_status_index');
        $this->addIndex('pmb_pendaftars', ['status'], 'pmb_status_index');
    }

    public function down(): void
    {
        $this->dropIndex('pembayaran', 'pembayaran_status_verified_at_index');
        $this->dropIndex('pembayaran', 'pembayaran_verified_at_index');
        $this->dropIndex('pembayaran', 'pembayaran_status_index');
        $this->dropIndex('tagihan', 'tagihan_mhs_status_index');
        $this->dropIndex('tagihan', 'tagihan_status_index');
        $this->dropIndex('krs', 'krs_ta_status_index');
        $this->dropIndex('krs', 'krs_status_index');
        $this->dropIndex('mahasiswa', 'mahasiswa_status_index');
        $this->dropIndex('pmb_pendaftars', 'pmb_status_index');
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
