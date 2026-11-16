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
        Schema::create('tunjangan_template', function (Blueprint $table) {
            // Default tunjangan per peran (dosen/tendik/...); dipakai saat isi slip.
            $table->id();
            $table->string('role', 50)->unique();
            $table->unsignedBigInteger('nominal')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tunjangan_template');
    }
};
