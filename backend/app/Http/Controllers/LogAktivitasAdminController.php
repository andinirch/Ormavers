<?php

namespace App\Http\Controllers;

use App\Http\Requests\DaftarLogAktivitasRequest;
use App\Services\LogAktivitasQueryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class LogAktivitasAdminController extends Controller
{
    public function __construct(
        protected LogAktivitasQueryService $logQueryService,
    ) {}

    #[OA\Get(
        path: '/api/admin-panel/activities',
        operationId: 'indexActivities',
        summary: 'Daftar log aktivitas',
        description: 'Mengambil daftar log aktivitas admin dan organisasi dengan berbagai filter.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'jenis', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'pelaku', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'dari_tanggal', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sampai_tanggal', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar aktivitas berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(DaftarLogAktivitasRequest $request): JsonResponse
    {
        $result = $this->logQueryService->daftarAktivitas($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar aktivitas berhasil diambil',
            'data'    => $result,
        ]);
    }

    #[OA\Get(
        path: '/api/admin-panel/activities/filter-options',
        operationId: 'activitiesFilterOptions',
        summary: 'Pilihan filter log aktivitas',
        description: 'Mengambil daftar opsi filter untuk log aktivitas.',
        tags: ['Super Admin & Admin Panel'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Pilihan filter aktivitas berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function filterOptions(): JsonResponse
    {
        $result = $this->logQueryService->filterOptions();

        return response()->json([
            'success' => true,
            'message' => 'Pilihan filter aktivitas berhasil diambil',
            'data'    => $result,
        ]);
    }
}
