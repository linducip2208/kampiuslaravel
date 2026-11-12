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
        Schema::create('krs_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krs_id')->index();
            // Relasi ke kelas (modul M1) tanpa hard FK.
            $table->foreignId('kelas_id')->nullable()->index();
            // Snapshot SKS matakuliah saat pengajuan (agar cetak KRS tetap valid).
            $table->unsignedTinyInteger('sks')->default(0);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->foreign('krs_id')->references('id')->on('krs')->cascadeOnDelete();
            $table->unique(['krs_id', 'kelas_id']);
        });

        // FK ke tabel singular `kelas` (modul M1); diguard karena migrasi
        // fondasi berjalan setelah migrasi ini pada fresh migrate.
        if (Schema::hasTable('kelas')) {
            Schema::table('krs_detail', function (Blueprint $table) {
                $table->foreign('kelas_id')->references('id')->on('kelas')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('krs_detail');
    }
};
