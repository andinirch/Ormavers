<?php

namespace App\Http\Controllers;

use App\Services\DirektoriOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DirektoriOrganisasiController extends Controller
{
    public function __construct(
        protected DirektoriOrganisasiService $direktoriService,
    ) {}

    /**
     * Menampilkan daftar direktori universitas dengan fitur pencarian dan pagination.
     */
    #[OA\Get(
        path: '/api/direktori/universitas',
        summary: 'Direktori universitas',
        description: 'Menampilkan daftar direktori universitas dengan fitur pencarian dan pagination.',
        tags: ['Direktori & Pencarian Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Kata kunci pencarian nama universitas',
                schema: new OA\Schema(type: 'string', example: 'Indonesia')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Jumlah data per halaman (default: 8)',
                schema: new OA\Schema(type: 'integer', example: 8)
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                required: false,
                description: 'Pengurutan (misal: asc, desc)',
                schema: new OA\Schema(type: 'string', example: 'asc')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar universitas berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar universitas berhasil diambil'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function universitas(Request $request): JsonResponse
    {
        $search  = $request->input('search');
        $perPage = (int) $request->input('per_page', 8);
        $sort    = $request->input('sort');

        // Mengirim parameter pencarian, limit pagination, dan pengurutan ke service
        $paginator = $this->direktoriService->getDaftarUniversitas($search, $perPage, $sort);

        return response()->json([
            'success' => true,
            'message' => 'Daftar universitas berhasil diambil',
            'data'    => $paginator->items(),
            'links'   => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'from'         => $paginator->firstItem(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Menampilkan daftar organisasi mahasiswa terverifikasi pada universitas yang dipilih.
     */
    #[OA\Get(
        path: '/api/direktori/universitas/{id_universitas}/organisasi',
        summary: 'Direktori organisasi berdasarkan universitas',
        description: 'Menampilkan daftar organisasi mahasiswa terverifikasi pada universitas yang dipilih.',
        tags: ['Direktori & Pencarian Organisasi'],
        security: [['bearerAuth' => []]],
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
                description: 'Daftar organisasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar organisasi berhasil diambil'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Universitas tidak ditemukan')
        ]
    )]
    public function organisasi(int $id_universitas): JsonResponse
    {
        $result = $this->direktoriService->getOrganisasiByUniversitas($id_universitas);

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