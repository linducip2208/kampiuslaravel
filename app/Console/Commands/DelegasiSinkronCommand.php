<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\DelegasiAkses;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * RBAC batch 4: terapkan/p Cabut delegasi peran berbatas waktu.
 * - Beri: hanya bila periode aktif DAN role belum dimiliki.
 * - Cabut: hanya bila pemberian tercatat dari delegasi ini (diterapkan_pada)
 *   DAN tidak ada pemberian manual sesudahnya (aman dari pencabutan liar).
 * Idempoten: aman dijalankan tiap hari via scheduler.
 */
class DelegasiSinkronCommand extends Command
{
    protected $signature = 'delegasi:sinkron';

    protected $description = 'Terapkan delegasi peran aktif & cabut yang kedaluwarsa (aman, teraudit).';

    public function handle(): int
    {
        $diberi = 0;
        $dicabut = 0;

        foreach (DelegasiAkses::with('ke')->orderBy('id')->cursor() as $d) {
            if ($d->status !== 'aktif') {
                continue;
            }

            $user = $d->ke;

            if (! $user) {
                continue;
            }

            if ($d->aktifHariIni()) {
                if (! $user->hasRole($d->role)) {
                    DB::transaction(function () use ($d, $user, &$diberi): void {
                        $user->assignRole($d->role);
                        $d->update(['diterapkan_pada' => now()]);
                        AuditLog::create([
                            'user_id' => null,
                            'action' => 'delegasi.diterapkan',
                            'model' => DelegasiAkses::class,
                            'model_id' => $d->id,
                            'diff_json' => ['ke_user_id' => $user->id, 'role' => $d->role],
                            'created_at' => now(),
                        ]);
                        $diberi++;
                    });
                }

                continue;
            }

            // Kedaluwarsa: cabut hanya bila kami yang memberi + tanpa grant manual sesudahnya.
            if ($d->diterapkan_pada && $user->hasRole($d->role) && ! $this->adaGrantManualSesudahnya($user->id, $d->role, $d->diterapkan_pada)) {
                DB::transaction(function () use ($d, $user, &$dicabut): void {
                    $user->removeRole($d->role);
                    AuditLog::create([
                        'user_id' => null,
                        'action' => 'delegasi.dicabut',
                        'model' => DelegasiAkses::class,
                        'model_id' => $d->id,
                        'diff_json' => ['ke_user_id' => $user->id, 'role' => $d->role],
                        'created_at' => now(),
                    ]);
                    $dicabut++;
                });
            }
        }

        $this->info("Delegasi diterapkan: {$diberi}; dicabut (kedaluwarsa): {$dicabut}.");

        return self::SUCCESS;
    }

    private function adaGrantManualSesudahnya(int $userId, string $role, \DateTimeInterface $sejak): bool
    {
        // >= (bukan >): SQLite/MySQL menyimpan detik yang sama untuk
        // pemberian delegasi dan pemberian manual yang berurutan.
        return AuditLog::where('action', 'user.role.diubah')
            ->where('model_id', $userId)
            ->where('created_at', '>=', $sejak)
            ->get()
            ->contains(fn ($log) => in_array($role, (array) ($log->diff_json['ke'] ?? []), true));
    }
}
