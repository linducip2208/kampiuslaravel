<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beban_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->enum('kategori', ['pendidikan', 'penelitian', 'pengabdian', 'penunjang'])->default('pendidikan');
            $table->string('uraian');
            $table->unsignedInteger('sks')->default(0);
            $table->string('bukti')->nullable();
            $table->enum('status', ['draft', 'diajukan', 'disetujui', 'ditolak'])->default('diajukan');
            $table->timestamps();
            $table->index(['dosen_id', 'tahun_akademik_id']);
        });

        Schema::create('sk_mengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('nomor')->unique();
            $table->date('tanggal_terbit');
            $table->unsignedInteger('total_sks')->default(0);
            $table->text('daftar_kelas')->nullable();
            $table->timestamps();
            $table->unique(['dosen_id', 'tahun_akademik_id'], 'uq_sk_dosen_tahun');
        });

        Schema::create('izin_cuti_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->enum('jenis', ['izin', 'cuti', 'sakit'])->default('izin');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->text('alasan');
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan');
            $table->timestamps();
            $table->index(['dosen_id', 'status']);
        });

        Schema::create('aset', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->string('kategori')->default('umum');
            $table->string('lokasi')->nullable();
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->date('tgl_beli')->nullable();
            $table->unsignedBigInteger('nilai')->default(0);
            $table->timestamps();
        });

        Schema::create('aset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('jenis')->default('perbaikan');
            $table->text('deskripsi');
            $table->unsignedBigInteger('biaya')->default(0);
            $table->enum('status', ['diajukan', 'diproses', 'selesai'])->default('diajukan');
            $table->date('tanggal')->nullable();
            $table->timestamps();
        });

        Schema::create('helpdesk_sla', function (Blueprint $table) {
            $table->id();
            $table->string('kategori')->default('umum');
            $table->enum('prioritas', ['rendah', 'normal', 'tinggi', 'kritis'])->default('normal');
            $table->unsignedInteger('respon_jam')->default(24);
            $table->unsignedInteger('resolusi_jam')->default(72);
            $table->string('eskalasi_role')->default('superadmin');
            $table->timestamps();
            $table->unique(['kategori', 'prioritas'], 'uq_sla_kat_prioritas');
        });

        Schema::create('broadcast_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('role_target')->default('mahasiswa');
            $table->string('judul');
            $table->text('isi');
            $table->timestamps();
        });

        Schema::create('tracer_alumni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->year('tahun_lulus');
            $table->enum('status_kerja', ['bekerja', 'wirausaha', 'kuliah_lanjut', 'mencari_kerja', 'lainnya'])->default('bekerja');
            $table->string('perusahaan')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('rentang_gaji')->nullable();
            $table->text('masukan')->nullable();
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'tahun_lulus'], 'uq_tracer_mhs_tahun');
        });

        Schema::table('tiket', function (Blueprint $table) {
            $table->string('kategori')->default('umum')->after('isi');
            $table->enum('prioritas', ['rendah', 'normal', 'tinggi', 'kritis'])->default('normal')->after('kategori');
            $table->timestamp('due_respon_at')->nullable()->after('status');
            $table->timestamp('due_resolusi_at')->nullable()->after('due_respon_at');
            $table->boolean('dieskalasi')->default(false)->after('due_resolusi_at');
        });

        $defaults = [
            ['kategori' => 'umum', 'prioritas' => 'rendah', 'respon_jam' => 48, 'resolusi_jam' => 120, 'eskalasi_role' => 'admin_prodi'],
            ['kategori' => 'umum', 'prioritas' => 'normal', 'respon_jam' => 24, 'resolusi_jam' => 72, 'eskalasi_role' => 'admin_prodi'],
            ['kategori' => 'umum', 'prioritas' => 'tinggi', 'respon_jam' => 8, 'resolusi_jam' => 24, 'eskalasi_role' => 'superadmin'],
            ['kategori' => 'umum', 'prioritas' => 'kritis', 'respon_jam' => 2, 'resolusi_jam' => 8, 'eskalasi_role' => 'superadmin'],
        ];

        foreach ($defaults as $row) {
            DB::table('helpdesk_sla')->updateOrInsert(
                ['kategori' => $row['kategori'], 'prioritas' => $row['prioritas']],
                $row + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::table('tiket', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'prioritas', 'due_respon_at', 'due_resolusi_at', 'dieskalasi']);
        });
        Schema::dropIfExists('tracer_alumni');
        Schema::dropIfExists('broadcast_templates');
        Schema::dropIfExists('helpdesk_sla');
        Schema::dropIfExists('aset_maintenance');
        Schema::dropIfExists('aset');
        Schema::dropIfExists('izin_cuti_dosen');
        Schema::dropIfExists('sk_mengajar');
        Schema::dropIfExists('beban_dosen');
    }
};
