<?php

namespace App\Services;

use App\Models\KontakOrganisasi;
use App\Models\Organisasi;
use App\Models\Pengguna;

class KontakService
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
     * Ambil seluruh daftar kontak milik organisasi pengguna.
     */
    public function getDaftarKontak(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $daftarKontak = KontakOrganisasi::where('id_organisasi', $organisasi->id_organisasi)
            ->orderBy('id_kontak', 'asc')
            ->get();

        return [
            'data' => $daftarKontak,
        ];
    }

    /**
     * Tambah data kontak baru dan kembalikan seluruh daftar kontak organisasi.
     */
    public function tambahKontak(Pengguna $pengguna, array $data): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        KontakOrganisasi::create([
            'id_organisasi' => $organisasi->id_organisasi,
            'jenis'         => $data['jenis'],
            'tautan'        => $data['tautan'],
        ]);

        $daftarKontak = KontakOrganisasi::where('id_organisasi', $organisasi->id_organisasi)
            ->orderBy('id_kontak', 'asc')
            ->get();

        return [
            'data' => $daftarKontak,
        ];
    }

    /**
     * Perbarui data kontak dan kembalikan seluruh daftar kontak organisasi.
     */
    public function updateKontak(Pengguna $pengguna, int $idKontak, array $data): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $kontak = KontakOrganisasi::where('id_kontak', $idKontak)
            ->where('id_organisasi', $organisasi->id_organisasi)
            ->first();

        if (! $kontak) {
            return [
                'error'  => 'Kontak tidak ditemukan',
                'status' => 404,
            ];
        }

        $kontak->update([
            'jenis'  => $data['jenis'],
            'tautan' => $data['tautan'],
        ]);

        $daftarKontak = KontakOrganisasi::where('id_organisasi', $organisasi->id_organisasi)
            ->orderBy('id_kontak', 'asc')
            ->get();

        return [
            'data' => $daftarKontak,
        ];
    }

    /**
     * Hapus data kontak dan kembalikan seluruh daftar kontak organisasi terbaru.
     */
    public function hapusKontak(Pengguna $pengguna, int $idKontak): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $kontak = KontakOrganisasi::where('id_kontak', $idKontak)
            ->where('id_organisasi', $organisasi->id_organisasi)
            ->first();

        if (! $kontak) {
            return [
                'error'  => 'Kontak tidak ditemukan',
                'status' => 404,
            ];
        }

        $kontak->delete();

        $daftarKontak = KontakOrganisasi::where('id_organisasi', $organisasi->id_organisasi)
            ->orderBy('id_kontak', 'asc')
            ->get();

        return [
            'data' => $daftarKontak,
        ];
    }
}
