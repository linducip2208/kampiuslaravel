<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index Wave A: reuse-VA check (tagihan_id+status) + lookup cicilan.
     * Aditif, guarded, idempoten (pola add_performance_indexes).
     */
    public function up(): void
    {
        $this->addIndex('pembayaran', ['tagihan_id', 'status'], 'pembayaran_tagihan_status_index');
        $this->addIndex('tagihan_cicilan', ['tagihan_id', 'status'], 'cicilan_tagihan_status_index');
    }

    public function down(): void
    {
        $this->dropIndex('pembayaran', 'pembayaran_tagihan_status_index');
        $this->dropIndex('tagihan_cicilan', 'cicilan_tagihan_status_index');
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
