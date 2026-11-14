<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M-021: tepat 1 periode aktif.
 * Partial-unique "hanya 1 baris aktif=true" tidak portabel SQLite/MySQL,
 * jadi invarian ditegakkan di transaksi `TahunAkademik::aktifkan()`.
 * Migrasi ini: (1) index `aktif` untuk lookup cepat, (2) reparasi data
 * bila ada >1 aktif (pertahankan id terbaru).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tahun_akademik', function (Blueprint $table): void {
            $table->index('aktif', 'tahun_akademik_aktif_index');
        });

        $ids = DB::table('tahun_akademik')->where('aktif', true)->orderByDesc('id')->pluck('id');

        if ($ids->count() > 1) {
            DB::table('tahun_akademik')->whereIn('id', $ids->skip(1))->update(['aktif' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('tahun_akademik', function (Blueprint $table): void {
            $table->dropIndex('tahun_akademik_aktif_index');
        });
    }
};
