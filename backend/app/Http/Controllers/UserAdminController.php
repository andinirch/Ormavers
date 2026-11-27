<?php

namespace App\Http\Controllers;

use App\Http\Requests\DaftarUserRequest;
use App\Http\Requests\TambahUserAdminRequest;
use App\Http\Requests\UbahUserAdminRequest;
use App\Services\UserAdminService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserAdminController extends Controller
{
    public function __construct(
        protected UserAdminService $userAdminService,
    ) {}

    #[OA\Get(
        path: '/api/superadmin/users',
        operationId: 'indexUsersAdmin',
        summary: 'Daftar pengguna (Super Admin)',
        description: 'Mengambil daftar pengguna dengan filter peran, status, pencarian, dan paginasi.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar pengguna berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(DaftarUserRequest $request): JsonResponse
    {
        $result = $this->userAdminService->daftarPengguna($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil',
            'data'    => $result,
        ]);
    }

    #[OA\Post(
        path: '/api/superadmin/users',
        operationId: 'storeUserAdmin',
        summary: 'Tambah akun administrator',
        description: 'Menambahkan akun administrator baru.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'username', 'password', 'peran'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string'),
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'peran', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Akun Administrator Berhasil Ditambahkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(TambahUserAdminRequest $request): JsonResponse
    {
        $user = $this->userAdminService->tambahAdministrator(
            $request->validated(),
            $request->user()->id_pengguna,
        );

        return response()->json([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Ditambahkan',
            'data'    => [
                'user' => $user,
            ],
        ], 201);
    }

    #[OA\Put(
        path: '/api/superadmin/users/{id}',
        operationId: 'updateUserAdmin',
        summary: 'Ubah status akun pengguna',
        description: 'Mengubah status akun pengguna berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status_akun'],
                properties: [
                    new OA\Property(property: 'status_akun', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Akun Administrator Berhasil Diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UbahUserAdminRequest $request, int $id): JsonResponse
    {
        $result = $this->userAdminService->ubahPengguna(
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
            'message' => 'Akun Administrator Berhasil Diperbarui',
            'data'    => $result,
        ]);
    }
}
