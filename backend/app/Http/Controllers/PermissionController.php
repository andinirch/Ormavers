<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanPermissionRequest;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    #[OA\Post(
        path: '/api/admin-panel/permissions',
        operationId: 'storePermission',
        summary: 'Tambah permission',
        description: 'Menambahkan permission baru ke sistem.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Permission berhasil dibuat', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(SimpanPermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->buat($request->validated(), $request->user()->id_pengguna);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil dibuat',
            'data' => ['permission' => $permission],
        ], 201);
    }

    #[OA\Put(
        path: '/api/admin-panel/permissions/{id}',
        operationId: 'updatePermission',
        summary: 'Perbarui permission',
        description: 'Memperbarui permission berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permission berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Permission tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(SimpanPermissionRequest $request, int $id): JsonResponse
    {
        $result = $this->permissionService->ubah($id, $request->validated(), $request->user()->id_pengguna);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil diperbarui',
            'data' => ['permission' => $result],
        ]);
    }

    #[OA\Delete(
        path: '/api/admin-panel/permissions/{id}',
        operationId: 'destroyPermission',
        summary: 'Hapus permission',
        description: 'Menghapus permission berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Permission berhasil dihapus', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Permission tidak ditemukan'),
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->permissionService->hapus($id, $request->user()->id_pengguna);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], $result['status']);
        }

        return response()->json(['success' => true, 'message' => $result['message']]);
    }
}
