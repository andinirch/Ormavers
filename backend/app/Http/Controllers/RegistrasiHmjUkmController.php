<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrasiHmjUkmRequest;
use App\Services\RegistrasiHmjUkmService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RegistrasiHmjUkmController extends Controller
{
    public function __construct(
        protected RegistrasiHmjUkmService $registrasiHmjUkmService,
    ) {}

    #[OA\Post(
        path: '/api/register/hmj-ukm',
        summary: 'Pendaftaran Akun HMJ atau UKM',
        description: 'Mendaftarkan organisasi HMJ atau UKM baru di universitas yang telah memiliki BEM aktif, beserta akun penanggung jawab dan berkas pendukung.',
        tags: ['Autentikasi & Registrasi'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id_universitas', 'jenis_organisasi', 'nama_organisasi', 'nomor_telepon_organisasi', 'nama', 'nim', 'nomor_whatsapp', 'username', 'password', 'foto_ktm', 'sk_organisasi', 'sk_kepengurusan'],
                    properties: [
                        new OA\Property(property: 'id_universitas', type: 'integer', example: 1),
                        new OA\Property(property: 'id_bidang', type: 'integer', nullable: true, example: 1),
                        new OA\Property(property: 'jenis_organisasi', type: 'string', enum: ['hmj', 'ukm'], example: 'hmj'),
                        new OA\Property(property: 'nama_organisasi', type: 'string', example: 'Himpunan Mahasiswa Informatika'),
                        new OA\Property(property: 'nomor_telepon_organisasi', type: 'string', example: '081234567890'),
                        new OA\Property(property: 'nama', type: 'string', example: 'Ahmad Fauzi'),
                        new OA\Property(property: 'nim', type: 'string', example: '2106001122'),
                        new OA\Property(property: 'nomor_whatsapp', type: 'string', example: '081298765432'),
                        new OA\Property(property: 'username', type: 'string', example: 'hmj_informatika'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123'),
                        new OA\Property(property: 'foto_ktm', type: 'string', format: 'binary', description: 'Foto KTM JPG/JPEG max 1MB'),
                        new OA\Property(property: 'sk_organisasi', type: 'string', format: 'binary', description: 'File SK Organisasi PDF max 2MB'),
                        new OA\Property(property: 'sk_kepengurusan', type: 'string', format: 'binary', description: 'File SK Kepengurusan PDF max 2MB')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registrasi HMJ/UKM berhasil dikirim',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Registrasi HMJ berhasil dikirim'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validasi form atau file gagal'),
            new OA\Response(response: 429, description: 'Terlalu banyak percobaan registrasi')
        ]
    )]
    public function register(RegistrasiHmjUkmRequest $request): JsonResponse
    {
        $data = $request->validated();

        $files = [
            'foto_ktm'        => $request->file('foto_ktm'),
            'sk_organisasi'   => $request->file('sk_organisasi'),
            'sk_kepengurusan' => $request->file('sk_kepengurusan'),
        ];

        $pendaftaran = $this->registrasiHmjUkmService->register($data, $files);

        $jenisLabel = strtoupper($pendaftaran['jenis_organisasi']);

        return response()->json([
            'success' => true,
            'message' => "Registrasi {$jenisLabel} berhasil dikirim",
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ], 201);
    }
}
