<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_baca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('model', 80);
            $table->unsignedBigInteger('model_id');
            $table->timestamp('dibaca_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_baca');
    }
};
