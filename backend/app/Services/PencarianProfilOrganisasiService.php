<?php

namespace App\Services;

use App\Models\Organisasi;
use Illuminate\Support\Facades\Storage;

class PencarianProfilOrganisasiService
{
    /**
     * Cari dan filter daftar organisasi aktif berdasarkan parameter opsional.
     *
     * @param array $filters  keyword, id_universitas, jenis_organisasi, id_bidang
     * @return array
     */
    public function cariOrganisasi(array $filters): array
    {
        $query = Organisasi::with(['universitas', 'bidang'])
            ->where('status_organisasi', 'aktif');

        if (! empty($filters['keyword'])) {
            $query->where('nama_organisasi', 'LIKE', '%' . $filters['keyword'] . '%');
        }

        if (! empty($filters['id_universitas'])) {
            $query->where('id_universitas', $filters['id_universitas']);
        }

        if (! empty($filters['jenis_organisasi'])) {
            $query->where('jenis_organisasi', $filters['jenis_organisasi']);
        }

        if (! empty($filters['id_bidang'])) {
            $query->where('id_bidang', $filters['id_bidang']);
        }

        $organisasiList = $query->orderBy('nama_organisasi', 'asc')->get();

        $daftarOrganisasi = $organisasiList->map(function (Organisasi $org) {
            return $this->formatOrganisasi($org);
        })->values()->all();

        if (empty($daftarOrganisasi)) {
            return [
                'success' => true,
                'status'  => 200,
                'message' => 'Organisasi tidak ditemukan',
                'data'    => [],
            ];
        }

        return [
            'success' => true,
            'status'  => 200,
            'message' => 'Daftar organisasi berhasil diambil',
            'data'    => $daftarOrganisasi,
        ];
    }

    /**
     * Ambil detail profil organisasi lengkap beserta relasi.
     *
     * @param int $idOrganisasi
     * @return array
     */
    public function getProfilOrganisasi(int $idOrganisasi): array
    {
        $organisasi = Organisasi::with([
            'universitas',
            'bidang',
            'portofolio' => function ($query) {
                $query->orderBy('tahun', 'desc')
                      ->orderBy('id_portofolio', 'desc');
            },
            'kontak',
        ])->find($idOrganisasi);

        if (! $organisasi || $organisasi->status_organisasi !== 'aktif') {
            return [
                'success' => false,
                'status'  => 404,
                'message' => 'Organisasi tidak ditemukan',
            ];
        }

        $isDefaultLogo = true;
        $logoUrl = asset('images/default-organisasi.png');

        if ($organisasi->logo && Storage::disk('public')->exists($organisasi->logo)) {
            $logoUrl = Storage::disk('public')->url($organisasi->logo);
            $isDefaultLogo = false;
        }

        $data = [
            'id_organisasi'     => $organisasi->id_organisasi,
            'nama_organisasi'   => $organisasi->nama_organisasi,
            'jenis_organisasi'  => $organisasi->jenis_organisasi,
            'deskripsi'         => $organisasi->deskripsi,
            'alamat'            => $organisasi->alamat,
            'nomor_telepon'     => $organisasi->nomor_telepon,
            'logo'              => $logoUrl,
            'is_default_logo'   => $isDefaultLogo,
            'status_organisasi' => $organisasi->status_organisasi,
            'universitas'       => $organisasi->universitas ? [
                'id_universitas'   => $organisasi->universitas->id_universitas,
                'nama_universitas' => $organisasi->universitas->nama_universitas,
            ] : null,
            'bidang' => $organisasi->bidang ? [
                'id_bidang'    => $organisasi->bidang->id_bidang,
                'nama_bidang'  => $organisasi->bidang->nama_bidang,
            ] : null,
            'portofolio' => $organisasi->portofolio->map(function ($porto) {
                return [
                    'id_portofolio' => $porto->id_portofolio,
                    'judul'         => $porto->judul,
                    'tahun'         => $porto->tahun,
                    'deskripsi'     => $porto->deskripsi,
                    'dokumentasi'   => $porto->dokumentasi,
                ];
            })->values()->all(),
            'kontak' => $organisasi->kontak->map(function ($kontak) {
                return [
                    'id_kontak' => $kontak->id_kontak,
                    'jenis'     => $kontak->jenis,
                    'tautan'    => $kontak->tautan,
                ];
            })->values()->all(),
        ];

        return [
            'success' => true,
            'status'  => 200,
            'message' => 'Profil organisasi berhasil diambil',
            'data'    => $data,
        ];
    }

    /**
     * Format data organisasi untuk respons pencarian.
     */
    private function formatOrganisasi(Organisasi $org): array
    {
        $isDefaultLogo = true;
        $logoUrl = asset('images/default-organisasi.png');

        if ($org->logo && Storage::disk('public')->exists($org->logo)) {
            $logoUrl = Storage::disk('public')->url($org->logo);
            $isDefaultLogo = false;
        }

        return [
            'id_organisasi'    => $org->id_organisasi,
            'id_universitas'   => $org->id_universitas,
            'nama_organisasi'  => $org->nama_organisasi,
            'jenis_organisasi' => $org->jenis_organisasi,
            'id_bidang'        => $org->id_bidang,
            'nama_bidang'      => $org->bidang?->nama_bidang,
            'nama_universitas' => $org->universitas?->nama_universitas,
            'deskripsi'        => $org->deskripsi,
            'alamat'           => $org->alamat,
            'logo'             => $logoUrl,
            'is_default_logo'  => $isDefaultLogo,
        ];
    }
}
