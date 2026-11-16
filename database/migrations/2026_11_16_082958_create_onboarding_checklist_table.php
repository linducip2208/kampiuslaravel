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
        Schema::create('onboarding_checklist', function (Blueprint $table) {
            // Checklist onboarding per lamaran yang diterima.
            $table->id();
            $table->foreignId('lamaran_id')->constrained('lamaran_kerja')->cascadeOnDelete();
            $table->string('item', 150);
            $table->boolean('selesai')->default(false);
            $table->timestamps();
            $table->unique(['lamaran_id', 'item']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_checklist');
    }
};
