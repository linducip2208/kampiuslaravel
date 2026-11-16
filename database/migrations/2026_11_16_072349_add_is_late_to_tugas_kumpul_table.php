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
        Schema::table('tugas_kumpul', function (Blueprint $table) {
            // Kolom yang selama ini diasumsikan AtRiskService/ChatbotService
            // (query is_late gagal tanpa kolom ini → daftar at-risk kosong).
            $table->boolean('is_late')->default(false)->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tugas_kumpul', function (Blueprint $table) {
            $table->dropColumn('is_late');
        });
    }
};
