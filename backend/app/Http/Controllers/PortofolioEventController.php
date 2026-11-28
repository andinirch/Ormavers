<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortofolioEventRequest;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PortofolioEventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
    ) {}

    /**
     * ORMV-1-27: Ambil Daftar Event Selesai Milik Organisasi
     */
    #[OA\Get(
        path: '/api/organisasi/events-selesai',
        operationId: 'eventSelesai',
        summary: 'Daftar event selesai milik organisasi',
        description: 'Mengambil daftar event yang sudah selesai milik organisasi untuk portofolio.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar event selesai berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function eventSelesai(Request $request): JsonResponse
    {
        $result = $this->eventService->getEventSelesaiUntukPortofolio($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar event selesai berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-27: Ambil Prefill Formulir Portofolio dari Event
     */
    #[OA\Get(
        path: '/api/organisasi/events-selesai/{id}/prefill',
        operationId: 'prefillPortofolioEvent',
        summary: 'Prefill formulir portofolio dari event',
        description: 'Mengambil data prefill untuk formulir portofolio berdasarkan event selesai.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data formulir portofolio berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
        ]
    )]
    public function prefill(Request $request, int|string $id): JsonResponse
    {
        $result = $this->eventService->getPrefillPortofolio($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data formulir portofolio berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-27: Simpan Event ke Portofolio Organisasi
     */
    #[OA\Post(
        path: '/api/organisasi/events-selesai/{id}/portofolio',
        operationId: 'storePortofolioEvent',
        summary: 'Simpan event ke portofolio',
        description: 'Menyimpan event yang sudah selesai ke dalam portofolio organisasi.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
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
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePortofolioEventRequest $request, int|string $id): JsonResponse
    {
        $result = $this->eventService->tambahEventKePortofolio(
            $request->user(),
            (int) $id,
            $request->validated(),
            $request->file('dokumentasi')
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
}
