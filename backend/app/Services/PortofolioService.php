<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\Portofolio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PortofolioService
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
     * Tambah data portofolio baru dan kembalikan seluruh daftar portofolio terurut tahun DESC.
     */
    public function tambahPortofolio(Pengguna $pengguna, array $data, ?UploadedFile $dokumentasiFile = null): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $dokumentasi = $data['dokumentasi'] ?? null;
        if ($dokumentasiFile instanceof UploadedFile) {
            $path = $dokumentasiFile->store('portofolio', 'public');
            $dokumentasi = Storage::disk('public')->url($path);
        }

        Portofolio::create([
            'id_organisasi' => $organisasi->id_organisasi,
            'judul'         => $data['judul'],
            'tahun'         => (int) $data['tahun'],
            'deskripsi'     => $data['deskripsi'],
            'dokumentasi'   => $dokumentasi,
        ]);

        $daftarPortofolio = Portofolio::where('id_organisasi', $organisasi->id_organisasi)
            ->orderBy('tahun', 'desc')
            ->orderBy('id_portofolio', 'desc')
            ->get();

        return [
            'data' => $daftarPortofolio,
        ];
    }

    /**
     * Ambil seluruh daftar portofolio milik organisasi pengguna terurut tahun DESC.
     */
    public function getDaftarPortofolio(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $daftarPortofolio = Portofolio::where('id_organisasi', $organisasi->id_organisasi)
            ->orderBy('tahun', 'desc')
            ->orderBy('id_portofolio', 'desc')
            ->get();

        return [
            'data' => $daftarPortofolio,
        ];
    }
}
