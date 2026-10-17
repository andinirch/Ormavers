<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class AdministratorService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

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
     * @param int $idPelaku
     * @return array
     */
    public function tambahAdministrator(array $data, int $idPelaku): array
    {
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
            "Membuat akun Administrator baru: {$pengguna->nama} ({$pengguna->username})"
        );

        return $this->formatPengguna($pengguna);
    }

    /**
     * Menonaktifkan akun administrator dan mencabut semua tokennya dalam satu transaksi.
     *
     * @param int $id
     * @param int $idPelaku
     * @return array{error?: string, status?: int, administrator?: array}
     */
    public function nonaktifkanAdministrator(int $id, int $idPelaku): array
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

        DB::transaction(function () use ($pengguna, $idPelaku) {
            $pengguna->update(['status_akun' => 'nonaktif']);
            $pengguna->tokens()->delete();

            $this->logAktivitasService->catat(
                $idPelaku,
                'kelola_akun',
                "Menonaktifkan akun Administrator: {$pengguna->nama}"
            );
        });

        return [
            'administrator' => $this->formatPengguna($pengguna->fresh()),
        ];
    }

    /**
     * Format data pengguna untuk response tanpa menyertakan kata sandi.
     */
    public function formatPengguna(Pengguna $pengguna): array
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
}
