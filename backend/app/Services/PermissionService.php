<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionService
{
    /** Permission yang dipakai langsung oleh middleware backend. */
    private const PERMISSION_TERKUNCI = [
        'view roles', 'create roles', 'edit roles', 'delete roles',
    ];

    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    public function buat(array $data, int $idPelaku): array
    {
        return DB::transaction(function () use ($data, $idPelaku) {
            $permission = Permission::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->logAktivitasService->catat($idPelaku, 'role', "Membuat permission {$permission->name}");

            return $this->format($permission);
        });
    }

    public function ubah(int $id, array $data, int $idPelaku): array
    {
        $permission = Permission::where('guard_name', 'web')->find($id);

        if (! $permission) {
            return ['error' => 'Permission tidak ditemukan', 'status' => 404];
        }

        if (in_array($permission->name, self::PERMISSION_TERKUNCI, true)) {
            return ['error' => 'Permission ini digunakan langsung oleh middleware dan tidak dapat diubah namanya', 'status' => 409];
        }

        return DB::transaction(function () use ($permission, $data, $idPelaku) {
            $namaLama = $permission->name;
            $permission->update(['name' => $data['name']]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->logAktivitasService->catat($idPelaku, 'role', "Mengubah permission {$namaLama} menjadi {$permission->name}");

            return $this->format($permission->fresh('roles'));
        });
    }

    public function hapus(int $id, int $idPelaku): array
    {
        $permission = Permission::where('guard_name', 'web')->withCount('roles')->find($id);

        if (! $permission) {
            return ['error' => 'Permission tidak ditemukan', 'status' => 404];
        }

        if (in_array($permission->name, self::PERMISSION_TERKUNCI, true)) {
            return ['error' => 'Permission ini digunakan langsung oleh middleware dan tidak dapat dihapus', 'status' => 409];
        }

        if ($permission->roles_count > 0) {
            return ['error' => 'Permission masih digunakan oleh satu atau lebih role. Lepaskan permission dari role terlebih dahulu.', 'status' => 409];
        }

        return DB::transaction(function () use ($permission, $idPelaku) {
            $nama = $permission->name;
            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->logAktivitasService->catat($idPelaku, 'role', "Menghapus permission {$nama}");

            return ['message' => 'Permission berhasil dihapus'];
        });
    }

    private function format(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'roles' => $permission->roles()->pluck('name')->values()->toArray(),
        ];
    }
}
