<?php

namespace App\Services;

use App\Models\Pengguna;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    private const ROLE_BAWAAN = [
        'super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota',
    ];

    public function daftarPermission(): array
    {
        return Permission::all(['id', 'name'])->toArray();
    }

    public function daftarRole(): array
    {
        $roles = Role::with('permissions')->get();

        return $roles->map(function (Role $role) {
            return $this->formatRole($role);
        })->toArray();
    }

    public function detailRole(int $id): array
    {
        $role = Role::with('permissions')->find($id);

        if (! $role) {
            return ['error' => 'Role tidak ditemukan', 'status' => 404];
        }

        return $this->formatRole($role);
    }

    public function buatRole(array $data): array
    {
        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions']);
        $role->load('permissions');

        return $this->formatRole($role);
    }

    public function ubahRole(int $id, array $data): array
    {
        $role = Role::with('permissions')->find($id);

        if (! $role) {
            return ['error' => 'Role tidak ditemukan', 'status' => 404];
        }

        // Role super_admin tidak boleh diubah sama sekali
        if ($role->name === 'super_admin') {
            return ['error' => 'Role super_admin tidak dapat diubah', 'status' => 403];
        }

        // Role bawaan lain: nama tidak boleh diganti, tapi permission boleh diubah
        if (in_array($role->name, self::ROLE_BAWAAN, true)) {
            if ($data['name'] !== $role->name) {
                return ['error' => 'Nama role bawaan tidak dapat diubah', 'status' => 403];
            }
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions']);
        $role->load('permissions');

        return $this->formatRole($role);
    }

    public function hapusRole(int $id): array
    {
        $role = Role::find($id);

        if (! $role) {
            return ['error' => 'Role tidak ditemukan', 'status' => 404];
        }

        // Role bawaan tidak boleh dihapus
        if (in_array($role->name, self::ROLE_BAWAAN, true)) {
            return ['error' => 'Role bawaan tidak dapat dihapus', 'status' => 403];
        }

        // Cek apakah role masih dipakai
        $jumlah = Pengguna::role($role->name)->count();

        if ($jumlah > 0) {
            return [
                'error'  => "Role masih dipakai oleh {$jumlah} pengguna",
                'status' => 409,
            ];
        }

        $role->delete();

        return ['message' => 'Role berhasil dihapus'];
    }

    private function formatRole(Role $role): array
    {
        return [
            'id'              => $role->id,
            'name'            => $role->name,
            'permissions'     => $role->permissions->pluck('name')->values()->toArray(),
            'jumlah_pengguna' => Pengguna::role($role->name)->count(),
            'dibuat_pada'     => $role->created_at,
        ];
    }
}
