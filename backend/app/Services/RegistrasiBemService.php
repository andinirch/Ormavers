<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\PendaftaranOrganisasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RegistrasiBemService
{
    /**
     * Mendaftarkan BEM baru.
     *
     * @param  array  $data   Data yang sudah divalidasi dari request
     * @param  array  $files  File yang diunggah ['foto_ktm', 'sk_organisasi', 'sk_kepengurusan']
     * @return array  Data pendaftaran untuk response
     *
     * @throws ValidationException
     */
    public function registerBem(array $data, array $files): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($data, $files, &$uploadedPaths) {
                // 1. Cek duplikasi nama_instansi (di dalam transaksi)
                $exists = PendaftaranOrganisasi::where('jenis_organisasi', 'bem')
                    ->whereIn('status_pendaftaran', ['menunggu', 'disetujui'])
                    ->whereRaw('LOWER(TRIM(nama_instansi)) = ?', [strtolower(trim($data['nama_instansi']))])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'nama_instansi' => ['BEM untuk instansi ini sudah terdaftar'],
                    ]);
                }

                // 2. Buat akun pengguna (peran & status ditetapkan oleh server)
                $pengguna = Pengguna::create([
                    'nama'        => $data['nama'],
                    'username'    => $data['username'],
                    'kata_sandi'  => $data['password'],
                    'peran'       => 'akun_organisasi',
                    'status_akun' => 'menunggu',
                ]);

                // 3. Simpan file di disk privat (local)
                $uploadedPaths[] = $files['foto_ktm']->store('pendaftaran/ktm', 'local');
                $uploadedPaths[] = $files['sk_organisasi']->store('pendaftaran/sk_organisasi', 'local');
                $uploadedPaths[] = $files['sk_kepengurusan']->store('pendaftaran/sk_kepengurusan', 'local');

                // 4. Buat pendaftaran organisasi
                $pendaftaran = PendaftaranOrganisasi::create([
                    'id_organisasi'            => null,
                    'id_pengguna_pendaftar'    => $pengguna->id_pengguna,
                    'jenis_organisasi'         => 'bem',
                    'nama_organisasi'          => $data['nama_organisasi'],
                    'nama_instansi'            => trim($data['nama_instansi']),
                    'nomor_telepon_organisasi' => $data['nomor_telepon_organisasi'],
                    'nama_penanggung_jawab'    => $data['nama'],
                    'nim_penanggung_jawab'     => $data['nim'],
                    'nomor_whatsapp'           => $data['nomor_whatsapp'],
                    'foto_ktm'                 => $uploadedPaths[0],
                    'dokumen_sk_organisasi'    => $uploadedPaths[1],
                    'dokumen_sk_kepengurusan'  => $uploadedPaths[2],
                    'status_pendaftaran'       => 'menunggu',
                    'tanggal_pendaftaran'      => now(),
                ]);

                return [
                    'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                    'jenis_organisasi'          => $pendaftaran->jenis_organisasi,
                    'nama_organisasi'           => $pendaftaran->nama_organisasi,
                    'nama_instansi'             => $pendaftaran->nama_instansi,
                    'status_pendaftaran'        => $pendaftaran->status_pendaftaran,
                    'tanggal_pendaftaran'       => $pendaftaran->tanggal_pendaftaran->toISOString(),
                ];
            });
        } catch (\Throwable $e) {
            // Hapus file yang sempat terunggah jika transaksi gagal
            foreach ($uploadedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }
    }
}
