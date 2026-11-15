<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC batch 4: delegasi peran berbatas waktu.
 * Penerapan via command terjadwal (bukan middleware), agar:
 * - pemberian/pencabutan tercatat di audit_logs (sumber kebenaran),
 * - pencabutan tidak pernah menghapus role pemberian manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegasi_akses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dari_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ke_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 40);
            $table->date('mulai_at');
            $table->date('sampai_at');
            $table->string('status', 20)->default('aktif');
            $table->string('keperluan', 500)->nullable();
            $table->timestamp('diterapkan_pada')->nullable();
            $table->timestamps();
            $table->index(['ke_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegasi_akses');
    }
};
