<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
    ) {}

    /**
     * ORMV-1-26: Lihat Event Saya
     */
    #[OA\Get(
        path: '/api/organisasi/event',
        operationId: 'indexEventOrganisasi',
        summary: 'Lihat event saya',
        description: 'Mengambil daftar event milik organisasi pengguna.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar event berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->eventService->getEventSaya($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => empty($result['data']) ? 'Belum ada event' : 'Daftar event berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-21: Buat Event
     */
    #[OA\Post(
        path: '/api/organisasi/event',
        operationId: 'storeEventOrganisasi',
        summary: 'Buat event baru',
        description: 'Membuat event baru untuk organisasi.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nama_event', 'deskripsi_event', 'id_kategori', 'jenis_event', 'tanggal_mulai', 'tanggal_selesai', 'lokasi'],
                    properties: [
                        new OA\Property(property: 'nama_event', type: 'string'),
                        new OA\Property(property: 'deskripsi_event', type: 'string'),
                        new OA\Property(property: 'id_kategori', type: 'integer'),
                        new OA\Property(property: 'jenis_event', type: 'string'),
                        new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'lokasi', type: 'string'),
                        new OA\Property(property: 'poster[]', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Event berhasil dibuat', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreEventRequest $request): JsonResponse
    {
        $posters = $request->file('poster', []);

        $result = $this->eventService->buatEvent(
            $request->user(),
            $request->validated(),
            is_array($posters) ? $posters : [$posters]
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event berhasil dibuat',
            'data'    => $result['data'],
        ], 201);
    }

    /**
     * Detail Event
     */
    #[OA\Get(
        path: '/api/organisasi/event/{id}',
        operationId: 'showEventOrganisasi',
        summary: 'Detail event organisasi',
        description: 'Mengambil detail event berdasarkan ID.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail event berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
        ]
    )]
    public function show(Request $request, int|string $id): JsonResponse
    {
        $result = $this->eventService->getDetailEvent($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail event berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-24: Edit Event
     */
    #[OA\Post(
        path: '/api/organisasi/event/{id}',
        operationId: 'updateEvent',
        summary: 'Edit event',
        description: 'Memperbarui event berdasarkan ID. Gunakan POST karena Route::match([put,patch,post]).',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'nama_event', type: 'string'),
                    new OA\Property(property: 'deskripsi_event', type: 'string'),
                    new OA\Property(property: 'id_kategori', type: 'integer'),
                    new OA\Property(property: 'jenis_event', type: 'string'),
                    new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'lokasi', type: 'string'),
                    new OA\Property(property: 'poster[]', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Event berhasil diperbarui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateEventRequest $request, int|string $id): JsonResponse
    {
        $posters = $request->file('poster', []);
        $postersArray = $posters ? (is_array($posters) ? $posters : [$posters]) : null;

        try {
            $result = $this->eventService->editEvent(
                $request->user(),
                (int) $id,
                $request->validated(),
                $postersArray
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-25: Hapus Event
     */
    #[OA\Delete(
        path: '/api/organisasi/event/{id}',
        operationId: 'destroyEventOrganisasi',
        summary: 'Hapus event',
        description: 'Menghapus event berdasarkan ID.',
        tags: ['Manajemen Event Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Event berhasil dihapus', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
        ]
    )]
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $result = $this->eventService->hapusEvent($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Event berhasil dihapus',
            'data'    => $result['data'],
        ]);
    }
}
