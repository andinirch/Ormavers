<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles dan permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat 14 permission
        $permissions = [
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view users', 'create users', 'edit users', 'delete users',
            'verify bem', 'verify hmj/ukm', 'verify members',
            'view members', 'view profile', 'view registration status',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Buat 5 role dan assign permission
        $rolePermissions = [
            'super_admin' => [
                'view roles', 'create roles', 'edit roles', 'delete roles',
                'view users', 'create users', 'edit users', 'delete users',
            ],
            'administrator' => ['view users', 'verify bem'],
            'akun_bem'      => ['view users', 'verify hmj/ukm', 'verify members', 'view profile'],
            'akun_hmj_ukm'  => ['view members', 'verify members', 'view profile'],
            'anggota'       => ['view profile', 'view registration status'],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }

        // Sinkronisasi pengguna yang sudah ada berdasarkan kolom peran
        foreach (Pengguna::PERAN_ROLE_MAP as $kolom => $namaRole) {
            $role = Role::findByName($namaRole, 'web');
            Pengguna::where('peran', $kolom)->each(function ($p) use ($role) {
                if (! $p->hasRole($role)) {
                    $p->assignRole($role);
                }
            });
        }
    }
}
