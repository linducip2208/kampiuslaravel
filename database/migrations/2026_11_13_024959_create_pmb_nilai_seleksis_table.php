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
        Schema::create('pmb_nilai_seleksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->index();
            // cbt/wawancara
            $table->string('jenis', 20)->index();
            $table->decimal('nilai', 5, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('penilai_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pendaftar_id', 'jenis']);
            $table->foreign('pendaftar_id')->references('id')->on('pmb_pendaftars')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pmb_nilai_seleksis');
    }
};
