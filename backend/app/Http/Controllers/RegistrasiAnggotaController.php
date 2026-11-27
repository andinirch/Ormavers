<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrasiAnggotaRequest;
use App\Http\Requests\AjukanUlangFotoKtmRequest;
use App\Services\RegistrasiAnggotaService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RegistrasiAnggotaController extends Controller
{
    public function __construct(
        protected RegistrasiAnggotaService $registrasiAnggotaService,
    ) {}

    #[OA\Post(
        path: '/api/register/anggota',
        summary: 'Pendaftaran Anggota Organisasi',
        description: 'Mendaftarkan akun mahasiswa baru sebagai anggota ke organisasi tertentu dengan melampirkan foto KTM.',
        tags: ['Autentikasi & Registrasi'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id_organisasi', 'nama', 'nim', 'username', 'password', 'foto_ktm'],
                    properties: [
                        new OA\Property(property: 'id_organisasi', type: 'integer', example: 1),
                        new OA\Property(property: 'nama', type: 'string', example: 'Dewi Sartika'),
                        new OA\Property(property: 'nim', type: 'string', example: '2106098765'),
                        new OA\Property(property: 'username', type: 'string', example: 'dewi_sartika'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123'),
                        new OA\Property(property: 'foto_ktm', type: 'string', format: 'binary', description: 'Foto KTM JPG/JPEG max 1MB')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pendaftaran anggota berhasil dikirim',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pendaftaran anggota berhasil dikirim'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validasi form atau foto KTM gagal'),
            new OA\Response(response: 429, description: 'Terlalu banyak percobaan registrasi')
        ]
    )]
    public function register(RegistrasiAnggotaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $fotoKtm = $request->file('foto_ktm');

        $pendaftaran = $this->registrasiAnggotaService->register($data, $fotoKtm);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran anggota berhasil dikirim',
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/api/registration/anggota/ajukan-ulang',
        summary: 'Ajukan ulang foto KTM anggota',
        description: 'Mengajukan ulang berkas foto KTM bagi anggota yang pendaftarannya ditolak.',
        tags: ['Autentikasi & Registrasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['foto_ktm'],
                    properties: [
                        new OA\Property(property: 'foto_ktm', type: 'string', format: 'binary', description: 'Foto KTM baru (JPG/JPEG max 1MB)')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Foto KTM berhasil diajukan ulang',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Foto KTM berhasil diajukan ulang'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Data keanggotaan tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi file gagal')
        ]
    )]
    public function ajukanUlang(AjukanUlangFotoKtmRequest $request): JsonResponse
    {
        $user = $request->user();

        // 1. Ambil data keanggotaan terbaru dari relasi user
        $keanggotaan = $user->keanggotaan()->latest('id_keanggotaan')->first();
        if (!$keanggotaan) {
            return response()->json([
                'success' => false,
                'message' => 'Data keanggotaan tidak ditemukan',
            ], 404);
        }

        $idKeanggotaan = $keanggotaan->id_keanggotaan;
        $data = $request->validated();
        $fotoKtm = $request->file('foto_ktm');

        // 2. Panggil service
        $hasil = $this->registrasiAnggotaService->ajukanUlang($idKeanggotaan, $data, $fotoKtm, $user);

        // 3. Tangani jika service mengembalikan error
        if (isset($hasil['error'])) {
            return response()->json([
                'success' => false,
                'message' => $hasil['error'],
            ], $hasil['status'] ?? 400);
        }

        return response()->json([
            'success' => true,
            'message' => $hasil['message'] ?? 'Foto KTM berhasil diajukan ulang',
            'data'    => $hasil,
        ], 200);
    }
}
