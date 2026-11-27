<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortofolioRequest;
use App\Http\Requests\UpdatePortofolioRequest;
use App\Services\PortofolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PortofolioController extends Controller
{
    public function __construct(
        protected PortofolioService $portofolioService,
    ) {}

    #[OA\Get(
        path: '/api/organisasi/portofolio',
        operationId: 'indexPortofolio',
        summary: 'Daftar portofolio organisasi',
        description: 'Mengambil daftar portofolio milik organisasi pengguna.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar portofolio berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->portofolioService->getDaftarPortofolio($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar portofolio berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    #[OA\Get(
        path: '/api/organisasi/portofolio/{id}',
        operationId: 'showPortofolio',
        summary: 'Detail portofolio',
        description: 'Mengambil detail portofolio berdasarkan ID.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail portofolio berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Portofolio tidak ditemukan'),
        ]
    )]
    public function show(Request $request, int|string $id): JsonResponse
    {
        $result = $this->portofolioService->getPortofolioById($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail portofolio berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    #[OA\Post(
        path: '/api/organisasi/portofolio',
        operationId: 'storePortofolio',
        summary: 'Tambah portofolio',
        description: 'Menambahkan portofolio baru ke organisasi.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['judul', 'tahun', 'deskripsi'],
                    properties: [
                        new OA\Property(property: 'judul', type: 'string'),
                        new OA\Property(property: 'tahun', type: 'integer'),
                        new OA\Property(property: 'deskripsi', type: 'string'),
                        new OA\Property(property: 'dokumentasi', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Portofolio berhasil ditambahkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePortofolioRequest $request): JsonResponse
    {
        $result = $this->portofolioService->tambahPortofolio(
            $request->user(),
            $request->validated(),
            $request->file('dokumentasi'),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Portofolio berhasil ditambahkan',
            'data'    => $result['data'],
        ], 201);
    }

    #[OA\Put(
        path: '/api/organisasi/portofolio/{id}',
        operationId: 'updatePortofolio',
        summary: 'Perbarui portofolio',
        description: 'Memperbarui portofolio berdasarkan ID.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'judul', type: 'string'),
                    new OA\Property(property: 'tahun', type: 'integer'),
                    new OA\Property(property: 'deskripsi', type: 'string'),
                    new OA\Property(property: 'dokumentasi', type: 'string', format: 'binary'),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Portofolio berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Portofolio tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdatePortofolioRequest $request, int|string $id): JsonResponse
    {
        $result = $this->portofolioService->updatePortofolio(
            $request->user(),
            (int) $id,
            $request->validated(),
            $request->file('dokumentasi'),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Portofolio berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }

    #[OA\Delete(
        path: '/api/organisasi/portofolio/{id}',
        operationId: 'destroyPortofolio',
        summary: 'Hapus portofolio',
        description: 'Menghapus portofolio berdasarkan ID.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Portofolio berhasil dihapus', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Portofolio tidak ditemukan'),
        ]
    )]
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $result = $this->portofolioService->hapusPortofolio($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Portofolio berhasil dihapus',
            'data'    => $result['data'],
        ]);
    }
}
