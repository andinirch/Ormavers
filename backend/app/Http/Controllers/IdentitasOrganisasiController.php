<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentitasOrganisasiRequest;
use App\Services\IdentitasOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class IdentitasOrganisasiController extends Controller
{
    public function __construct(
        protected IdentitasOrganisasiService $identitasOrganisasiService,
    ) {}

    /**
     * Tampilkan data identitas organisasi milik pengguna saat ini.
     */
    #[OA\Get(
        path: '/api/organisasi/identitas',
        operationId: 'showIdentitasOrganisasi',
        summary: 'Tampilkan identitas organisasi',
        description: 'Mengambil data identitas organisasi milik pengguna saat ini.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Identitas organisasi berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $result = $this->identitasOrganisasiService->getIdentitas($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas organisasi berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * Perbarui data identitas organisasi (beserta logo dan masa berlaku SK).
     */
    #[OA\Post(
        path: '/api/organisasi/identitas',
        operationId: 'updateIdentitasPost',
        summary: 'Perbarui identitas organisasi',
        description: 'Memperbarui data identitas organisasi beserta logo dan masa berlaku SK.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'nama_organisasi', type: 'string'),
                    new OA\Property(property: 'nomor_telepon_organisasi', type: 'string'),
                    new OA\Property(property: 'id_bidang', type: 'integer'),
                    new OA\Property(property: 'sk_mulai_berlaku', type: 'string', format: 'date'),
                    new OA\Property(property: 'sk_akhir_berlaku', type: 'string', format: 'date'),
                    new OA\Property(property: 'logo', type: 'string', format: 'binary'),
                    new OA\Property(property: 'logo_universitas', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Identitas organisasi berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(IdentitasOrganisasiRequest $request): JsonResponse
    {
        $result = $this->identitasOrganisasiService->updateIdentitas(
            pengguna: $request->user(),
            data: $request->validated(),
            logoFile: $request->file('logo'),
            logoUniversitasFile: $request->file('logo_universitas'),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas organisasi berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }
}
