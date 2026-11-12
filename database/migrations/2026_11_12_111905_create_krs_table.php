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
        Schema::create('krs', function (Blueprint $table) {
            $table->id();
            // Relasi ke mahasiswa & tahun_akademik (modul M1) tanpa hard FK.
            $table->foreignId('mahasiswa_id')->nullable()->index();
            $table->foreignId('tahun_akademik_id')->nullable()->index();
            // draft/diajukan/disetujui/ditolak
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedTinyInteger('total_sks')->default(0);
            $table->decimal('ipk_snapshot', 3, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'tahun_akademik_id']);
        });

        // FK ke tabel singular `mahasiswa` & `tahun_akademik` (modul M1);
        // diguard karena migrasi fondasi berjalan setelah migrasi ini.
        if (Schema::hasTable('mahasiswa')) {
            Schema::table('krs', function (Blueprint $table) {
                $table->foreign('mahasiswa_id')->references('id')->on('mahasiswa')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('tahun_akademik')) {
            Schema::table('krs', function (Blueprint $table) {
                $table->foreign('tahun_akademik_id')->references('id')->on('tahun_akademik')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('krs');
    }
};
