<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RegistrasiHmjUkmService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

    /**
     * Mendaftarkan HMJ atau UKM baru.
     *
     * @param  array  $data   Data dari request yang sudah tervalidasi
     * @param  array  $files  File ['foto_ktm', 'sk_organisasi', 'sk_kepengurusan']
     * @return array  Data pendaftaran
     *
     * @throws ValidationException
     */
    public function register(array $data, array $files): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($data, $files, &$uploadedPaths) {
                // 1. Ambil BEM induk
                $bemInduk = Organisasi::where('id_organisasi', $data['id_organisasi_induk'])
                    ->where('jenis_organisasi', 'bem')
                    ->where('status_organisasi', 'aktif')
                    ->first();

                if (! $bemInduk) {
                    throw ValidationException::withMessages([
                        'id_organisasi_induk' => ['Organisasi induk harus berupa BEM yang aktif'],
                    ]);
                }

                // 2. Cek keunikan nama organisasi di dalam universitas yang sama
                $namaKembar = Organisasi::where('id_universitas', $bemInduk->id_universitas)
                    ->whereRaw('LOWER(TRIM(nama_organisasi)) = ?', [strtolower(trim($data['nama_organisasi']))])
                    ->exists();

                if ($namaKembar) {
                    throw ValidationException::withMessages([
                        'nama_organisasi' => ['Nama organisasi sudah terdaftar di universitas ini'],
                    ]);
                }

                // 3. Simpan file di disk privat (local) dengan nama acak
                $uploadedPaths[] = $files['foto_ktm']->store('pendaftaran/ktm', 'local');
                $uploadedPaths[] = $files['sk_organisasi']->store('pendaftaran/sk_organisasi', 'local');
                $uploadedPaths[] = $files['sk_kepengurusan']->store('pendaftaran/sk_kepengurusan', 'local');

                // 4. Buat akun pengguna (kredensial login)
                $pengguna = Pengguna::create([
                    'nama'        => $data['nama'],
                    'username'    => $data['username'],
                    'kata_sandi'  => $data['password'],
                    'peran'       => 'akun_organisasi',
                    'status_akun' => 'menunggu',
                ]);

                // 4b. Buat profil anggota penanggung jawab
                ProfilAnggota::create([
                    'id_pengguna'    => $pengguna->id_pengguna,
                    'nim'            => $data['nim'] ?? null,
                    'nomor_whatsapp' => $data['nomor_whatsapp'] ?? null,
                    'foto_ktm'       => $uploadedPaths[0],
                    'id_universitas' => $bemInduk->id_universitas,
                ]);

                // 5. Buat organisasi (jenis hmj atau ukm, id_organisasi_induk = BEM, id_universitas dari induk)
                $organisasi = Organisasi::create([
                    'id_universitas'      => $bemInduk->id_universitas,
                    'id_organisasi_induk' => $bemInduk->id_organisasi,
                    'id_akun_organisasi'  => $pengguna->id_pengguna,
                    'id_bidang'           => $data['id_bidang'] ?? null,
                    'nama_organisasi'     => $data['nama_organisasi'],
                    'jenis_organisasi'    => $data['jenis_organisasi'],
                    'nomor_telepon'       => $data['nomor_telepon_organisasi'],
                    'status_organisasi'   => 'menunggu',
                ]);

                // 6. Tetapkan role Spatie akun_hmj_ukm (bukan akun_bem)
                $roleHmjUkm = Role::findOrCreate('akun_hmj_ukm', 'web');
                $pengguna->syncRoles($roleHmjUkm);

                // 7. Buat pendaftaran organisasi
                $pendaftaran = PendaftaranOrganisasi::create([
                    'id_organisasi'       => $organisasi->id_organisasi,
                    'status_pendaftaran'  => 'menunggu',
                    'tanggal_pendaftaran' => now(),
                ]);

                // 8. Buat 2 baris pendaftaran_organisasi_file
                $ukuranSkOrg = (int) ceil($files['sk_organisasi']->getSize() / 1024);
                $ukuranSkKep = (int) ceil($files['sk_kepengurusan']->getSize() / 1024);

                PendaftaranOrganisasiFile::create([
                    'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                    'jenis_dokumen'             => 'sk_organisasi',
                    'nama_file_asli'            => $files['sk_organisasi']->getClientOriginalName(),
                    'path_file'                 => $uploadedPaths[1],
                    'ukuran_kb'                 => $ukuranSkOrg,
                    'diunggah_pada'             => now(),
                ]);

                PendaftaranOrganisasiFile::create([
                    'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                    'jenis_dokumen'             => 'sk_kepengurusan',
                    'nama_file_asli'            => $files['sk_kepengurusan']->getClientOriginalName(),
                    'path_file'                 => $uploadedPaths[2],
                    'ukuran_kb'                 => $ukuranSkKep,
                    'diunggah_pada'             => now(),
                ]);

                // 9. Catat log aktivitas (pelaku = pendaftar)
                $jenisLabel = strtoupper($organisasi->jenis_organisasi);
                $this->logAktivitasService->catat(
                    $pengguna->id_pengguna,
                    'pendaftaran',
                    "Pendaftaran {$jenisLabel} {$organisasi->nama_organisasi} ke {$bemInduk->nama_organisasi}"
                );

                return [
                    'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                    'jenis_organisasi'          => $organisasi->jenis_organisasi,
                    'nama_organisasi'           => $organisasi->nama_organisasi,
                    'id_organisasi_induk'       => $bemInduk->id_organisasi,
                    'nama_bem_induk'            => $bemInduk->nama_organisasi,
                    'status_pendaftaran'        => $pendaftaran->status_pendaftaran,
                    'tanggal_pendaftaran'       => $pendaftaran->tanggal_pendaftaran->toISOString(),
                ];
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }
    }
}
