<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanRoleRequest;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
    ) {}

    #[OA\Get(
        path: '/api/admin-panel/permissions',
        operationId: 'listPermissions',
        summary: 'Daftar permission',
        description: 'Mengambil seluruh daftar permission yang tersedia.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar permission berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function permissions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar permission berhasil diambil',
            'data'    => [
                'permissions' => $this->roleService->daftarPermission(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/admin-panel/roles',
        operationId: 'indexRoles',
        summary: 'Daftar role',
        description: 'Mengambil daftar role yang tersedia beserta permissions.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar role berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar role berhasil diambil',
            'data'    => [
                'roles' => $this->roleService->daftarRole(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/admin-panel/roles/{id}',
        operationId: 'showRole',
        summary: 'Detail role',
        description: 'Mengambil detail role berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail role berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $result = $this->roleService->detailRole($id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail role berhasil diambil',
            'data'    => [
                'role' => $result,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/admin-panel/roles',
        operationId: 'storeRole',
        summary: 'Tambah role',
        description: 'Menambahkan role baru beserta permissions terkait.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'permissions'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Role berhasil dibuat', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(SimpanRoleRequest $request): JsonResponse
    {
        $result = $this->roleService->buatRole(
            $request->validated(),
            $request->user()->id_pengguna,
        );

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dibuat',
            'data'    => [
                'role' => $result,
            ],
        ], 201);
    }

    #[OA\Put(
        path: '/api/admin-panel/roles/{id}',
        operationId: 'updateRole',
        summary: 'Perbarui role',
        description: 'Memperbarui role dan permissions berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'permissions'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Role berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(SimpanRoleRequest $request, int $id): JsonResponse
    {
        $result = $this->roleService->ubahRole(
            $id,
            $request->validated(),
            $request->user()->id_pengguna,
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diperbarui',
            'data'    => [
                'role' => $result,
            ],
        ]);
    }

    #[OA\Delete(
        path: '/api/admin-panel/roles/{id}',
        operationId: 'destroyRole',
        summary: 'Hapus role',
        description: 'Menghapus role berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role berhasil dihapus', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->roleService->hapusRole(
            $id,
            $request->user()->id_pengguna,
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    #[OA\Get(
        path: '/api/admin-panel/ringkasan',
        operationId: 'ringkasanAdminPanel',
        summary: 'Ringkasan dashboard admin panel',
        description: 'Mengambil data ringkasan dashboard untuk admin panel.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Ringkasan dashboard berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function ringkasan(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard berhasil diambil',
            'data'    => $this->roleService->ringkasan(),
        ]);
    }
}
