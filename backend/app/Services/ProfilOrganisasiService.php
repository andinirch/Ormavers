<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\Pengguna;

class ProfilOrganisasiService
{
    /**
     * Dapatkan organisasi aktif milik akun pengguna.
     */
    public function getOrganisasiAktif(Pengguna $pengguna): ?Organisasi
    {
        $organisasi = Organisasi::where('id_akun_organisasi', $pengguna->id_pengguna)->first();

        if (! $organisasi || $organisasi->status_organisasi !== 'aktif') {
            return null;
        }

        return $organisasi;
    }

    /**
     * Ambil data deskripsi dan informasi organisasi milik pengguna.
     */
    public function getDeskripsi(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        return [
            'data' => [
                'id_organisasi'     => $organisasi->id_organisasi,
                'nama_organisasi'   => $organisasi->nama_organisasi,
                'jenis_organisasi'  => $organisasi->jenis_organisasi,
                'deskripsi'         => $organisasi->deskripsi,
                'status_organisasi' => $organisasi->status_organisasi,
            ],
        ];
    }

    /**
     * Perbarui deskripsi organisasi.
     */
    public function updateDeskripsi(Pengguna $pengguna, string $deskripsi): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $organisasi->update([
            'deskripsi' => $deskripsi,
        ]);

        $organisasi->refresh();

        return [
            'data' => [
                'id_organisasi'     => $organisasi->id_organisasi,
                'nama_organisasi'   => $organisasi->nama_organisasi,
                'jenis_organisasi'  => $organisasi->jenis_organisasi,
                'deskripsi'         => $organisasi->deskripsi,
                'status_organisasi' => $organisasi->status_organisasi,
            ],
        ];
    }
}
