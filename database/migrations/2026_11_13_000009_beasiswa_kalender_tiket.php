<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('jenis', ['persen', 'nominal'])->default('persen');
            $table->unsignedInteger('nilai')->default(0);
            $table->unsignedInteger('kuota')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('mahasiswa_beasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('beasiswa_id')->constrained('beasiswa')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'beasiswa_id', 'tahun_akademik_id'], 'uq_mhs_beasiswa_tahun');
        });

        Schema::table('tagihan', function (Blueprint $table) {
            $table->foreignId('beasiswa_id')->nullable()->after('jenis')->constrained('beasiswa')->nullOnDelete();
            $table->unsignedBigInteger('diskon')->default(0)->after('nominal');
        });

        Schema::create('kalender_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->string('judul');
            $table->enum('jenis', ['kuliah', 'uts', 'uas', 'libur', 'pembayaran', 'pmb', 'wisuda', 'lain'])->default('lain');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('tiket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subjek');
            $table->text('isi');
            $table->enum('status', ['terbuka', 'diproses', 'selesai'])->default('terbuka');
            $table->timestamps();
        });

        Schema::create('tiket_balasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tiket')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropForeign(['beasiswa_id']);
            $table->dropColumn(['beasiswa_id', 'diskon']);
        });
        Schema::dropIfExists('tiket_balasan');
        Schema::dropIfExists('tiket');
        Schema::dropIfExists('kalender_events');
        Schema::dropIfExists('mahasiswa_beasiswa');
        Schema::dropIfExists('beasiswa');
    }
};
