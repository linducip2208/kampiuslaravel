<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-038: slip gaji per pegawai per periode (total tersimpan). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_slip', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('periode_id')->constrained('payroll_periode')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('gaji_pokok')->default(0);
            $table->unsignedBigInteger('tunjangan')->default(0);
            $table->unsignedBigInteger('potongan')->default(0);
            $table->unsignedBigInteger('total_bersih')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->unique(['periode_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_slip');
    }
};
