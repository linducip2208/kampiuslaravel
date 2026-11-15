<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Perpustakaan: sirkulasi pinjam-kembali + denda keterlambatan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpus_pinjam', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('eksemplar_id')->constrained('perpus_eksemplar')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tgl_pinjam');
            $table->date('jatuh_tempo');
            $table->date('tgl_kembali')->nullable();
            $table->unsignedBigInteger('denda_rp')->default(0);
            $table->string('status', 20)->default('pinjam');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perpus_pinjam');
    }
};
