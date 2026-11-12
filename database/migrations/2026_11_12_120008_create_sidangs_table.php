<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judul_id')->constrained('skripsi_judul')->cascadeOnDelete();
            $table->enum('jenis', ['proposal', 'hasil', 'tutup'])->default('proposal');
            $table->dateTime('tanggal')->nullable();
            $table->foreignId('ruang_id')->nullable()->index();
            $table->string('berita_acara_path')->nullable();
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('catatan_revisi')->nullable();
            $table->enum('status', ['terdaftar', 'lulus', 'revisi', 'gagal'])->default('terdaftar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidang');
    }
};
