<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jurnal akuntansi sederhana (double-entry satu baris: debet + kredit).
        if (! Schema::hasTable('jurnal_keuangan')) {
            Schema::create('jurnal_keuangan', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal');
                $table->foreignId('pembayaran_id')->nullable()->constrained('pembayaran')->nullOnDelete();
                $table->foreignId('tagihan_id')->nullable()->constrained('tagihan')->nullOnDelete();
                $table->enum('jenis', ['penerimaan', 'refund', 'penyesuaian'])->default('penerimaan');
                $table->string('akun_debet', 60);
                $table->string('akun_kredit', 60);
                $table->unsignedBigInteger('nominal');
                $table->string('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->index();
                $table->timestamps();

                $table->unique(['pembayaran_id', 'jenis'], 'jurnal_bayar_jenis_unique');
            });
        }

        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'jatuh_tempo')) {
                $table->date('jatuh_tempo')->nullable()->after('status');
            }
            if (! Schema::hasColumn('tagihan', 'notif_h7_at')) {
                $table->timestamp('notif_h7_at')->nullable()->after('jatuh_tempo');
            }
            if (! Schema::hasColumn('tagihan', 'notif_hp7_at')) {
                $table->timestamp('notif_hp7_at')->nullable()->after('notif_h7_at');
            }
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            if (! Schema::hasColumn('pembayaran', 'qr_payload')) {
                $table->text('qr_payload')->nullable()->after('va_number');
            }
            if (! Schema::hasColumn('pembayaran', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('qr_payload');
            }
            if (! Schema::hasColumn('pembayaran', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('verified_at');
            }
            if (! Schema::hasColumn('pembayaran', 'refund_reason')) {
                $table->string('refund_reason')->nullable()->after('refunded_at');
            }
            if (! Schema::hasColumn('pembayaran', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('refund_reason');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'no_wa')) {
                $table->string('no_wa', 20)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_keuangan');

        foreach ([
            ['tagihan', ['notif_hp7_at', 'notif_h7_at', 'jatuh_tempo']],
            ['pembayaran', ['reconciled_at', 'refund_reason', 'refunded_at', 'expires_at', 'qr_payload']],
            ['users', ['no_wa']],
        ] as [$table, $columns]) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table->getTable(), $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
