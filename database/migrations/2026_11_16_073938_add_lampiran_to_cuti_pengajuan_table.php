<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuti_pengajuan', function (Blueprint $table) {
            // Lampiran pendukung pengajuan cuti (surat sakit/tugas, opsional).
            $table->string('lampiran_path')->nullable()->after('alasan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuti_pengajuan', function (Blueprint $table) {
            $table->dropColumn('lampiran_path');
        });
    }
};
