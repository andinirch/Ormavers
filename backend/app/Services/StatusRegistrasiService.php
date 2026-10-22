<?php

namespace App\Services;

use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StatusRegistrasiService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Ambil data status registrasi pendaftar (organisasi atau anggota).
     *
     * @return array
     */
    public function ambilStatus(Pengguna $pengguna): array
    {
        $organisasi = $pengguna->organisasi()->with(['universitas', 'pendaftaran.files'])->first();

        if ($organisasi) {
            return $this->formatStatusOrganisasi($pengguna, $organisasi);
        }

        $keanggotaan = $pengguna->keanggotaan()->with('organisasi')->first();

        if ($keanggotaan) {
            return $this->formatStatusAnggota($pengguna, $keanggotaan);
        }

        return [
            'error'  => 'Data registrasi tidak ditemukan',
            'status' => 404,
        ];
    }

    /**
     * Format data status pendaftar organisasi.
     */
    public function formatStatusOrganisasi(Pengguna $pengguna, $organisasi = null): array
    {
        $organisasi = $organisasi ?? $pengguna->organisasi()->with(['universitas', 'pendaftaran.files'])->first();
        $pendaftaran = $organisasi?->pendaftaran;

        $statusPendaftaran = $pendaftaran?->status_pendaftaran ?? 'menunggu';
        $alasanPenolakan   = ($statusPendaftaran === 'ditolak') ? $pendaftaran?->alasan_penolakan : null;
        $bisaUnggahUlang   = ($statusPendaftaran === 'ditolak');

        $dokumen = $pendaftaran?->files->map(fn ($f) => [
            'jenis_dokumen'  => $f->jenis_dokumen,
            'nama_file_asli' => $f->nama_file_asli,
            'ukuran_kb'      => $f->ukuran_kb,
            'diunggah_pada'  => $f->diunggah_pada?->toISOString(),
        ])->values()->all() ?? [];

        return [
            'pendaftar' => [
                'id_pengguna'     => $pengguna->id_pengguna,
                'nama'            => $pengguna->nama,
                'username'        => $pengguna->username,
                'jenis_pendaftar' => 'organisasi',
            ],
            'status_pendaftaran'  => $statusPendaftaran,
            'alasan_penolakan'    => $alasanPenolakan,
            'organisasi'          => [
                'id_organisasi'     => $organisasi->id_organisasi,
                'nama_organisasi'   => $organisasi->nama_organisasi,
                'jenis_organisasi'  => $organisasi->jenis_organisasi,
                'nama_universitas'  => $organisasi->universitas?->nama_universitas,
                'status_organisasi' => $organisasi->status_organisasi,
            ],
            'dokumen'             => $dokumen,
            'bisa_unggah_ulang'   => $bisaUnggahUlang,
            'batas_ukuran_sk_mb'  => 2,
            'tanggal_pendaftaran' => $pendaftaran?->tanggal_pendaftaran?->toISOString(),
            'tanggal_verifikasi'  => $pendaftaran?->tanggal_verifikasi?->toISOString(),
        ];
    }

    /**
     * Format data status pendaftar anggota.
     */
    public function formatStatusAnggota(Pengguna $pengguna, $keanggotaan = null): array
    {
        $keanggotaan = $keanggotaan ?? $pengguna->keanggotaan()->with('organisasi')->first();

        $statusPendaftaran = $keanggotaan->status_keanggotaan;
        $alasanPenolakan   = ($statusPendaftaran === 'ditolak') ? $keanggotaan->alasan_penolakan : null;

        return [
            'pendaftar' => [
                'id_pengguna'     => $pengguna->id_pengguna,
                'nama'            => $pengguna->nama,
                'username'        => $pengguna->username,
                'jenis_pendaftar' => 'anggota',
            ],
            'status_pendaftaran'  => $statusPendaftaran,
            'alasan_penolakan'    => $alasanPenolakan,
            'organisasi'          => [
                'id_organisasi'   => $keanggotaan->organisasi?->id_organisasi,
                'nama_organisasi' => $keanggotaan->organisasi?->nama_organisasi,
            ],
            'bisa_unggah_ulang'   => false,
            'batas_ukuran_sk_mb'  => 2,
            'tanggal_pendaftaran' => $keanggotaan->tanggal_pendaftaran?->toISOString(),
            'tanggal_verifikasi'  => $keanggotaan->tanggal_verifikasi?->toISOString(),
        ];
    }

    /**
     * Unggah ulang berkas SK untuk pendaftaran yang ditolak.
     *
     * @return array
     */
    public function resubmitSk(Pengguna $pengguna, array $files, int $idPelaku): array
    {
        $organisasi = $pengguna->organisasi()->with('pendaftaran')->first();

        if (! $organisasi) {
            $keanggotaan = $pengguna->keanggotaan()->first();
            if ($keanggotaan) {
                return [
                    'error'  => 'Anggota tidak dapat mengunggah dokumen',
                    'status' => 403,
                ];
            }

            return [
                'error'  => 'Data registrasi tidak ditemukan',
                'status' => 404,
            ];
        }

        $pendaftaran = $organisasi->pendaftaran;

        if (! $pendaftaran || $pendaftaran->status_pendaftaran !== 'ditolak') {
            return [
                'error'  => 'Hanya pendaftaran yang ditolak yang dapat memperbarui dokumen',
                'status' => 409,
            ];
        }

        $filesBaru = [];
        $filesLama = [];

        try {
            $result = DB::transaction(function () use (
                $pengguna,
                $organisasi,
                $pendaftaran,
                $files,
                $idPelaku,
                &$filesBaru,
                &$filesLama
            ) {
                if (isset($files['sk_organisasi']) && $files['sk_organisasi']) {
                    $pathBaru = $files['sk_organisasi']->store('pendaftaran/sk_organisasi', 'local');
                    $filesBaru[] = $pathBaru;

                    $fileLama = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                        ->where('jenis_dokumen', 'sk_organisasi')
                        ->first();

                    if ($fileLama?->path_file) {
                        $filesLama[] = $fileLama->path_file;
                    }

                    PendaftaranOrganisasiFile::updateOrCreate(
                        [
                            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                            'jenis_dokumen'             => 'sk_organisasi',
                        ],
                        [
                            'nama_file_asli' => $files['sk_organisasi']->getClientOriginalName(),
                            'path_file'      => $pathBaru,
                            'ukuran_kb'      => (int) ceil($files['sk_organisasi']->getSize() / 1024),
                            'diunggah_pada'  => now(),
                        ]
                    );
                }

                if (isset($files['sk_kepengurusan']) && $files['sk_kepengurusan']) {
                    $pathBaru = $files['sk_kepengurusan']->store('pendaftaran/sk_kepengurusan', 'local');
                    $filesBaru[] = $pathBaru;

                    $fileLama = PendaftaranOrganisasiFile::where('id_pendaftaran_organisasi', $pendaftaran->id_pendaftaran_organisasi)
                        ->where('jenis_dokumen', 'sk_kepengurusan')
                        ->first();

                    if ($fileLama?->path_file) {
                        $filesLama[] = $fileLama->path_file;
                    }

                    PendaftaranOrganisasiFile::updateOrCreate(
                        [
                            'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                            'jenis_dokumen'             => 'sk_kepengurusan',
                        ],
                        [
                            'nama_file_asli' => $files['sk_kepengurusan']->getClientOriginalName(),
                            'path_file'      => $pathBaru,
                            'ukuran_kb'      => (int) ceil($files['sk_kepengurusan']->getSize() / 1024),
                            'diunggah_pada'  => now(),
                        ]
                    );
                }

                // Perbarui pendaftaran_organisasi
                $pendaftaran->update([
                    'status_pendaftaran'      => 'menunggu',
                    'alasan_penolakan'        => null,
                    'id_pengguna_verifikator' => null,
                    'tanggal_verifikasi'      => null,
                ]);

                // Perbarui pengguna.status_akun
                $pengguna->update([
                    'status_akun' => 'menunggu',
                ]);

                // Catat log aktivitas
                $this->logAktivitasService->catat(
                    $idPelaku,
                    'pendaftaran',
                    "Memperbarui dokumen SK pendaftaran {$organisasi->jenis_organisasi} {$organisasi->nama_organisasi}"
                );

                return $this->formatStatusOrganisasi($pengguna);
            });

            // Jika transaksi sukses commit, hapus file lama
            foreach ($filesLama as $lama) {
                Storage::disk('local')->delete($lama);
            }

            return $result;
        } catch (\Throwable $e) {
            // Hapus file baru yang sempat disimpan jika transaksi gagal
            foreach ($filesBaru as $baru) {
                Storage::disk('local')->delete($baru);
            }

            throw $e;
        }
    }
}
