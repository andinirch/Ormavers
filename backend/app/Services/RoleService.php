<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Pemetaan permission → modul (A3).
     */
    private const PERMISSION_MODUL = [
        'view users'                => 'Pengguna & Role',
        'create users'              => 'Pengguna & Role',
        'edit users'                => 'Pengguna & Role',
        'delete users'              => 'Pengguna & Role',
        'view roles'                => 'Pengguna & Role',
        'create roles'              => 'Pengguna & Role',
        'edit roles'                => 'Pengguna & Role',
        'delete roles'              => 'Pengguna & Role',
        'verify bem'                => 'Verifikasi',
        'verify hmj/ukm'            => 'Verifikasi',
        'verify members'            => 'Verifikasi',
        'view profile'              => 'Profil & Keanggotaan',
        'view registration status'  => 'Profil & Keanggotaan',
        'view members'              => 'Profil & Keanggotaan',
    ];

    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    // ─── Permission ─────────────────────────────────────────

    public function daftarPermission(): array
    {
        $permissions = Permission::with('roles')->get();

        return $permissions->map(function (Permission $perm) {
            return [
                'id'    => $perm->id,
                'name'  => $perm->name,
                'modul' => self::PERMISSION_MODUL[$perm->name] ?? null,
                'roles' => $perm->roles->pluck('name')->values()->toArray(),
            ];
        })->toArray();
    }

    // ─── Role CRUD ──────────────────────────────────────────

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

    public function buatRole(array $data, int $idPelaku): array
    {
        return DB::transaction(function () use ($data, $idPelaku) {
            $role = Role::create([
                'name'       => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($data['permissions']);
            $role->load('permissions');

            $this->logAktivitasService->catat(
                $idPelaku,
                'role',
                "Membuat role {$role->name}"
            );

            return $this->formatRole($role);
        });
    }

    public function ubahRole(int $id, array $data, int $idPelaku): array
    {
        $role = Role::with('permissions')->find($id);

        if (! $role) {
            return ['error' => 'Role tidak ditemukan', 'status' => 404];
        }

        // Role super_admin tidak boleh diubah sama sekali (terkunci)
        if ($role->name === 'super_admin') {
            return ['error' => 'Role super_admin dikunci dan tidak dapat diubah', 'status' => 403];
        }

        return DB::transaction(function () use ($role, $data, $idPelaku) {
            // Role lain (termasuk bawaan): nama dan permission boleh diubah
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions']);
            $role->load('permissions');

            $this->logAktivitasService->catat(
                $idPelaku,
                'role',
                "Mengubah permission role {$role->name}"
            );

            return $this->formatRole($role);
        });
    }

    public function hapusRole(int $id, int $idPelaku): array
    {
        $role = Role::find($id);

        if (! $role) {
            return ['error' => 'Role tidak ditemukan', 'status' => 404];
        }

        // Role super_admin tidak boleh dihapus (terkunci)
        if ($role->name === 'super_admin') {
            return ['error' => 'Role super_admin dikunci dan tidak dapat dihapus', 'status' => 403];
        }

        // Cek apakah role masih dipakai
        $jumlah = Pengguna::role($role->name)->count();

        if ($jumlah > 0) {
            return [
                'error'  => "Role masih dipakai oleh {$jumlah} pengguna",
                'status' => 409,
            ];
        }

        return DB::transaction(function () use ($role, $idPelaku) {
            $namaRole = $role->name;
            $role->delete();

            $this->logAktivitasService->catat(
                $idPelaku,
                'role',
                "Menghapus role {$namaRole}"
            );

            return ['message' => 'Role berhasil dihapus'];
        });
    }

    // ─── Ringkasan Dashboard (A4) ───────────────────────────

    public function ringkasan(): array
    {
        $logTerbaru = \App\Models\LogAktivitas::with('pengguna')
            ->orderByDesc('waktu_aktivitas')
            ->orderByDesc('id_log')
            ->limit(4)
            ->get()
            ->map(function (\App\Models\LogAktivitas $log) {
                return [
                    'nama_pengguna'   => $log->pengguna?->nama,
                    'jenis_aktivitas' => $log->jenis_aktivitas,
                    'keterangan'      => $log->keterangan,
                    'waktu_aktivitas' => $log->waktu_aktivitas,
                ];
            })
            ->toArray();

        return [
            'total_pengguna'      => Pengguna::count(),
            'administrator_aktif' => Pengguna::where('peran', 'administrator')
                ->where('status_akun', 'aktif')
                ->count(),
            'role_terdefinisi'    => Role::count(),
            'permission'         => Permission::count(),
            'aktivitas_terbaru'  => $logTerbaru,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function formatRole(Role $role): array
    {
        $isBawaan = in_array($role->name, Pengguna::ROLE_BAWAAN, true);

        return [
            'id'              => $role->id,
            'name'            => $role->name,
            'permissions'     => $role->permissions->pluck('name')->values()->toArray(),
            'jumlah_pengguna' => Pengguna::role($role->name)->count(),
            'bawaan'          => $isBawaan,
            'terkunci'        => $role->name === 'super_admin',
            'akses_penuh'     => false,
            'dibuat_pada'     => $role->created_at,
        ];
    }
}
