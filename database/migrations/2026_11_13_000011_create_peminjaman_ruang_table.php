<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_ruang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruang_id')->constrained('ruang')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('keperluan', 255)->nullable();
            $table->string('status', 20)->default('diajukan');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
            $table->index(['ruang_id', 'tanggal']);
            $table->index(['status']);
        });

        Schema::table('jurnal_dosen', function (Blueprint $table) {
            if (! Schema::hasColumn('jurnal_dosen', 'dosen_id')) {
                $table->foreignId('dosen_id')->nullable()->after('pertemuan_id')->constrained('dosen')->nullOnDelete();
            }
            if (! Schema::hasColumn('jurnal_dosen', 'status_hadir')) {
                $table->string('status_hadir', 10)->default('hadir')->after('realisasi');
            }
            if (! Schema::hasColumn('jurnal_dosen', 'tanggal')) {
                $table->date('tanggal')->nullable()->after('status_hadir');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_dosen', function (Blueprint $table) {
            if (Schema::hasColumn('jurnal_dosen', 'dosen_id')) {
                try {
                    $table->dropForeign(['dosen_id']);
                } catch (Throwable) {
                }
            }
            foreach (['dosen_id', 'status_hadir', 'tanggal'] as $col) {
                if (Schema::hasColumn('jurnal_dosen', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('peminjaman_ruang');
    }
};
