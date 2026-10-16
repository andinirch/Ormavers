<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class AdministratorService
{
    /**
     * Mengambil daftar seluruh administrator, diurutkan dari yang terbaru.
     *
     * @return array<int, array>
     */
    public function daftarAdministrator(): array
    {
        return Pengguna::where('peran', 'administrator')
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id_pengguna')
            ->get()
            ->map(fn (Pengguna $pengguna) => $this->formatPengguna($pengguna))
            ->values()
            ->all();
    }

    /**
     * Menambahkan akun administrator baru.
     *
     * @param array{nama: string, username: string, password: string} $data
     * @return array
     */
    public function tambahAdministrator(array $data): array
    {
        $pengguna = Pengguna::create([
            'nama'        => $data['nama'],
            'username'    => $data['username'],
            'kata_sandi'  => $data['password'],
            'peran'       => 'administrator',
            'status_akun' => 'aktif',
        ]);

        return $this->formatPengguna($pengguna);
    }

    /**
     * Menonaktifkan akun administrator dan mencabut semua tokennya dalam satu transaksi.
     *
     * @param int $id
     * @return array{error?: string, status?: int, administrator?: array}
     */
    public function nonaktifkanAdministrator(int $id): array
    {
        $pengguna = Pengguna::where('id_pengguna', $id)->first();

        if (! $pengguna || $pengguna->peran !== 'administrator') {
            return [
                'error'  => 'Administrator tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pengguna->status_akun === 'nonaktif') {
            return [
                'error'  => 'Akun Administrator sudah dinonaktifkan',
                'status' => 409,
            ];
        }

        DB::transaction(function () use ($pengguna) {
            $pengguna->update(['status_akun' => 'nonaktif']);
            $pengguna->tokens()->delete();
        });

        return [
            'administrator' => $this->formatPengguna($pengguna->fresh()),
        ];
    }

    public function daftarSemuaPengguna(): array
    {
        return Pengguna::query()
            ->select('pengguna.*')
            ->selectSub(function ($query) {
                $query->from('personal_access_tokens')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('tokenable_id', 'pengguna.id_pengguna')
                    ->where('tokenable_type', Pengguna::class);
            }, 'login_terakhir')
            ->orderByDesc('dibuat_pada')
            ->get()
            ->map(fn (Pengguna $pengguna) => $this->formatPengguna($pengguna, true))
            ->values()
            ->all();
    }

    /**
     * Format data pengguna untuk response tanpa menyertakan kata sandi.
     */
    public function formatPengguna(Pengguna $pengguna, bool $includeLogin = false): array
    {
        return [
            'id_pengguna' => $pengguna->id_pengguna,
            'nama'        => $pengguna->nama,
            'username'    => $pengguna->username,
            'peran'       => $pengguna->peran,
            'status_akun' => $pengguna->status_akun,
            'foto_profil' => $pengguna->foto_profil,
            ...($includeLogin ? ['login_terakhir' => $pengguna->login_terakhir] : []),
        ];
    }
}
