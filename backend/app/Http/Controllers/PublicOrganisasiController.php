<?php

namespace App\Http\Controllers;

use App\Services\PublicOrganisasiService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PublicOrganisasiController extends Controller
{
    public function __construct(
        protected PublicOrganisasiService $publicOrganisasiService,
    ) {}

    #[OA\Get(
        path: '/api/public/bem',
        summary: 'Daftar BEM publik',
        description: 'Mendapatkan daftar seluruh organisasi BEM yang berstatus aktif.',
        tags: ['Master Data & Publik'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar BEM aktif berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar BEM aktif berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'bem',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id_organisasi', type: 'integer', example: 1),
                                            new OA\Property(property: 'nama_organisasi', type: 'string', example: 'BEM Universitas Indonesia'),
                                            new OA\Property(property: 'id_universitas', type: 'integer', example: 1),
                                            new OA\Property(property: 'nama_universitas', type: 'string', example: 'Universitas Indonesia')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function bem(): JsonResponse
    {
        $bem = $this->publicOrganisasiService->daftarBemAktif();

        return response()->json([
            'success' => true,
            'message' => 'Daftar BEM aktif berhasil diambil',
            'data'    => [
                'bem' => $bem,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/public/bem/{id}/organisasi',
        summary: 'Daftar organisasi di bawah naungan BEM',
        description: 'Mendapatkan daftar HMJ dan UKM aktif yang berada di bawah universitas yang sama dengan BEM yang dipilih.',
        tags: ['Master Data & Publik'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID Organisasi BEM',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar organisasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar organisasi berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'bem', type: 'object'),
                                new OA\Property(property: 'organisasi', type: 'array', items: new OA\Items(type: 'object'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Organisasi BEM tidak ditemukan'
            )
        ]
    )]
    public function organisasiDiBawahBem(int $id): JsonResponse
    {
        $result = $this->publicOrganisasiService->daftarOrganisasiDiBawahBem($id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar organisasi berhasil diambil',
            'data'    => $result,
        ]);
    }

    #[OA\Get(
        path: '/api/public/universitas/{id_universitas}/organisasi-lengkap',
        summary: 'Daftar organisasi lengkap berdasarkan universitas',
        description: 'Mendapatkan daftar seluruh organisasi aktif pada universitas tertentu.',
        tags: ['Master Data & Publik'],
        parameters: [
            new OA\Parameter(
                name: 'id_universitas',
                in: 'path',
                required: true,
                description: 'ID Universitas',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar organisasi universitas berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar organisasi universitas berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'organisasi',
                                    type: 'array',
                                    items: new OA\Items(type: 'object')
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function organisasiBerdasarkanUniversitas(int $id_universitas): JsonResponse
    {
        // Ambil semua organisasi (BEM, HMJ, UKM) yang terdaftar di universitas tersebut
        $organisasi = \App\Models\Organisasi::where('id_universitas', $id_universitas)
            ->where('status_organisasi', 'aktif')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar organisasi universitas berhasil diambil',
            'data'    => [
            'organisasi' => $organisasi,
            ],
        ]);
    }
}
