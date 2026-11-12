<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pertemuan_id')->index();
            $table->unsignedBigInteger('mahasiswa_id')->index();
            $table->string('status', 10)->default('hadir');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->unique(['pertemuan_id', 'mahasiswa_id']);
        });

        Schema::create('jurnal_dosen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pertemuan_id')->unique();
            $table->text('realisasi');
            $table->text('kendala')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_dosen');
        Schema::dropIfExists('absensi');
    }
};
