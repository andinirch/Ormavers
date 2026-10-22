<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\PendaftaranOrganisasi;
use App\Models\RiwayatVerifikasi;
use Illuminate\Support\Facades\DB;

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
        return PendaftaranOrganisasi::where('jenis_organisasi', 'bem')
            ->where('status_pendaftaran', 'menunggu')
            ->orderBy('tanggal_pendaftaran', 'asc')
            ->get()
            ->map(fn (PendaftaranOrganisasi $p) => [
                'id_pendaftaran_organisasi' => $p->id_pendaftaran_organisasi,
                'nama_organisasi'           => $p->nama_organisasi,
                'nama_instansi'             => $p->nama_instansi,
                'nama_penanggung_jawab'     => $p->nama_penanggung_jawab,
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
        $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || $pendaftaran->jenis_organisasi !== 'bem') {
            return [
                'error'  => 'Pendaftaran BEM tidak ditemukan',
                'status' => 404,
            ];
        }

        $pendaftar = $pendaftaran->pendaftar;

        return [
            'pendaftaran' => [
                'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                'jenis_organisasi'          => $pendaftaran->jenis_organisasi,
                'nama_organisasi'           => $pendaftaran->nama_organisasi,
                'nama_instansi'             => $pendaftaran->nama_instansi,
                'nomor_telepon_organisasi'  => $pendaftaran->nomor_telepon_organisasi,
                'status_pendaftaran'        => $pendaftaran->status_pendaftaran,
                'tanggal_pendaftaran'       => $pendaftaran->tanggal_pendaftaran->toISOString(),
                'tanggal_verifikasi'        => $pendaftaran->tanggal_verifikasi?->toISOString(),
                'alasan_penolakan'          => $pendaftaran->alasan_penolakan,
                'penanggung_jawab'          => [
                    'nama'           => $pendaftaran->nama_penanggung_jawab,
                    'nim'            => $pendaftaran->nim_penanggung_jawab,
                    'nomor_whatsapp' => $pendaftaran->nomor_whatsapp,
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
        $mapKolom = [
            'ktm'              => 'foto_ktm',
            'sk-organisasi'    => 'dokumen_sk_organisasi',
            'sk-kepengurusan'  => 'dokumen_sk_kepengurusan',
        ];

        if (! isset($mapKolom[$jenis])) {
            return [
                'error'  => 'Jenis dokumen tidak valid',
                'status' => 404,
            ];
        }

        $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $id)
            ->first();

        if (! $pendaftaran || $pendaftaran->jenis_organisasi !== 'bem') {
            return [
                'error'  => 'Pendaftaran BEM tidak ditemukan',
                'status' => 404,
            ];
        }

        $kolom = $mapKolom[$jenis];
        $path  = $pendaftaran->{$kolom};

        if (! $path) {
            return [
                'error'  => 'Dokumen tidak ditemukan',
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
    public function setujui(int $id, Pengguna $verifikator): array
    {
        $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $id)
            ->where('jenis_organisasi', 'bem')
            ->first();

        if (! $pendaftaran) {
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

        return DB::transaction(function () use ($pendaftaran, $verifikator) {
            // Kunci baris
            $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                ->lockForUpdate()
                ->first();

            // Double-check setelah lock
            if ($pendaftaran->status_pendaftaran !== 'menunggu') {
                return [
                    'error'  => 'Pendaftaran sudah diputuskan',
                    'status' => 409,
                ];
            }

            // 1. Buat organisasi
            $organisasi = Organisasi::create([
                'id_pengguna_akun'    => $pendaftaran->id_pengguna_pendaftar,
                'id_organisasi_induk' => null,
                'nama_organisasi'     => $pendaftaran->nama_organisasi,
                'jenis_organisasi'    => 'bem',
                'nama_instansi'       => $pendaftaran->nama_instansi,
                'nomor_telepon'       => $pendaftaran->nomor_telepon_organisasi,
                'nomor_whatsapp'      => $pendaftaran->nomor_whatsapp,
                'status_organisasi'   => 'aktif',
            ]);

            // 2. Update pendaftaran
            $pendaftaran->update([
                'status_pendaftaran'  => 'disetujui',
                'id_organisasi'       => $organisasi->id_organisasi,
                'tanggal_verifikasi'  => now(),
            ]);

            // 3. Aktifkan akun pendaftar
            Pengguna::where('id_pengguna', $pendaftaran->id_pengguna_pendaftar)
                ->update(['status_akun' => 'aktif']);

            // 4. Catat riwayat verifikasi
            RiwayatVerifikasi::create([
                'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                'id_pengguna_verifikator'   => $verifikator->id_pengguna,
                'status_sebelumnya'         => 'menunggu',
                'status_sesudahnya'         => 'disetujui',
                'tanggal_verifikasi'        => now(),
            ]);

            // 5. Catat log aktivitas
            $this->logAktivitasService->catat(
                $verifikator->id_pengguna,
                'verifikasi',
                "Menyetujui pendaftaran BEM {$pendaftaran->nama_organisasi}"
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
    public function tolak(int $id, string $alasan, Pengguna $verifikator): array
    {
        $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $id)
            ->where('jenis_organisasi', 'bem')
            ->first();

        if (! $pendaftaran) {
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

        return DB::transaction(function () use ($pendaftaran, $alasan, $verifikator) {
            // Kunci baris
            $pendaftaran = PendaftaranOrganisasi::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                ->lockForUpdate()
                ->first();

            // Double-check setelah lock
            if ($pendaftaran->status_pendaftaran !== 'menunggu') {
                return [
                    'error'  => 'Pendaftaran sudah diputuskan',
                    'status' => 409,
                ];
            }

            // 1. Update pendaftaran
            $pendaftaran->update([
                'status_pendaftaran'  => 'ditolak',
                'alasan_penolakan'    => $alasan,
                'tanggal_verifikasi'  => now(),
            ]);

            // 2. Update status akun pendaftar
            Pengguna::where('id_pengguna', $pendaftaran->id_pengguna_pendaftar)
                ->update(['status_akun' => 'ditolak']);

            // 3. Catat riwayat verifikasi
            RiwayatVerifikasi::create([
                'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                'id_pengguna_verifikator'   => $verifikator->id_pengguna,
                'status_sebelumnya'         => 'menunggu',
                'status_sesudahnya'         => 'ditolak',
                'catatan_verifikasi'        => $alasan,
                'tanggal_verifikasi'        => now(),
            ]);

            // 4. Catat log aktivitas
            $this->logAktivitasService->catat(
                $verifikator->id_pengguna,
                'verifikasi',
                "Menolak pendaftaran BEM {$pendaftaran->nama_organisasi} dengan alasan: {$alasan}"
            );

            return [
                'message' => 'Registrasi BEM berhasil ditolak',
            ];
        });
    }
}
