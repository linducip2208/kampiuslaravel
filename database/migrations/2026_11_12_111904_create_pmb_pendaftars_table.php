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
        Schema::create('pmb_pendaftars', function (Blueprint $table) {
            $table->id();
            $table->string('no_daftar', 30)->unique();
            $table->string('nama', 100);
            $table->string('email', 100)->index();
            $table->string('hp', 20);
            $table->string('sekolah_asal', 150)->nullable();
            // Relasi ke prodi (prodi1_id/prodi2_id) & pmb_gelombangs tanpa hard FK
            // agar tidak bergantung pada urutan migrasi modul M1.
            $table->foreignId('prodi1_id')->nullable()->index();
            $table->foreignId('prodi2_id')->nullable()->index();
            $table->foreignId('gelombang_id')->nullable()->index();
            $table->json('berkas_json')->nullable();
            // draft/diajukan/diverifikasi/ditolak/lulus/tidak (string agar kompatibel sqlite & mysql)
            $table->string('status', 20)->default('diajukan')->index();
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreign('gelombang_id')->references('id')->on('pmb_gelombangs')->nullOnDelete();
            $table->timestamps();
        });

        // FK ke tabel singular `prodi` (modul M1); diguard karena migrasi
        // fondasi berjalan setelah migrasi ini pada fresh migrate.
        if (Schema::hasTable('prodi')) {
            Schema::table('pmb_pendaftars', function (Blueprint $table) {
                $table->foreign('prodi1_id')->references('id')->on('prodi')->nullOnDelete();
                $table->foreign('prodi2_id')->references('id')->on('prodi')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pmb_pendaftars');
    }
};
