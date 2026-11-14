<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-042: placeholder digabung ke migrasi hibah (tetap 1 file jalan). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hibah_pencairan')) {
            Schema::create('hibah_pencairan', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('hibah_id')->constrained('hibah_riset')->cascadeOnDelete();
                $table->unsignedInteger('tahap')->default(1);
                $table->unsignedBigInteger('nominal')->default(0);
                $table->date('tanggal')->nullable();
                $table->string('keterangan', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Dicakup migrasi hibah.
    }
};
