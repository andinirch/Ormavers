<?php

namespace App\Http\Controllers;

use App\Http\Requests\TambahAdministratorRequest;
use App\Http\Requests\UbahStatusAdministratorRequest;
use App\Services\AdministratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdministratorController extends Controller
{
    public function __construct(
        protected AdministratorService $administratorService,
    ) {}

    #[OA\Get(
        path: '/api/superadmin/administrators',
        operationId: 'indexAdministrators',
        summary: 'Daftar administrator',
        description: 'Mengambil daftar seluruh administrator.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar Administrator berhasil diambil', content: new OA\JsonContent(properties: [
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
        $administrators = $this->administratorService->daftarAdministrator();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Administrator berhasil diambil',
            'data'    => [
                'administrators' => $administrators,
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar seluruh pengguna berhasil diambil',
            'data' => [
                'users' => $this->administratorService->daftarSemuaPengguna(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/superadmin/administrators',
        operationId: 'storeAdministrator',
        summary: 'Tambah administrator',
        description: 'Menambahkan akun administrator baru.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'username', 'password'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string'),
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
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
    public function store(TambahAdministratorRequest $request): JsonResponse
    {
        $administrator = $this->administratorService->tambahAdministrator(
            $request->validated(),
            $request->user()->id_pengguna,
        );

        return response()->json([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Ditambahkan',
            'data'    => [
                'administrator' => $administrator,
            ],
        ], 201);
    }

    #[OA\Patch(
        path: '/api/superadmin/administrators/{id}/nonaktif',
        operationId: 'nonaktifkanAdministrator',
        summary: 'Nonaktifkan administrator',
        description: 'Menonaktifkan akun administrator berdasarkan ID.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Akun Administrator Berhasil Dinonaktifkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Administrator tidak ditemukan'),
        ]
    )]
    public function nonaktifkan(int $id, Request $request): JsonResponse
    {
        $result = $this->administratorService->nonaktifkanAdministrator(
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
            'message' => 'Akun Administrator Berhasil Dinonaktifkan',
            'data'    => [
                'administrator' => $result['administrator'],
            ],
        ]);
    }

    #[OA\Patch(
        path: '/api/superadmin/administrators/{id}/status',
        operationId: 'ubahStatusAdministrator',
        summary: 'Ubah status administrator',
        description: 'Mengubah status akun administrator (aktif/nonaktif).',
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
                    new OA\Property(property: 'status_akun', type: 'string', enum: ['aktif', 'nonaktif']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status akun administrator berhasil diubah', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Administrator tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function ubahStatus(
        int $id,
        UbahStatusAdministratorRequest $request
    ): JsonResponse {
        $statusBaru = $request->validated()['status_akun'];

        $result = $this->administratorService->ubahStatusAdministrator(
            $id,
            $statusBaru,
            $request->user()->id_pengguna,
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        $message = $statusBaru === 'aktif'
            ? 'Akun Administrator Berhasil Diaktifkan'
            : 'Akun Administrator Berhasil Dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'administrator' => $result['administrator'],
            ],
        ]);
    }
}
