<?php

namespace App\Services;

use App\Models\LogAktivitas;

class LogAktivitasService
{
    // Konstanta Jenis Aktivitas (UC-11)
    public const JENIS_LOGIN = 'login';
    public const JENIS_LOGOUT = 'logout';
    public const JENIS_PENDAFTARAN = 'pendaftaran';
    public const JENIS_VERIFIKASI = 'verifikasi';
    public const JENIS_KELOLA_AKUN = 'kelola_akun';
    public const JENIS_ROLE = 'role';

    public const SEMUA_JENIS = [
        self::JENIS_LOGIN,
        self::JENIS_LOGOUT,
        self::JENIS_PENDAFTARAN,
        self::JENIS_VERIFIKASI,
        self::JENIS_KELOLA_AKUN,
        self::JENIS_ROLE,
    ];

    /**
     * Catat log aktivitas pengguna.
     */
    public function catat(int $idPengguna, string $jenis, string $keterangan): void
    {
        LogAktivitas::create([
            'id_pengguna'     => $idPengguna,
            'jenis_aktivitas' => $jenis,
            'keterangan'      => $keterangan,
            'waktu_aktivitas' => now(),
        ]);
    }
}

