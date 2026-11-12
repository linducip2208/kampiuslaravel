<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bimbingan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judul_id')->constrained('skripsi_judul')->cascadeOnDelete();
            $table->foreignId('dosen_id')->nullable()->index();
            $table->text('catatan');
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'disetujui', 'revisi'])->default('pending');
            $table->date('tanggal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bimbingan');
    }
};
