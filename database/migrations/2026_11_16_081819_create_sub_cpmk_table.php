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
        Schema::create('sub_cpmk', function (Blueprint $table) {
            // Breakdown CPMK menjadi sub-capaian terukur.
            $table->id();
            $table->foreignId('cpmk_id')->constrained('cpmk')->cascadeOnDelete();
            $table->string('kode', 20);
            $table->text('deskripsi');
            $table->decimal('bobot', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['cpmk_id', 'kode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_cpmk');
    }
};
