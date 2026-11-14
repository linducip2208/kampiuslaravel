<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-034: utang usaha ke pemasok + pelunasan bertahap (mirror piutang mahasiswa). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hutang_dagang', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pemasok_id')->constrained('pemasok')->cascadeOnDelete();
            $table->string('nomor', 60)->unique();
            $table->string('uraian', 255);
            $table->unsignedBigInteger('nominal')->default(0);
            $table->unsignedBigInteger('terbayar')->default(0);
            $table->date('jatuh_tempo')->nullable();
            $table->string('status', 20)->default('belum_bayar');
            $table->timestamps();
            $table->index(['pemasok_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hutang_dagang');
    }
};
