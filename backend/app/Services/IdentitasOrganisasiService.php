<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdentitasOrganisasiService
{
    /**
     * Dapatkan organisasi aktif milik akun pengguna.
     */
    public function getOrganisasiAktif(Pengguna $pengguna): ?Organisasi
    {
        $organisasi = Organisasi::with(['universitas', 'bidang'])
            ->where('id_akun_organisasi', $pengguna->id_pengguna)
            ->first();

        if (! $organisasi || $organisasi->status_organisasi !== 'aktif') {
            return null;
        }

        return $organisasi;
    }

    /**
     * Ambil data identitas organisasi milik pengguna.
     */
    public function getIdentitas(Pengguna $pengguna): array
    {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        return [
            'data' => $this->formatIdentitasResponse($organisasi),
        ];
    }

    /**
     * Perbarui identitas organisasi beserta file logonya.
     */
    public function updateIdentitas(
        Pengguna $pengguna,
        array $data,
        ?UploadedFile $logoFile = null,
        ?UploadedFile $logoUniversitasFile = null
    ): array {
        $organisasi = $this->getOrganisasiAktif($pengguna);

        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif atau tidak ditemukan',
                'status' => 403,
            ];
        }

        $newUploadedFiles = [];
        $filesToDeleteOnSuccess = [];

        try {
            DB::transaction(function () use (
                $organisasi,
                $data,
                $logoFile,
                $logoUniversitasFile,
                &$newUploadedFiles,
                &$filesToDeleteOnSuccess
            ) {
                // 1. Tangani Logo Organisasi
                if ($logoFile) {
                    $filename = Str::random(40) . '.' . $logoFile->getClientOriginalExtension();
                    $pathLogo = $logoFile->storeAs('logo-organisasi', $filename, 'public');
                    $newUploadedFiles[] = $pathLogo;

                    if ($organisasi->logo && Storage::disk('public')->exists($organisasi->logo)) {
                        $filesToDeleteOnSuccess[] = $organisasi->logo;
                    }

                    $organisasi->logo = $pathLogo;
                }

                // 2. Tangani Logo Universitas (Khusus BEM)
                if ($logoUniversitasFile && $organisasi->jenis_organisasi === 'bem') {
                    $universitas = $organisasi->universitas;

                    if ($universitas) {
                        $univFilename = Str::random(40) . '.' . $logoUniversitasFile->getClientOriginalExtension();
                        $pathUnivLogo = $logoUniversitasFile->storeAs('logo-universitas', $univFilename, 'public');
                        $newUploadedFiles[] = $pathUnivLogo;

                        if ($universitas->logo && Storage::disk('public')->exists($universitas->logo)) {
                            $filesToDeleteOnSuccess[] = $universitas->logo;
                        }

                        $universitas->logo = $pathUnivLogo;
                        $universitas->save();
                    }
                }

                // 3. Update field identitas pada tabel organisasi
                $organisasi->id_bidang           = $data['id_bidang'];
                $organisasi->alamat              = $data['alamat'];
                $organisasi->tanggal_mulai_sk    = $data['tanggal_mulai_sk'];
                $organisasi->tanggal_berakhir_sk = $data['tanggal_berakhir_sk'];
                $organisasi->save();
            });

            // Hapus file lama setelah transaksi sukses
            foreach ($filesToDeleteOnSuccess as $oldPath) {
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $organisasi->refresh();
            $organisasi->load(['universitas', 'bidang']);

            return [
                'data' => $this->formatIdentitasResponse($organisasi),
            ];
        } catch (\Throwable $e) {
            // Bersihkan file yang baru diunggah jika transaksi gagal
            foreach ($newUploadedFiles as $newPath) {
                if (Storage::disk('public')->exists($newPath)) {
                    Storage::disk('public')->delete($newPath);
                }
            }

            throw $e;
        }
    }

    /**
     * Format data identitas organisasi untuk response JSON.
     */
    protected function formatIdentitasResponse(Organisasi $organisasi): array
    {
        $logoUrl = null;
        if ($organisasi->logo) {
            $logoUrl = Storage::disk('public')->exists($organisasi->logo)
                ? Storage::disk('public')->url($organisasi->logo)
                : null;
        }

        $logoUnivUrl = null;
        if ($organisasi->jenis_organisasi === 'bem' && $organisasi->universitas?->logo) {
            $logoUnivUrl = Storage::disk('public')->exists($organisasi->universitas->logo)
                ? Storage::disk('public')->url($organisasi->universitas->logo)
                : null;
        }

        return [
            'id_organisasi'       => $organisasi->id_organisasi,
            'nama_organisasi'     => $organisasi->nama_organisasi,
            'jenis_organisasi'    => $organisasi->jenis_organisasi,
            'id_universitas'      => $organisasi->id_universitas,
            'nama_universitas'    => $organisasi->universitas?->nama_universitas,
            'id_bidang'           => $organisasi->id_bidang,
            'nama_bidang'         => $organisasi->bidang?->nama_bidang,
            'alamat'              => $organisasi->alamat,
            'logo'                => $logoUrl,
            'logo_universitas'    => $logoUnivUrl,
            'tanggal_mulai_sk'    => $organisasi->tanggal_mulai_sk?->format('Y-m-d'),
            'tanggal_berakhir_sk' => $organisasi->tanggal_berakhir_sk?->format('Y-m-d'),
            'status_organisasi'   => $organisasi->status_organisasi,
        ];
    }
}
