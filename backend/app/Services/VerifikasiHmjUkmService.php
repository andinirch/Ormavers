<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerifikasiHmjUkmService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Dapatkan organisasi BEM aktif dari pengguna.
     */
    protected function getBemAktif(Pengguna $pengguna): ?Organisasi
    {
        $org = $pengguna->organisasi;

        if (! $org || $org->jenis_organisasi !== 'bem' || $org->status_organisasi !== 'aktif') {
            return null;
        }

        return $org;
    }

    /**
     * Daftar pendaftaran HMJ/UKM berstatus menunggu di universitas yang sama dengan BEM.
     *
     * @return array
     */
    public function daftarPendaftaran(Pengguna $userBem): array
    {
        $bem = $this->getBemAktif($userBem);
        if (! $bem) {
            return [
                'error'  => 'Akun BEM tidak aktif',
                'status' => 403,
            ];
        }

        $pendaftaran = PendaftaranOrganisasi::where('status_pendaftaran', 'menunggu')
            ->whereHas('organisasi', function ($q) use ($bem) {
                $q->whereIn('jenis_organisasi', ['hmj', 'ukm'])
                  ->where('id_universitas', $bem->id_universitas);
            })
            ->with(['organisasi.akun'])
            ->orderBy('tanggal_pendaftaran', 'asc')
            ->get()
            ->map(fn (PendaftaranOrganisasi $p) => [
                'id_pendaftaran_organisasi' => $p->id_pendaftaran_organisasi,
                'nama_organisasi'           => $p->organisasi->nama_organisasi,
                'jenis_organisasi'          => $p->organisasi->jenis_organisasi,
                'nama_penanggung_jawab'     => $p->organisasi->akun?->nama,
                'tanggal_pendaftaran'       => $p->tanggal_pendaftaran?->toISOString(),
            ])
            ->values()
            ->all();

        return [
            'pendaftaran' => $pendaftaran,
        ];
    }

    /**
     * Detail satu pendaftaran HMJ/UKM.
     *
     * @return array
     */
    public function detailPendaftaran(int $id, Pengguna $userBem): array
    {
        $bem = $this->getBemAktif($userBem);
        if (! $bem) {
            return [
                'error'  => 'Akun BEM tidak aktif',
                'status' => 403,
            ];
        }

        $pendaftaran = PendaftaranOrganisasi::with(['organisasi.akun.profilAnggota', 'files'])
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || ! in_array($pendaftaran->organisasi?->jenis_organisasi, ['hmj', 'ukm'], true)) {
            return [
                'error'  => 'Pendaftaran tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pendaftaran->organisasi->id_universitas !== $bem->id_universitas) {
            return [
                'error'  => 'Registrasi ini bukan dari universitas Anda',
                'status' => 403,
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
                'nomor_telepon_organisasi'  => $organisasi->nomor_telepon,
                'status_pendaftaran'        => $pendaftaran->status_pendaftaran,
                'tanggal_pendaftaran'       => $pendaftaran->tanggal_pendaftaran?->toISOString(),
                'tanggal_verifikasi'        => $pendaftaran->tanggal_verifikasi?->toISOString(),
                'alasan_penolakan'          => $pendaftaran->alasan_penolakan,
                'penanggung_jawab'          => [
                    'nama'           => $pendaftar?->nama,
                    'nim'            => $profil?->nim,
                    'nomor_whatsapp' => $profil?->nomor_whatsapp,
                    'username'       => $pendaftar?->username,
                    'status'         => $pendaftar?->status_akun,
                ],
                'dokumen' => [
                    'ktm'             => "/api/org/registrations/hmj-ukm/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/ktm",
                    'sk_organisasi'   => "/api/org/registrations/hmj-ukm/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-organisasi",
                    'sk_kepengurusan' => "/api/org/registrations/hmj-ukm/{$pendaftaran->id_pendaftaran_organisasi}/dokumen/sk-kepengurusan",
                ],
            ],
        ];
    }

    /**
     * Ambil path file dokumen pendaftaran HMJ/UKM.
     *
     * @return array
     */
    public function ambilDokumen(int $id, string $jenis, Pengguna $userBem): array
    {
        $bem = $this->getBemAktif($userBem);
        if (! $bem) {
            return [
                'error'  => 'Akun BEM tidak aktif',
                'status' => 403,
            ];
        }

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

        if (! $pendaftaran || ! in_array($pendaftaran->organisasi?->jenis_organisasi, ['hmj', 'ukm'], true)) {
            return [
                'error'  => 'Pendaftaran tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pendaftaran->organisasi->id_universitas !== $bem->id_universitas) {
            return [
                'error'  => 'Registrasi ini bukan dari universitas Anda',
                'status' => 403,
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
     * Setujui pendaftaran HMJ/UKM.
     *
     * @return array
     */
    public function setujui(int $id, Pengguna $userBem, int $idPelaku): array
    {
        $bem = $this->getBemAktif($userBem);
        if (! $bem) {
            return [
                'error'  => 'Akun BEM tidak aktif',
                'status' => 403,
            ];
        }

        $pendaftaran = PendaftaranOrganisasi::with('organisasi')
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || ! in_array($pendaftaran->organisasi?->jenis_organisasi, ['hmj', 'ukm'], true)) {
            return [
                'error'  => 'Pendaftaran tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pendaftaran->organisasi->id_universitas !== $bem->id_universitas) {
            return [
                'error'  => 'Registrasi ini bukan dari universitas Anda',
                'status' => 403,
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
            $jenisLabel = strtoupper($organisasi?->jenis_organisasi ?? 'organisasi');
            $namaOrg = $organisasi?->nama_organisasi ?? '';
            $this->logAktivitasService->catat(
                $idPelaku,
                'verifikasi',
                "Menyetujui pendaftaran {$jenisLabel} {$namaOrg}"
            );

            return [
                'message' => 'Registrasi HMJ/UKM berhasil disetujui',
            ];
        });
    }

    /**
     * Tolak pendaftaran HMJ/UKM.
     *
     * @return array
     */
    public function tolak(int $id, string $alasan, Pengguna $userBem, int $idPelaku): array
    {
        $bem = $this->getBemAktif($userBem);
        if (! $bem) {
            return [
                'error'  => 'Akun BEM tidak aktif',
                'status' => 403,
            ];
        }

        $pendaftaran = PendaftaranOrganisasi::with('organisasi')
            ->where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || ! in_array($pendaftaran->organisasi?->jenis_organisasi, ['hmj', 'ukm'], true)) {
            return [
                'error'  => 'Pendaftaran tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($pendaftaran->organisasi->id_universitas !== $bem->id_universitas) {
            return [
                'error'  => 'Registrasi ini bukan dari universitas Anda',
                'status' => 403,
            ];
        }

        if ($pendaftaran->status_pendaftaran !== 'menunggu') {
            return [
                'error'  => 'Pendaftaran sudah diputuskan',
                'status' => 409,
            ];
        }

        return DB::transaction(function () use ($pendaftaran, $alasan, $idPelaku) {
            $pendaftaranLocked = PendaftaranOrganisasi::with('organisasi')
                ->where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                ->lockForUpdate()
                ->first();

            if (! $pendaftaranLocked || $pendaftaranLocked->status_pendaftaran !== 'menunggu') {
                return [
                    'error'  => 'Pendaftaran sudah diputuskan',
                    'status' => 409,
                ];
            }

            $organisasi = $pendaftaranLocked->organisasi;
            $jenisLabel = strtoupper($organisasi?->jenis_organisasi ?? 'organisasi');
            $namaOrg = $organisasi?->nama_organisasi ?? '';

            // 1. Catat log aktivitas penolakan
            $this->logAktivitasService->catat(
                $idPelaku,
                'verifikasi',
                "Menolak pendaftaran {$jenisLabel} {$namaOrg} dengan alasan: {$alasan}"
            );

            // 2. Update status pendaftaran menjadi 'ditolak' dan simpan alasannya
            PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                ->update([
                    'status_pendaftaran'      => 'ditolak',
                    'alasan_penolakan'        => $alasan,
                    'id_pengguna_verifikator' => $idPelaku,
                    'tanggal_verifikasi'      => now(),
                ]);

            return [
                'message' => "Pendaftaran {$jenisLabel} berhasil ditolak",
            ];
        });
    }
}