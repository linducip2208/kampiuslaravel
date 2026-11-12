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
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
