<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    /**
     * Proses login: cari pengguna, verifikasi password, cek boleh login.
     *
     * @return array{token: string, pengguna: array}
     */
    public function login(string $username, string $password): array
    {
        $pengguna = Pengguna::where('username', $username)->first();

        if (! $pengguna || ! Hash::check($password, $pengguna->kata_sandi)) {
            return ['error' => 'Username atau password salah', 'status' => 401];
        }

        $this->bolehLogin($pengguna);

        $token = $pengguna->createToken('auth_token')->plainTextToken;

        return [
            'token'       => $token,
            'pengguna'    => $this->dataPengguna($pengguna),
            'roles'       => $this->dataSpatie($pengguna)['roles'],
            'permissions' => $this->dataSpatie($pengguna)['permissions'],
        ];
    }

    /**
     * Cek apakah akun boleh login berdasarkan status_akun.
     *
     * @throws AccessDeniedHttpException
     */
    public function bolehLogin(Pengguna $pengguna): bool
    {
        if ($pengguna->status_akun === 'aktif') {
            return true;
        }

        $pesan = match ($pengguna->status_akun) {
            'menunggu' => 'Akun belum disetujui',
            'nonaktif' => 'Akun dinonaktifkan',
            'ditolak'  => 'Registrasi ditolak',
            default    => 'Akun tidak dapat digunakan',
        };

        throw new AccessDeniedHttpException($pesan);
    }

    /**
     * Logout: hapus token yang sedang dipakai.
     */
    public function logout(Pengguna $pengguna): void
    {
        $pengguna->currentAccessToken()->delete();
    }

    /**
     * Format data pengguna untuk response.
     */
    public function dataPengguna(Pengguna $pengguna): array
    {
        return [
            'id_pengguna' => $pengguna->id_pengguna,
            'nama'        => $pengguna->nama,
            'username'    => $pengguna->username,
            'peran'       => $pengguna->peran,
            'status_akun' => $pengguna->status_akun,
            'foto_profil' => $pengguna->foto_profil,
        ];
    }

    /**
     * Data role dan permission Spatie untuk response.
     */
    public function dataSpatie(Pengguna $pengguna): array
    {
        return [
            'roles'       => $pengguna->getRoleNames(),
            'permissions' => $pengguna->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
