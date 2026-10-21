<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Proses login: cari pengguna, verifikasi password, cek boleh login.
     *
     * @return array{token: string, pengguna: array}
     */
    public function login(string $username, string $password, ?string $ip = null): array
    {
        $pengguna = Pengguna::where('username', $username)->first();

        if (! $pengguna || ! Hash::check($password, $pengguna->kata_sandi)) {
            return ['error' => 'Username atau password salah', 'status' => 401];
        }

        $this->bolehLogin($pengguna);

        // Jika status menunggu atau ditolak, berikan akses terbatas
        if (in_array($pengguna->status_akun, ['menunggu', 'ditolak'], true)) {
            $token = $pengguna->createToken('auth_token', ['status-registrasi'])->plainTextToken;

            $this->logAktivitasService->catat(
                $pengguna->id_pengguna,
                'login',
                "Berhasil masuk dengan akses terbatas dari IP {$ip}"
            );

            return [
                'token'          => $token,
                'akses_terbatas' => true,
                'message'        => 'Login terbatas: hanya dapat melihat status registrasi',
                'pengguna'       => $this->dataPengguna($pengguna),
                'roles'          => $this->dataSpatie($pengguna)['roles'],
                'permissions'    => [],
            ];
        }

        $token = $pengguna->createToken('auth_token', ['*'])->plainTextToken;

        $this->logAktivitasService->catat(
            $pengguna->id_pengguna,
            'login',
            "Berhasil masuk ke sistem dari IP {$ip}"
        );

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
        if (in_array($pengguna->status_akun, ['aktif', 'menunggu', 'ditolak'], true)) {
            return true;
        }

        $pesan = match ($pengguna->status_akun) {
            'nonaktif'     => 'Akun dinonaktifkan',
            'ditangguhkan' => 'Akun ditangguhkan',
            default        => 'Akun tidak dapat digunakan',
        };

        throw new AccessDeniedHttpException($pesan);
    }

    /**
     * Logout: hapus token yang sedang dipakai.
     */
    public function logout(Pengguna $pengguna): void
    {
        $pengguna->currentAccessToken()->delete();

        $this->logAktivitasService->catat(
            $pengguna->id_pengguna,
            'logout',
            'Keluar dari sistem'
        );
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
