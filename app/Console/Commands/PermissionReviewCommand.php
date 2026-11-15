<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RBAC batch 4: review higiene — peran tanpa user, permission tanpa peran,
 * delegasi kedaluwarsa belum dicabut. Keluar 0 selalu (laporan, bukan gate).
 */
class PermissionReviewCommand extends Command
{
    protected $signature = 'permission:review';

    protected $description = 'Laporkan peran tanpa user, permission tanpa peran, dan delegasi kedaluwarsa.';

    public function handle(): int
    {
        $tanpaUser = [];
        foreach (Role::withCount('users')->get() as $role) {
            if ($role->users_count === 0) {
                $tanpaUser[] = $role->name;
            }
        }

        $tanpaRole = Permission::doesntHave('roles')->pluck('name')->all();

        $kedaluwarsa = 0;
        if (Schema::hasTable('delegasi_akses')) {
            try {
                $kedaluwarsa = (int) DB::table('delegasi_akses')
                    ->where('status', 'aktif')
                    ->whereDate('sampai_at', '<', today()->toDateString())
                    ->count();
            } catch (\Throwable) {
            }
        }

        $this->table(
            ['Temuan', 'Jumlah', 'Detail'],
            [
                ['Peran tanpa user', count($tanpaUser), implode(', ', $tanpaUser) ?: '-'],
                ['Permission tanpa peran', count($tanpaRole), implode(', ', $tanpaRole) ?: '-'],
                ['Delegasi kedaluwarsa (status aktif)', $kedaluwarsa, $kedaluwarsa > 0 ? 'jalankan delegasi:sinkron' : '-'],
            ]
        );

        return self::SUCCESS;
    }
}
