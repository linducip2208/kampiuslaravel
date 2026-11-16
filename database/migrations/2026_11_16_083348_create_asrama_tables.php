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
        Schema::create('asrama_kamar', function (Blueprint $table) {
            // Kamar asrama: kapasitas = jumlah bed; 1 bed 1 penghuni aktif.
            $table->id();
            $table->string('gedung', 50);
            $table->string('kode', 20)->unique();
            $table->unsignedInteger('kapasitas')->default(2);
            $table->unsignedBigInteger('iuran')->default(0);
            $table->timestamps();
        });

        Schema::create('asrama_huni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('asrama_kamar')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->date('masuk_at');
            $table->timestamps();
            // 1 mahasiswa hanya boleh menghuni 1 kamar aktif.
            $table->unique('mahasiswa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asrama_huni');
        Schema::dropIfExists('asrama_kamar');
    }
};
