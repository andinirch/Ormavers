<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKontakRequest;
use App\Http\Requests\UpdateKontakRequest;
use App\Services\KontakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class KontakController extends Controller
{
    public function __construct(
        protected KontakService $kontakService,
    ) {}

    #[OA\Get(
        path: '/api/organisasi/kontak',
        operationId: 'indexKontak',
        summary: 'Daftar kontak organisasi',
        description: 'Mengambil daftar kontak milik organisasi pengguna.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar kontak berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->kontakService->getDaftarKontak($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar kontak berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    #[OA\Post(
        path: '/api/organisasi/kontak',
        operationId: 'storeKontak',
        summary: 'Tambah kontak',
        description: 'Menambahkan kontak baru ke organisasi.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jenis', 'nilai'],
                properties: [
                    new OA\Property(property: 'jenis', type: 'string'),
                    new OA\Property(property: 'nilai', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kontak berhasil ditambahkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreKontakRequest $request): JsonResponse
    {
        $result = $this->kontakService->tambahKontak(
            $request->user(),
            $request->validated(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil ditambahkan',
            'data'    => $result['data'],
        ], 201);
    }

    #[OA\Put(
        path: '/api/organisasi/kontak/{id}',
        operationId: 'updateKontak',
        summary: 'Perbarui kontak',
        description: 'Memperbarui kontak berdasarkan ID.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jenis', 'nilai'],
                properties: [
                    new OA\Property(property: 'jenis', type: 'string'),
                    new OA\Property(property: 'nilai', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Kontak berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Kontak tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateKontakRequest $request, int|string $id): JsonResponse
    {
        $result = $this->kontakService->updateKontak(
            $request->user(),
            (int) $id,
            $request->validated(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }

    #[OA\Delete(
        path: '/api/organisasi/kontak/{id}',
        operationId: 'destroyKontak',
        summary: 'Hapus kontak',
        description: 'Menghapus kontak berdasarkan ID.',
        tags: ['Identitas, Profil, Portofolio & Kontak Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kontak berhasil dihapus', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Kontak tidak ditemukan'),
        ]
    )]
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $result = $this->kontakService->hapusKontak($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil dihapus',
            'data'    => $result['data'],
        ]);
    }
}
