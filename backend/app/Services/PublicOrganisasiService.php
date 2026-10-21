<?php

namespace App\Services;

use App\Models\Organisasi;

class PublicOrganisasiService
{
    /**
     * Ambil daftar BEM berstatus aktif, urut nama universitas.
     *
     * @return array
     */
    public function daftarBemAktif(): array
    {
        return Organisasi::with('universitas')
            ->where('jenis_organisasi', 'bem')
            ->where('status_organisasi', 'aktif')
            ->get()
            ->sortBy(fn (Organisasi $o) => strtolower($o->universitas?->nama_universitas ?? ''))
            ->map(fn (Organisasi $bem) => [
                'id_organisasi'    => $bem->id_organisasi,
                'nama_organisasi'  => $bem->nama_organisasi,
                'id_universitas'   => $bem->id_universitas,
                'nama_universitas' => $bem->universitas?->nama_universitas,
            ])
            ->values()
            ->all();
    }

    /**
     * Ambil daftar organisasi di bawah BEM (BEM itu sendiri + HMJ/UKM aktif),
     * hanya jika BEM berstatus aktif.
     *
     * @param int $idBem
     * @return array
     */
    public function daftarOrganisasiDiBawahBem(int $idBem): array
    {
        $bem = Organisasi::where('id_organisasi', $idBem)
            ->where('jenis_organisasi', 'bem')
            ->where('status_organisasi', 'aktif')
            ->first();

        if (! $bem) {
            return [
                'error'  => 'BEM tidak ditemukan atau belum aktif',
                'status' => 404,
            ];
        }

        $anak = Organisasi::where('id_organisasi_induk', $idBem)
            ->whereIn('jenis_organisasi', ['hmj', 'ukm'])
            ->where('status_organisasi', 'aktif')
            ->orderBy('nama_organisasi', 'asc')
            ->get();

        $daftar = collect([$bem])->concat($anak)->map(fn (Organisasi $org) => [
            'id_organisasi'    => $org->id_organisasi,
            'nama_organisasi'  => $org->nama_organisasi,
            'jenis_organisasi' => $org->jenis_organisasi,
        ])->values()->all();

        return [
            'organisasi' => $daftar,
        ];
    }
}
