<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfilRequest;
use App\Http\Requests\UploadFotoProfilRequest;
use App\Services\ProfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfilController extends Controller
{
    public function __construct(
        protected ProfilService $profilService,
    ) {}

    #[OA\Get(
        path: '/api/profil',
        summary: 'Lihat profil pengguna',
        description: 'Mendapatkan data profil pengguna yang sedang login (semua peran).',
        tags: ['Profil Pengguna'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data profil berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data profil berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id_pengguna', type: 'integer', example: 1),
                                new OA\Property(property: 'username', type: 'string', example: 'user123'),
                                new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                new OA\Property(property: 'nim', type: 'string', nullable: true, example: '2106123456'),
                                new OA\Property(property: 'nomor_whatsapp', type: 'string', nullable: true, example: '081234567890'),
                                new OA\Property(property: 'foto_profil', type: 'string', nullable: true, example: 'storage/profil/foto.jpg'),
                                new OA\Property(property: 'organisasi_diikuti', type: 'array', items: new OA\Items(type: 'object'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $profil = $this->profilService->getProfil($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data'    => $profil,
        ]);
    }

    #[OA\Put(
        path: '/api/profil',
        summary: 'Perbarui data profil',
        description: 'Memperbarui nama, username, NIM, dan nomor WhatsApp pengguna saat ini.',
        tags: ['Profil Pengguna'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'username'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso Updated'),
                    new OA\Property(property: 'username', type: 'string', example: 'budisantoso'),
                    new OA\Property(property: 'nim', type: 'string', nullable: true, example: '2106123456'),
                    new OA\Property(property: 'nomor_whatsapp', type: 'string', nullable: true, example: '081234567890')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data Berhasil Diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data Berhasil Diperbarui'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    public function update(UpdateProfilRequest $request): JsonResponse
    {
        $profil = $this->profilService->updateProfil(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Data Berhasil Diperbarui',
            'data'    => $profil,
        ]);
    }

    #[OA\Put(
        path: '/api/profil/kata-sandi',
        summary: 'Ubah kata sandi',
        description: 'Mengubah kata sandi pengguna saat ini dan mencabut token login di perangkat lain.',
        tags: ['Profil Pengguna'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password_lama', 'password_baru', 'konfirmasi_password'],
                properties: [
                    new OA\Property(property: 'password_lama', type: 'string', format: 'password', example: 'PasswordLama123'),
                    new OA\Property(property: 'password_baru', type: 'string', format: 'password', example: 'PasswordBaru123'),
                    new OA\Property(property: 'konfirmasi_password', type: 'string', format: 'password', example: 'PasswordBaru123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password Berhasil Diubah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password Berhasil Diubah')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Password lama salah atau konfirmasi tidak cocok')
        ]
    )]
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profilService->updatePassword(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Password Berhasil Diubah',
        ]);
    }

    #[OA\Post(
        path: '/api/profil/foto',
        summary: 'Unggah foto profil',
        description: 'Mengunggah dan mengganti foto profil akun (JPG, JPEG, PNG max 2MB).',
        tags: ['Profil Pengguna'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['foto'],
                    properties: [
                        new OA\Property(property: 'foto', type: 'string', format: 'binary', description: 'File gambar foto profil (JPG/JPEG/PNG max 2MB)')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Foto Profil Berhasil Diubah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Foto Profil Berhasil Diubah'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'foto_profil', type: 'string', example: 'profil/abc123xyz.jpg')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'File tidak valid atau melebihi ukuran maksimum')
        ]
    )]
    public function uploadFoto(UploadFotoProfilRequest $request): JsonResponse
    {
        $result = $this->profilService->uploadFoto(
            $request->user(),
            $request->file('foto'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto Profil Berhasil Diubah',
            'data'    => $result,
        ]);
    }
}
