<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank soal per mata kuliah (fondasi kuis/ujian; belum dipakai mesin ujian).
     * Aditif murni: tabel baru, tanpa menyentuh tabel existing.
     */
    public function up(): void
    {
        if (Schema::hasTable('bank_soals')) {
            return;
        }

        Schema::create('bank_soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->cascadeOnDelete();
            $table->string('jenis', 20)->default('pg');
            $table->text('pertanyaan');
            $table->json('opsi')->nullable();
            $table->string('kunci', 5)->nullable();
            $table->unsignedTinyInteger('bobot')->default(1);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['matakuliah_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soals');
    }
};
