<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->unsignedBigInteger('kelas_id')->index();
            $table->decimal('tugas', 5, 2)->default(0);
            $table->decimal('uts', 5, 2)->default(0);
            $table->decimal('uas', 5, 2)->default(0);
            $table->decimal('kehadiran', 5, 2)->default(0);
            $table->decimal('na', 5, 2)->default(0);
            $table->string('huruf', 2)->default('E');
            $table->decimal('bobot', 3, 1)->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'kelas_id']);
        });

        Schema::create('edom', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->unsignedBigInteger('kelas_id')->index();
            $table->unsignedBigInteger('tahun_akademik_id')->index()->nullable();
            $table->unsignedTinyInteger('skor')->default(0);
            $table->text('kritik')->nullable();
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'kelas_id'], 'uq_edom_mhs_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edom');
        Schema::dropIfExists('nilai');
    }
};
