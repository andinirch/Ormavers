<?php

namespace App\Services;

use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;

class OrgMemberRequestService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

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
     * Mengambil daftar keanggotaan berstatus menunggu milik organisasi tersebut,
     * terlama dulu (tanggal_pendaftaran ASC).
     */
    public function daftarRequests(Pengguna $user, int $page = 1, int $perPage = 10): array
    {
        $organisasi = $this->getOrganisasiAktif($user);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif',
                'status' => 403,
            ];
        }

        $perPage = max(1, min($perPage, 50));

        $paginated = Keanggotaan::where('id_organisasi', $organisasi->id_organisasi)
            ->where('status_keanggotaan', 'menunggu')
            ->with('pengguna.profilAnggota')
            ->orderBy('tanggal_pendaftaran', 'asc')
            ->orderBy('id_keanggotaan', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $requests = $paginated->getCollection()->map(function (Keanggotaan $k) {
            return [
                'id_keanggotaan'      => $k->id_keanggotaan,
                'nama'                => $k->pengguna?->nama,
                'nim'                 => $k->pengguna?->profilAnggota?->nim,
                'tanggal_pendaftaran' => $k->tanggal_pendaftaran?->toISOString(),
            ];
        })->values()->all();

        return [
            'requests' => $requests,
            'meta'     => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Detail satu permintaan keanggotaan.
     */
    public function detailRequest(int $id, Pengguna $user): array
    {
        $organisasi = $this->getOrganisasiAktif($user);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif',
                'status' => 403,
            ];
        }

        $keanggotaan = Keanggotaan::with('pengguna.profilAnggota')
            ->where('id_keanggotaan', $id)
            ->first();

        if (! $keanggotaan) {
            return [
                'error'  => 'Permintaan keanggotaan tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($keanggotaan->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Permintaan ini bukan milik organisasi Anda',
                'status' => 403,
            ];
        }

        $pengguna = $keanggotaan->pengguna;
        $profil   = $pengguna?->profilAnggota;

        return [
            'id_keanggotaan'      => $keanggotaan->id_keanggotaan,
            'nama'                => $pengguna?->nama,
            'nim'                 => $profil?->nim,
            'username'            => $pengguna?->username,
            'status_keanggotaan'  => $keanggotaan->status_keanggotaan,
            'alasan_penolakan'    => $keanggotaan->alasan_penolakan,
            'tanggal_pendaftaran' => $keanggotaan->tanggal_pendaftaran?->toISOString(),
            'tanggal_verifikasi'  => $keanggotaan->tanggal_verifikasi?->toISOString(),
            'dokumen'             => [
                'ktm' => "/api/org/member-requests/{$keanggotaan->id_keanggotaan}/dokumen/ktm",
            ],
        ];
    }

    /**
     * Ambil path file dokumen KTM keanggotaan.
     */
    public function ambilKtmPath(int $id, Pengguna $user): array
    {
        $organisasi = $this->getOrganisasiAktif($user);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif',
                'status' => 403,
            ];
        }

        $keanggotaan = Keanggotaan::with('pengguna.profilAnggota')
            ->where('id_keanggotaan', $id)
            ->first();

        if (! $keanggotaan) {
            return [
                'error'  => 'Permintaan keanggotaan tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($keanggotaan->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Permintaan ini bukan milik organisasi Anda',
                'status' => 403,
            ];
        }

        $path = $keanggotaan->pengguna?->profilAnggota?->foto_ktm;

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
     * Setujui pendaftaran anggota.
     */
    public function approve(int $id, Pengguna $user, int $idPelaku): array
    {
        $organisasi = $this->getOrganisasiAktif($user);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif',
                'status' => 403,
            ];
        }

        $keanggotaan = Keanggotaan::with(['pengguna', 'organisasi'])
            ->where('id_keanggotaan', $id)
            ->first();

        if (! $keanggotaan) {
            return [
                'error'  => 'Permintaan keanggotaan tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($keanggotaan->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Permintaan ini bukan milik organisasi Anda',
                'status' => 403,
            ];
        }

        if ($keanggotaan->status_keanggotaan !== 'menunggu') {
            return [
                'error'  => 'Permintaan sudah diputuskan',
                'status' => 409,
            ];
        }

        return DB::transaction(function () use ($keanggotaan, $organisasi, $idPelaku) {
            $locked = Keanggotaan::where('id_keanggotaan', $keanggotaan->id_keanggotaan)
                ->lockForUpdate()
                ->first();

            if ($locked->status_keanggotaan !== 'menunggu') {
                return [
                    'error'  => 'Permintaan sudah diputuskan',
                    'status' => 409,
                ];
            }

            // 1. Update keanggotaan
            $locked->update([
                'status_keanggotaan'      => 'disetujui',
                'id_pengguna_verifikator' => $idPelaku,
                'tanggal_verifikasi'      => now(),
            ]);

            // 2. Update pengguna status_akun = aktif dan hapus semua token
            $anggota = Pengguna::find($locked->id_pengguna);
            if ($anggota) {
                $anggota->update(['status_akun' => 'aktif']);
                $anggota->tokens()->delete();
            }

            // 3. Catat log aktivitas jenis 'verifikasi'
            $namaAnggota = $anggota?->nama ?? '';
            $namaOrg = $organisasi->nama_organisasi;
            $this->logAktivitasService->catat(
                $idPelaku,
                'verifikasi',
                "Menyetujui keanggotaan {$namaAnggota} di {$namaOrg}"
            );

            return [
                'message' => 'Keanggotaan berhasil disetujui',
            ];
        });
    }

    /**
     * Tolak pendaftaran anggota.
     */
    /**
     * Tolak pendaftaran anggota.
     */
    public function reject(int $id, string $alasan, Pengguna $user, int $idPelaku): array
    {
        $organisasi = $this->getOrganisasiAktif($user);
        if (! $organisasi) {
            return [
                'error'  => 'Akun organisasi belum aktif',
                'status' => 403,
            ];
        }

        $keanggotaan = Keanggotaan::with(['pengguna', 'organisasi'])
            ->where('id_keanggotaan', $id)
            ->first();

        if (! $keanggotaan) {
            return [
                'error'  => 'Permintaan keanggotaan tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($keanggotaan->id_organisasi !== $organisasi->id_organisasi) {
            return [
                'error'  => 'Permintaan ini bukan milik organisasi Anda',
                'status' => 403,
            ];
        }

        if ($keanggotaan->status_keanggotaan !== 'menunggu') {
            return [
                'error'  => 'Permintaan sudah diputuskan',
                'status' => 409,
            ];
        }

        return DB::transaction(function () use ($keanggotaan, $organisasi, $alasan, $idPelaku) {
            $locked = Keanggotaan::where('id_keanggotaan', $keanggotaan->id_keanggotaan)
                ->lockForUpdate()
                ->first();

            if ($locked->status_keanggotaan !== 'menunggu') {
                return [
                    'error'  => 'Permintaan sudah diputuskan',
                    'status' => 409,
                ];
            }

            $anggota = Pengguna::find($locked->id_pengguna);
            $namaAnggota = $anggota?->nama ?? '';
            $namaOrg = $organisasi->nama_organisasi;

            // 1. Catat log aktivitas verifikasi
            $this->logAktivitasService->catat(
                $idPelaku,
                'verifikasi',
                "Menolak keanggotaan {$namaAnggota} di {$namaOrg} dengan alasan: {$alasan}"
            );

            // 2. Ubah status keanggotaan menjadi 'ditolak' dan simpan alasannya
            $locked->update([
                'status_keanggotaan'      => 'ditolak',
                'alasan_penolakan'        => $alasan,
                'id_pengguna_verifikator' => $idPelaku,
                'tanggal_verifikasi'      => now(),
            ]);

            return [
                'message' => 'Keanggotaan berhasil ditolak',
            ];
        });
    }
}
