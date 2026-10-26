<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class UserAdminService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Mengambil daftar pengguna dengan filter dan paginasi beserta ringkasan.
     */
    public function daftarPengguna(array $filters): array
    {
        $query = Pengguna::with(['roles', 'organisasi']);

        if (! empty($filters['q'])) {
            $keyword = '%' . strtolower($filters['q']) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw('LOWER(nama) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$keyword]);
            });
        }

        if (! empty($filters['role'])) {
            $roleName = $filters['role'];
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status_akun', $filters['status']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 10), 50);
        $paginated = $query->orderByDesc('dibuat_pada')
            ->orderByDesc('id_pengguna')
            ->paginate($perPage);

        // Ambil login terakhir untuk id pengguna yang ditampilkan
        $userIds = $paginated->pluck('id_pengguna');
        $loginTerakhirMap = LogAktivitas::whereIn('id_pengguna', $userIds)
            ->where('jenis_aktivitas', 'login')
            ->orderByDesc('waktu_aktivitas')
            ->get()
            ->groupBy('id_pengguna')
            ->map(fn ($logs) => $logs->first()->waktu_aktivitas);

        $users = $paginated->getCollection()->map(function (Pengguna $user) use ($loginTerakhirMap) {
            $roleName = $user->roles->first()?->name ?? $user->peran;

            return [
                'id_pengguna'    => $user->id_pengguna,
                'nama'           => $user->nama,
                'username'       => $user->username,
                'role'           => $roleName,
                'peran'          => $user->peran,
                'organisasi'     => $user->organisasi?->nama_organisasi,
                'status_akun'    => $user->status_akun,
                'login_terakhir' => $loginTerakhirMap->get($user->id_pengguna),
            ];
        })->values()->toArray();

        return [
            'users' => $users,
            'meta'  => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
            'ringkasan' => [
                'total_terdaftar'     => Pengguna::count(),
                'akun_aktif'          => Pengguna::where('status_akun', 'aktif')->count(),
                'verifikasi_tertunda' => Pengguna::where('status_akun', 'menunggu')->count(),
                'ditangguhkan'        => Pengguna::where('status_akun', 'ditangguhkan')->count(),
                'nonaktif_ditolak'    => Pengguna::whereIn('status_akun', ['nonaktif', 'ditolak'])->count(),
            ],
        ];
    }

    /**
     * Membuat akun Administrator baru.
     */
    public function tambahAdministrator(array $data, int $idPelaku): array
    {
        $pengguna = DB::transaction(function () use ($data, $idPelaku) {
            $pengguna = Pengguna::create([
                'nama'        => $data['nama'],
                'username'    => $data['username'],
                'kata_sandi'  => $data['password'],
                'peran'       => 'administrator',
                'status_akun' => 'aktif',
            ]);

            $this->logAktivitasService->catat(
                $idPelaku,
                'kelola_akun',
                "Membuat akun Administrator: {$pengguna->nama} ({$pengguna->username})"
            );

            return $pengguna;
        });

        $pengguna->load(['roles', 'organisasi', 'profilAnggota']);

        return [
            'id_pengguna' => $pengguna->id_pengguna,
            'nama'        => $pengguna->nama,
            'username'    => $pengguna->username,
            'role'        => $pengguna->roles->first()?->name ?? 'administrator',
            'peran'       => $pengguna->peran,
            'status_akun' => $pengguna->status_akun,
            'foto_profil' => $pengguna->profilAnggota?->foto_profil,
        ];
    }

    /**
     * Menonaktifkan akun Administrator dan mencabut semua tokennya.
     */
    public function ubahPengguna(int $id, array $data, int $idPelaku): array
    {
        $pengguna = Pengguna::with(['roles', 'organisasi'])->find($id);

        if (! $pengguna) {
            return [
                'error'  => 'Pengguna tidak ditemukan',
                'status' => 404,
            ];
        }

        // Super Admin hanya mengelola akun administrator dan bukan akunnya sendiri
        if ($pengguna->id_pengguna === $idPelaku || $pengguna->peran !== 'administrator') {
            return [
                'error'  => 'Hanya akun Administrator yang dikelola Super Admin',
                'status' => 403,
            ];
        }

        // Status menunggu tidak boleh dinonaktifkan di sini
        if ($pengguna->status_akun === 'menunggu') {
            return [
                'error'  => 'Akun yang sedang menunggu verifikasi tidak dapat dinonaktifkan',
                'status' => 409,
            ];
        }

        if ($pengguna->status_akun === 'nonaktif') {
            return [
                'error'  => 'Akun Administrator sudah dinonaktifkan',
                'status' => 409,
            ];
        }

        DB::transaction(function () use ($pengguna, $idPelaku) {
            $pengguna->update(['status_akun' => 'nonaktif']);
            $pengguna->tokens()->delete();

            $this->logAktivitasService->catat(
                $idPelaku,
                'kelola_akun',
                "Menonaktifkan akun Administrator: {$pengguna->nama}"
            );
        });

        $pengguna->refresh();

        return [
            'user' => [
                'id_pengguna'    => $pengguna->id_pengguna,
                'nama'           => $pengguna->nama,
                'username'       => $pengguna->username,
                'role'           => $pengguna->roles->first()?->name ?? $pengguna->peran,
                'peran'          => $pengguna->peran,
                'organisasi'     => $pengguna->organisasi?->nama_organisasi,
                'status_akun'    => $pengguna->status_akun,
                'login_terakhir' => null,
            ],
        ];
    }
}
