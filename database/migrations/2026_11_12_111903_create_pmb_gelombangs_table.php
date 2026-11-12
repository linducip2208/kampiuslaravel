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
        Schema::create('pmb_gelombangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            // Relasi ke tahun_akademik (modul M1, mungkin belum ada saat migrasi ini jalan).
            // Tanpa constraint FK agar urutan migrasi antar modul tidak saling mengunci.
            $table->foreignId('tahun_akademik_id')->nullable()->index();
            $table->date('tgl_buka')->nullable();
            $table->date('tgl_tutup')->nullable();
            $table->unsignedInteger('kuota')->default(0);
            $table->unsignedBigInteger('biaya')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // FK ke tabel singular modul M1; diguard karena migrasi fondasi
        // (2026_11_13_*) berjalan setelah migrasi ini pada fresh migrate.
        if (Schema::hasTable('tahun_akademik')) {
            Schema::table('pmb_gelombangs', function (Blueprint $table) {
                $table->foreign('tahun_akademik_id')->references('id')->on('tahun_akademik')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pmb_gelombangs');
    }
};
