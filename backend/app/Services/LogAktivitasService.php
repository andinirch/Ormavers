<?php

namespace App\Services;

use App\Models\LogAktivitas;

class LogAktivitasService
{
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
