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
        Schema::create('krs_revisi', function (Blueprint $table) {
            // Snapshot rincian KRS sebelum ditimpa submit ulang (riwayat revisi).
            $table->id();
            $table->foreignId('krs_id')->constrained('krs')->cascadeOnDelete();
            $table->unsignedTinyInteger('total_sks')->default(0);
            $table->decimal('ipk_snapshot', 3, 2)->nullable();
            $table->json('rincian')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('krs_revisi');
    }
};
