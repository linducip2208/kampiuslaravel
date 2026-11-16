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
        Schema::table('knowledge_base', function (Blueprint $table) {
            // Sumber RAG per prodi: null = global (terlihat semua prodi).
            $table->foreignId('prodi_id')->nullable()->after('id')->constrained('prodi')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prodi_id');
        });
    }
};
