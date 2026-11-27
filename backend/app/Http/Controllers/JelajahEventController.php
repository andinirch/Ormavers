<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class JelajahEventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
    ) {}

    /**
     * ORMV-1-30 & ORMV-1-31: Jelajah Event, Cari & Filter
     */
    #[OA\Get(
        path: '/api/events',
        operationId: 'jelajahEventIndex',
        summary: 'Jelajah event, cari & filter',
        description: 'Menampilkan daftar event yang tersedia dengan fitur pencarian dan filter.',
        tags: ['Jelajah & Share Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'kategori', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal_mulai', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_selesai', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'jenis_event', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar event berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->eventService->getJelajahEvent($request->user(), $request->all());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-32: Lihat Detail Event Lengkap
     */
    #[OA\Get(
        path: '/api/events/{id}',
        operationId: 'jelajahEventShow',
        summary: 'Lihat detail event lengkap',
        description: 'Menampilkan detail lengkap sebuah event.',
        tags: ['Jelajah & Share Event'],
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
        $result = $this->eventService->getDetailEventPublik($request->user(), (int) $id);

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
     * ORMV-1-33: Dapatkan Tautan Bagikan Event
     */
    #[OA\Post(
        path: '/api/events/{id}/bagikan',
        operationId: 'bagikanEvent',
        summary: 'Dapatkan tautan bagikan event',
        description: 'Menghasilkan tautan untuk membagikan event.',
        tags: ['Jelajah & Share Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tautan bagikan event berhasil didapatkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
        ]
    )]
    public function bagikan(Request $request, int|string $id): JsonResponse
    {
        $result = $this->eventService->bagikanEvent($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tautan bagikan event berhasil didapatkan',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-33: Buka Event via Sharelink
     */
    #[OA\Get(
        path: '/api/events/share/{token}',
        operationId: 'bukaSharelink',
        summary: 'Buka event via sharelink',
        description: 'Mengakses detail event melalui tautan berbagi.',
        tags: ['Jelajah & Share Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
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
    public function bukaSharelink(Request $request, string $token): JsonResponse
    {
        $result = $this->eventService->bukaSharelink($request->user(), $token);

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
}
