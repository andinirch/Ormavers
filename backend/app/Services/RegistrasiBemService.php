<?php

namespace App\Services;

use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use App\Models\Universitas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RegistrasiBemService
{
    public function __construct(
        protected LogAktivitasService $logAktivitasService,
    ) {}

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
                // 1. Cari atau buat otomatis data universitas dari input teks manual nama_instansi
                $namaInstansi = trim($data['nama_instansi']);
                
                $universitas = Universitas::firstOrCreate(
                    ['nama_universitas' => $namaInstansi],
                    [
                        'logo' => null, // Nilai default jika kolom logo bersifat nullable
                    ]
                );

                // 2. Cek BEM aktif/menunggu di universitas yang sama
                $adaBem = PendaftaranOrganisasi::whereIn('status_pendaftaran', ['menunggu', 'disetujui'])
                    ->whereHas('organisasi', function ($q) use ($universitas) {
                        $q->where('id_universitas', $universitas->id_universitas)
                            ->where('jenis_organisasi', 'bem');
                    })
                    ->exists();

                if ($adaBem) {
                    throw ValidationException::withMessages([
                        'nama_instansi' => ['BEM untuk universitas ini sudah terdaftar'],
                    ]);
                }

                // 3. Cek keunikan nama organisasi di universitas yang sama
                $namaKembar = Organisasi::where('id_universitas', $universitas->id_universitas)
                    ->whereRaw('LOWER(TRIM(nama_organisasi)) = ?', [strtolower(trim($data['nama_organisasi']))])
                    ->exists();

                if ($namaKembar) {
                    throw ValidationException::withMessages([
                        'nama_organisasi' => ['Nama organisasi sudah terdaftar di universitas ini'],
                    ]);
                }

                // 4. Simpan file di disk privat (local) dengan nama acak
                $uploadedPaths[] = $files['foto_ktm']->store('pendaftaran/ktm', 'local');
                $uploadedPaths[] = $files['sk_organisasi']->store('pendaftaran/sk_organisasi', 'local');
                $uploadedPaths[] = $files['sk_kepengurusan']->store('pendaftaran/sk_kepengurusan', 'local');

                // 5. Buat akun pengguna (peran & status akun ditetapkan oleh sistem)
                $pengguna = Pengguna::create([
                    'nama'           => $data['nama'],
                    'username'       => $data['username'],
                    'kata_sandi'     => $data['password'],
                    'peran'          => 'akun_organisasi',
                    'status_akun'    => 'menunggu',
                    'nim'            => $data['nim'],
                    'nomor_whatsapp' => $data['nomor_whatsapp'],
                    'foto_ktm'       => $uploadedPaths[0],
                ]);

                // 6. Buat organisasi (status awal menunggu)
                $organisasi = Organisasi::create([
                    'id_universitas'     => $universitas->id_universitas,
                    'id_organisasi_induk'=> null,
                    'id_akun_organisasi' => $pengguna->id_pengguna,
                    'nama_organisasi'    => $data['nama_organisasi'],
                    'jenis_organisasi'   => 'bem',
                    'nomor_telepon'      => $data['nomor_telepon_organisasi'],
                    'status_organisasi'  => 'menunggu',
                ]);

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
                $this->logAktivitasService->catat(
                    $pengguna->id_pengguna,
                    'pendaftaran',
                    "Pendaftaran BEM {$organisasi->nama_organisasi} dari {$universitas->nama_universitas}"
                );

                return [
                    'id_pendaftaran_organisasi' => $pendaftaran->id_pendaftaran_organisasi,
                    'jenis_organisasi'          => $organisasi->jenis_organisasi,
                    'nama_organisasi'           => $organisasi->nama_organisasi,
                    'id_universitas'            => $universitas->id_universitas,
                    'nama_universitas'          => $universitas->nama_universitas,
                    'nama_instansi'             => $universitas->nama_universitas,
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