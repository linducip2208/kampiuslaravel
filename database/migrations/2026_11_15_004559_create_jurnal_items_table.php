<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** M-052: baris jurnal (debet xor kredit per baris disarankan; total wajib seimbang). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_item', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jurnal_id')->constrained('jurnal_umum')->cascadeOnDelete();
            $table->foreignId('coa_id')->constrained('coa')->cascadeOnDelete();
            $table->unsignedBigInteger('debet')->default(0);
            $table->unsignedBigInteger('kredit')->default(0);
            $table->timestamps();
            $table->index('coa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_item');
    }
};
