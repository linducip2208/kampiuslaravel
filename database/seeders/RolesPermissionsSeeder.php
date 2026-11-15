<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'master.manage',
            'user.manage',
            'pmb.view',
            'pmb.verify',
            'krs.manage',
            'krs.approve',
            'lms.manage',
            'nilai.manage',
            'nilai.lock',
            'skripsi.manage',
            'skripsi.approve',
            'keuangan.manage',
            'surat.manage',
            'pengumuman.manage',
            'laporan.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = [
            'superadmin' => $permissions,
            'admin_prodi' => [
                'master.manage',
                'pmb.view',
                'pmb.verify',
                'krs.manage',
                'krs.approve',
                'skripsi.manage',
                'surat.manage',
                'pengumuman.manage',
                'laporan.view',
            ],
            'keuangan' => [
                'keuangan.manage',
                'laporan.view',
            ],
            'dosen' => [
                'lms.manage',
                'nilai.manage',
                'skripsi.manage',
            ],
            'mahasiswa' => [
                'pmb.view',
            ],
            'pimpinan' => [
                'laporan.view',
            ],
            // Batch 1 ekspansi RBAC (aditif; 6 peran lama di atas TIDAK diubah).
            // Pemetaan ke permission yang SUDAH ada agar langsung berfungsi
            // di gate berbasis permission; wiring berbasis role menyusul per modul.
            'rektor' => ['laporan.view'],
            'warek' => ['laporan.view'],
            'dekan' => ['laporan.view', 'pengumuman.manage'],
            'wadek' => ['laporan.view', 'pengumuman.manage'],
            'kaprodi' => [
                'master.manage',
                'pmb.view',
                'pmb.verify',
                'krs.manage',
                'krs.approve',
                'lms.manage',
                'nilai.manage',
                'skripsi.manage',
                'surat.manage',
                'pengumuman.manage',
                'laporan.view',
            ],
            'kajur' => [
                'master.manage',
                'pmb.view',
                'pmb.verify',
                'krs.manage',
                'krs.approve',
                'lms.manage',
                'nilai.manage',
                'skripsi.manage',
                'surat.manage',
                'pengumuman.manage',
                'laporan.view',
            ],
            'penguji' => ['skripsi.manage', 'nilai.manage'],
            'kasir' => ['keuangan.manage'],
            'akuntansi' => ['keuangan.manage', 'laporan.view'],
            'hrd' => ['laporan.view', 'pengumuman.manage'],
            'auditor' => ['laporan.view'],
            'alumni' => [],
            'orangtua' => [],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
