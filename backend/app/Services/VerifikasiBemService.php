<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\PendaftaranOrganisasi;
use App\Models\ProfilAnggota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerifikasiBemService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Daftar pendaftaran BEM berstatus menunggu, terlama dulu.
     *
     * @return array
     */
    public function daftarPendaftaran(): array
    {
        return PendaftaranOrganisasi::where('status_pendaftaran', 'menunggu')
            ->whereHas('organisasi', function ($q) {
                $q->where('jenis_organisasi', 'bem');
            })
            ->with(['organisasi.universitas', 'organisasi.akun'])
            ->orderBy('tanggal_pendaftaran', 'asc')
            ->get()
            ->map(fn (PendaftaranOrganisasi $p) => [
                'id_pendaftaran_organisasi' => $p->id_pendaftaran_organisasi,
                'nama_organisasi'           => $p->organisasi->nama_organisasi,
                'id_universitas'            => $p->organisasi->id_universitas,
                'nama_universitas'          => $p->organisasi->universitas?->nama_universitas,
                'nama_instansi'             => $p->organisasi->universitas?->nama_universitas,
                'nama_penanggung_jawab'     => $p->organisasi->akun?->nama,
                'tanggal_pendaftaran'       => $p->tanggal_pendaftaran->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * Detail satu pendaftaran BEM.
     *
     * @return array
     */
    public function detailPendaftaran(int $id): array
    {
        $pendaftaran = PendaftaranOrganisasi::with(['organisasi.universitas', 'organisasi.akun.profilAnggota', 'files'])
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || $pendaftaran->organisasi?->jenis_organisasi !== 'bem') {
            return [
                'error'  => 'Pendaftaran BEM tidak ditemukan',
                'status' => 404,
            ];
        }

        $organisasi = $pendaftaran->organisasi;
        $pendaftar  = $organisasi?->akun;
        $profil     = $pendaftar?->profilAnggota;

        return [
            'pendaftaran' => [
                'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                'jenis_organisasi'          => $organisasi->jenis_organisasi,
                'nama_organisasi'           => $organisasi->nama_organisasi,
                'id_universitas'            => $organisasi->id_universitas,
                'nama_universitas'          => $organisasi->universitas?->nama_universitas,
                'nama_instansi'             => $organisasi->universitas?->nama_universitas,
                'nomor_telepon_organisasi'  => $organisasi->nomor_telepon,
                'status_pendaftaran'        => $pendaftaran->status_pendaftaran,
                'tanggal_pendaftaran'       => $pendaftaran->tanggal_pendaftaran->toISOString(),
                'tanggal_verifikasi'        => $pendaftaran->tanggal_verifikasi?->toISOString(),
                'alasan_penolakan'          => $pendaftaran->alasan_penolakan,
                'penanggung_jawab'          => [
                    'nama'           => $pendaftar?->nama,
                    'nim'            => $profil?->nim,
                    'nomor_whatsapp' => $profil?->nomor_whatsapp,
                    'username'       => $pendaftar?->username,
                ],
                'dokumen' => [
                    'ktm'              => "/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/ktm",
                    'sk_organisasi'    => "/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-organisasi",
                    'sk_kepengurusan'  => "/api/admin/registrations/bem/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-kepengurusan",
                ],
            ],
        ];
    }

    /**
     * Ambil path file dokumen pendaftaran BEM.
     *
     * @return array
     */
    public function ambilDokumen(int $id, string $jenis): array
    {
        $validJenis = ['ktm', 'sk-organisasi', 'sk-kepengurusan'];

        if (! in_array($jenis, $validJenis, true)) {
            return [
                'error'  => 'Jenis dokumen tidak valid',
                'status' => 404,
            ];
        }

        $pendaftaran = PendaftaranOrganisasi::with(['organisasi.akun.profilAnggota', 'files'])
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || $pendaftaran->organisasi?->jenis_organisasi !== 'bem') {
            return [
                'error'  => 'Pendaftaran BEM tidak ditemukan',
                'status' => 404,
            ];
        }

        $path = null;
        if ($jenis === 'ktm') {
            $path = $pendaftaran->organisasi?->akun?->profilAnggota?->foto_ktm;
        } elseif ($jenis === 'sk-organisasi') {
            $file = $pendaftaran->files->firstWhere('jenis_dokumen', 'sk_organisasi');
            $path = $file?->path_file;
        } elseif ($jenis === 'sk-kepengurusan') {
            $file = $pendaftaran->files->firstWhere('jenis_dokumen', 'sk_kepengurusan');
            $path = $file?->path_file;
        }

        if (! $path) {
            return [
                'error'  => 'File dokumen tidak ditemukan',
                'status' => 404,
            ];
        }

        return [
            'path' => $path,
        ];
    }

    /**
     * Setujui pendaftaran BEM.
     *
     * @return array
     */
    public function setujui(int $id, int $idPelaku): array
    {
        $pendaftaran = PendaftaranOrganisasi::with('organisasi')
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || $pendaftaran->organisasi?->jenis_organisasi !== 'bem') {
            return [
                'error'  => 'Pendaftaran BEM tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pendaftaran->status_pendaftaran !== 'menunggu') {
            return [
                'error'  => 'Pendaftaran sudah diputuskan',
                'status' => 409,
            ];
        }

        return DB::transaction(function () use ($pendaftaran, $idPelaku) {
            $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                ->lockForUpdate()
                ->first();

            if ($pendaftaran->status_pendaftaran !== 'menunggu') {
                return [
                    'error'  => 'Pendaftaran sudah diputuskan',
                    'status' => 409,
                ];
            }

            $organisasi = Organisasi::where('id_organisasi', $pendaftaran->id_organisasi)->first();

            // 1. Organisasi diaktifkan
            if ($organisasi) {
                $organisasi->update(['status_organisasi' => 'aktif']);
            }

            // 2. Update pendaftaran
            $pendaftaran->update([
                'status_pendaftaran'      => 'disetujui',
                'id_pengguna_verifikator' => $idPelaku,
                'tanggal_verifikasi'      => now(),
            ]);

            // 3. Aktifkan akun pendaftar dan hapus semua tokennya
            if ($organisasi && $organisasi->id_akun_organisasi) {
                $pengguna = Pengguna::find($organisasi->id_akun_organisasi);
                if ($pengguna) {
                    $pengguna->update(['status_akun' => 'aktif']);
                    $pengguna->tokens()->delete();
                }
            }

            // 4. Catat log aktivitas (pelaku log = id verifikator)
            $namaOrg = $organisasi?->nama_organisasi ?? 'BEM';
            $this->logAktivitasService->catat(
                $idPelaku,
                'verifikasi',
                "Menyetujui pendaftaran BEM {$namaOrg}"
            );

            return [
                'message' => 'Registrasi BEM berhasil disetujui',
            ];
        });
    }

    /**
     * Tolak pendaftaran BEM.
     *
     * @return array
     */
    public function tolak(int $id, string $alasan, int $idPelaku): array
    {
        $pendaftaran = PendaftaranOrganisasi::with('organisasi')
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || $pendaftaran->organisasi?->jenis_organisasi !== 'bem') {
            return [
                'error'  => 'Pendaftaran BEM tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pendaftaran->status_pendaftaran !== 'menunggu') {
            return [
                'error'  => 'Pendaftaran sudah diputuskan',
                'status' => 409,
            ];
        }

        return DB::transaction(function () use ($pendaftaran, $alasan, $idPelaku) {
            $pendaftaran = PendaftaranOrganisasi::with(['files', 'organisasi.akun.profilAnggota'])
                ->where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                ->lockForUpdate()
                ->first();

            if ($pendaftaran->status_pendaftaran !== 'menunggu') {
                return [
                    'error'  => 'Pendaftaran sudah diputuskan',
                    'status' => 409,
                ];
            }

            $organisasi = $pendaftaran->organisasi;
            $pengguna   = $organisasi?->akun;
            $profil     = $pengguna?->profilAnggota;
            $namaOrg    = $organisasi?->nama_organisasi ?? 'BEM';

            // Kumpulkan semua path file yang harus dihapus dari storage
            $filesToDelete = [];
            foreach ($pendaftaran->files as $file) {
                if ($file->path_file) {
                    $filesToDelete[] = $file->path_file;
                }
            }
            if ($profil?->foto_ktm) {
                $filesToDelete[] = $profil->foto_ktm;
            }
            if ($profil?->foto_profil) {
                $filesToDelete[] = $profil->foto_profil;
            }

            // 1. Catat log aktivitas SEBELUM hapus data agar konteks tercatat
            $this->logAktivitasService->catat(
                $idPelaku,
                'verifikasi',
                "Menolak pendaftaran BEM {$namaOrg} dengan alasan: {$alasan}"
            );

            // 2. Hapus relasi files
            $pendaftaran->files()->delete();

            // 3. Hapus pendaftaran organisasi
            $pendaftaran->delete();

            // 4. Hapus organisasi (kontak dan portofolio cascade jika ada)
            if ($organisasi) {
                $organisasi->delete();
            }

            // 5. Hapus profil anggota, log aktivitas pendaftar, & akun pengguna
            if ($profil) {
                $profil->delete();
            }
            if ($pengguna) {
                \App\Models\LogAktivitas::where('id_pengguna', $pengguna->id_pengguna)->delete();
                $pengguna->tokens()->delete();
                $pengguna->delete();
            }

            // 6. Hapus file fisik dari disk storage
            foreach ($filesToDelete as $filePath) {
                Storage::disk('local')->delete($filePath);
            }

            return [
                'message' => 'Pendaftaran ditolak dan data dibersihkan secara permanen.',
            ];
        });
    }
}
