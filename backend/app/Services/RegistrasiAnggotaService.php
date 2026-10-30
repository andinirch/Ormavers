<?php

namespace App\Services;

use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RegistrasiAnggotaService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Mendaftarkan anggota baru ke organisasi.
     *
     * @param  array         $data
     * @param  UploadedFile  $fotoKtm
     * @return array
     *
     * @throws ValidationException
     */
    public function register(array $data, UploadedFile $fotoKtm): array
    {
        $uploadedPath = null;

        try {
            return DB::transaction(function () use ($data, $fotoKtm, &$uploadedPath) {
                // 1. Cek organisasi tujuan
                $organisasi = Organisasi::where('id_organisasi', $data['id_organisasi'])
                    ->where('status_organisasi', 'aktif')
                    ->whereIn('jenis_organisasi', ['bem', 'hmj', 'ukm'])
                    ->first();

                if (! $organisasi) {
                    throw ValidationException::withMessages([
                        'id_organisasi' => ['Organisasi tidak ditemukan atau belum aktif'],
                    ]);
                }

                // 2. Simpan foto KTM di disk privat
                $uploadedPath = $fotoKtm->store('pendaftaran/ktm', 'local');

                // 3. Buat akun pengguna (kredensial login)
                $pengguna = Pengguna::create([
                    'nama'        => $data['nama'],
                    'username'    => $data['username'],
                    'kata_sandi'  => $data['password'],
                    'peran'       => 'anggota',
                    'status_akun' => 'menunggu',
                ]);

                // 3b. Buat profil anggota
                ProfilAnggota::create([
                    'id_pengguna'    => $pengguna->id_pengguna,
                    'nim'            => $data['nim'] ?? null,
                    'nomor_whatsapp' => null,
                    'foto_ktm'       => $uploadedPath,
                    'id_universitas' => $organisasi->id_universitas,
                ]);

                // 4. Buat record keanggotaan
                $keanggotaan = Keanggotaan::create([
                    'id_pengguna'         => $pengguna->id_pengguna,
                    'id_organisasi'       => $organisasi->id_organisasi,
                    'status_keanggotaan'  => 'menunggu',
                    'tanggal_pendaftaran' => now(),
                ]);

                // 5. Catat log aktivitas (pelaku = anggota yang mendaftar)
                $this->logAktivitasService->catat(
                    $pengguna->id_pengguna,
                    'pendaftaran',
                    "Pendaftaran anggota ke {$organisasi->nama_organisasi}"
                );

                return [
                    'id_keanggotaan'      => $keanggotaan->id_keanggotaan,
                    'id_organisasi'       => $organisasi->id_organisasi,
                    'nama_organisasi'     => $organisasi->nama_organisasi,
                    'status_keanggotaan'  => $keanggotaan->status_keanggotaan,
                    'tanggal_pendaftaran' => $keanggotaan->tanggal_pendaftaran->toISOString(),
                ];
            });
        } catch (\Throwable $e) {
            if ($uploadedPath) {
                Storage::disk('local')->delete($uploadedPath);
            }

            throw $e;
        }
    }

    public function ajukanUlang(int $idKeanggotaan, array $data, ?UploadedFile $fotoKtm, Pengguna $user): array
    {
        $keanggotaan = Keanggotaan::with('pengguna.profilAnggota')
            ->where('id_keanggotaan', $idKeanggotaan)
            ->first();

        if (! $keanggotaan || $keanggotaan->id_pengguna !== $user->id_pengguna) {
            return [
                'error'  => 'Data keanggotaan tidak ditemukan',
                'status' => 404,
            ];
        }

        if ($keanggotaan->status_keanggotaan !== 'ditolak') {
            return [
                'error'  => 'Pendaftaran hanya dapat diajukan ulang jika berstatus ditolak',
                'status' => 400,
            ];
        }

        // Validasi opsional: pastikan file adalah gambar (JPG/JPEG/PNG) maksimal 1 MB
        if ($fotoKtm) {
            $mimeType = $fotoKtm->getMimeType();
            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg'])) {
                return [
                    'error'  => 'Format file foto KTM harus berupa JPG/JPEG/PNG',
                    'status' => 422,
                ];
            }
        }

        return DB::transaction(function () use ($keanggotaan, $fotoKtm) {
            $profil = $keanggotaan->pengguna?->profilAnggota;

            // Jika ada foto KTM baru yang diunggah
            if ($fotoKtm) {
                // Hapus foto KTM lama dari disk privat jika ada
                if ($profil?->foto_ktm && Storage::disk('local')->exists($profil->foto_ktm)) {
                    Storage::disk('local')->delete($profil->foto_ktm);
                }
                
                // Simpan foto KTM baru ke folder pendaftaran/ktm
                $uploadedPath = $fotoKtm->store('pendaftaran/ktm', 'local');
                if ($profil) {
                    $profil->update(['foto_ktm' => $uploadedPath]);
                }
            }

            // Reset status pendaftaran kembali ke 'menunggu'
            $keanggotaan->update([
                'status_keanggotaan'      => 'menunggu',
                'alasan_penolakan'        => null,
                'id_pengguna_verifikator' => null,
                'tanggal_verifikasi'      => null,
                'tanggal_pendaftaran'     => now(),
            ]);

            $this->logAktivitasService->catat(
                $keanggotaan->id_pengguna,
                'pendaftaran',
                "Mengajukan ulang foto KTM pendaftaran anggota"
            );

            return [
                'message' => 'Foto KTM pendaftaran berhasil diunggah ulang',
            ];
        });
    }
}
