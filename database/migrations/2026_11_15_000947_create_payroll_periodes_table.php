<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-038: periode penggajian (draft → final terkunci). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periode', function (Blueprint $table): void {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->string('status', 20)->default('draft');
            $table->string('catatan', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periode');
    }
};
