<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\Universitas;
use Illuminate\Support\Facades\Storage;

class DirektoriOrganisasiService
{
    /**
     * Mengambil daftar universitas beserta URL logonya atau ikon bawaan jika belum diunggah.
     *
     * @return array
     */
    public function getDaftarUniversitas(?string $search = null)
    {
        return Universitas::orderBy('nama_universitas', 'asc')
            ->when($search, function ($query, $search) {
                return $query->where('nama_universitas', 'like', '%' . $search . '%');
            })
            ->paginate(6)
            ->through(function (Universitas $univ) {
                $isDefaultLogo = true;
                $logoUrl = asset('images/default-university.png');

                if ($univ->logo && Storage::disk('public')->exists($univ->logo)) {
                    $logoUrl = Storage::disk('public')->url($univ->logo);
                    $isDefaultLogo = false;
                }

                return [
                    'id_universitas'   => $univ->id_universitas,
                    'nama_universitas' => $univ->nama_universitas,
                    'logo'             => $logoUrl,
                    'is_default_logo'  => $isDefaultLogo,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Mengambil daftar organisasi mahasiswa yang terverifikasi (aktif) pada universitas yang dipilih.
     *
     * @param int $idUniversitas
     * @return array
     */
    public function getOrganisasiByUniversitas(int $idUniversitas): array
    {
        $universitas = Universitas::find($idUniversitas);

        if (! $universitas) {
            return [
                'success' => false,
                'status'  => 404,
                'message' => 'Universitas tidak ditemukan',
            ];
        }

        $organisasiList = Organisasi::with(['bidang'])
            ->where('id_universitas', $idUniversitas)
            ->where('status_organisasi', 'aktif')
            ->orderByRaw("CASE WHEN jenis_organisasi = 'bem' THEN 1 WHEN jenis_organisasi = 'hmj' THEN 2 ELSE 3 END")
            ->orderBy('nama_organisasi', 'asc')
            ->get();

        $daftarOrganisasi = $organisasiList->map(function (Organisasi $org) {
            $isDefaultLogo = true;
            $logoUrl = asset('images/default-organisasi.png');

            if ($org->logo && Storage::disk('public')->exists($org->logo)) {
                $logoUrl = Storage::disk('public')->url($org->logo);
                $isDefaultLogo = false;
            }

            return [
                'id_organisasi'       => $org->id_organisasi,
                'id_universitas'      => $org->id_universitas,
                'nama_organisasi'     => $org->nama_organisasi,
                'jenis_organisasi'    => $org->jenis_organisasi,
                'id_bidang'           => $org->id_bidang,
                'nama_bidang'         => $org->bidang?->nama_bidang,
                'deskripsi'           => $org->deskripsi,
                'alamat'              => $org->alamat,
                'nomor_telepon'       => $org->nomor_telepon,
                'logo'                => $logoUrl,
                'is_default_logo'     => $isDefaultLogo,
                'status_organisasi'   => $org->status_organisasi,
            ];
        })->values()->all();

        if (empty($daftarOrganisasi)) {
            return [
                'success' => true,
                'status'  => 200,
                'message' => 'Belum ada organisasi mahasiswa yang terverifikasi pada universitas ini',
                'data'    => [
                    'universitas' => [
                        'id_universitas'   => $universitas->id_universitas,
                        'nama_universitas' => $universitas->nama_universitas,
                    ],
                    'organisasi' => [],
                ],
            ];
        }

        return [
            'success' => true,
            'status'  => 200,
            'message' => 'Daftar organisasi mahasiswa berhasil diambil',
            'data'    => [
                'universitas' => [
                    'id_universitas'   => $universitas->id_universitas,
                    'nama_universitas' => $universitas->nama_universitas,
                ],
                'organisasi' => $daftarOrganisasi,
            ],
        ];
    }
}
