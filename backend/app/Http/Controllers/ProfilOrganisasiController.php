<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDeskripsiOrganisasiRequest;
use App\Services\ProfilOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfilOrganisasiController extends Controller
{
    public function __construct(
        protected ProfilOrganisasiService $profilOrganisasiService,
    ) {}

    #[OA\Get(
        path: '/api/organisasi/deskripsi',
        operationId: 'showDeskripsiOrganisasi',
        summary: 'Tampilkan deskripsi organisasi',
        description: 'Mengambil data deskripsi organisasi milik pengguna saat ini.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Deskripsi organisasi berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function showDeskripsi(Request $request): JsonResponse
    {
        $result = $this->profilOrganisasiService->getDeskripsi($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deskripsi organisasi berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    #[OA\Put(
        path: '/api/organisasi/deskripsi',
        operationId: 'updateDeskripsiPut',
        summary: 'Perbarui deskripsi organisasi',
        description: 'Memperbarui data deskripsi organisasi.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['deskripsi'],
                properties: [
                    new OA\Property(property: 'deskripsi', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Deskripsi organisasi berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateDeskripsi(UpdateDeskripsiOrganisasiRequest $request): JsonResponse
    {
        $result = $this->profilOrganisasiService->updateDeskripsi(
            $request->user(),
            $request->validated('deskripsi'),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deskripsi organisasi berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }
}
