<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuti_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->text('alasan');
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['mahasiswa_id', 'status']);
        });

        // Samakan status mahasiswa menjadi enum di MySQL.
        // SQLite (dipakai phpunit :memory:) tidak mendukung MODIFY enum,
        // validasi nilai tetap ditegakkan di model + Livewire.
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('mahasiswa')) {
            DB::statement("ALTER TABLE `mahasiswa` MODIFY `status` ENUM('aktif','cuti','nonaktif','do','keluar','lulus') NOT NULL DEFAULT 'aktif'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cuti_pengajuan');

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('mahasiswa')) {
            DB::statement('ALTER TABLE `mahasiswa` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT \'aktif\'');
        }
    }
};
