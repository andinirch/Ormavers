<?php

namespace App\Http\Controllers;

use App\Services\PencarianProfilOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PencarianProfilOrganisasiController extends Controller
{
    public function __construct(
        protected PencarianProfilOrganisasiService $pencarianProfilService,
    ) {}

    #[OA\Get(
        path: '/api/organisasi/cari',
        summary: 'Cari dan filter organisasi',
        description: 'Mencari dan memfilter daftar organisasi aktif berdasarkan keyword, universitas, jenis, atau bidang.',
        tags: ['Direktori & Pencarian Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', required: false, description: 'Kata kunci pencarian', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id_universitas', in: 'query', required: false, description: 'Filter berdasarkan ID universitas', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'jenis_organisasi', in: 'query', required: false, description: 'Filter jenis organisasi (bem/hmj/ukm)', schema: new OA\Schema(type: 'string', enum: ['bem', 'hmj', 'ukm'])),
            new OA\Parameter(name: 'id_bidang', in: 'query', required: false, description: 'Filter berdasarkan bidang organisasi', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar organisasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function cari(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'id_universitas', 'jenis_organisasi', 'id_bidang']);

        $result = $this->pencarianProfilService->cariOrganisasi($filters);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'],
        ], $result['status']);
    }

    #[OA\Get(
        path: '/api/organisasi/{id_organisasi}',
        summary: 'Lihat profil organisasi',
        description: 'Melihat profil lengkap organisasi (deskripsi, portofolio, kontak) berdasarkan ID organisasi.',
        tags: ['Direktori & Pencarian Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id_organisasi',
                in: 'path',
                required: true,
                description: 'ID Organisasi',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil organisasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Organisasi tidak ditemukan')
        ]
    )]
    public function show(int|string $id_organisasi): JsonResponse
    {
        $result = $this->pencarianProfilService->getProfilOrganisasi((int) $id_organisasi);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ], $result['status']);
    }
}
