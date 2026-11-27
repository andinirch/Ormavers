<?php

namespace App\Http\Controllers;

use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotifikasiController extends Controller
{
    public function __construct(
        protected NotifikasiService $notifikasiService,
    ) {}

    /**
     * Tampilkan daftar notifikasi milik organisasi.
     */
    #[OA\Get(
        path: '/api/organisasi/notifikasi',
        operationId: 'indexNotifikasi',
        summary: 'Daftar notifikasi organisasi',
        description: 'Mengambil daftar notifikasi milik organisasi pengguna.',
        tags: ['Notifikasi Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar notifikasi berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->notifikasiService->getDaftarNotifikasi($request->user(), $request->query());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data'],
        ]);
    }

    /**
     * Dapatkan jumlah notifikasi yang belum dibaca (badge ikon lonceng).
     */
    #[OA\Get(
        path: '/api/organisasi/notifikasi/unread-count',
        operationId: 'unreadCountNotifikasi',
        summary: 'Jumlah notifikasi belum dibaca',
        description: 'Mengambil jumlah notifikasi yang belum dibaca untuk badge ikon lonceng.',
        tags: ['Notifikasi Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Jumlah notifikasi belum dibaca', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'unread_count', type: 'integer', example: 5),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'unread_count', type: 'integer', example: 5),
                    ]
                ),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        $result = $this->notifikasiService->getUnreadCount($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        $unreadCount = $result['data']['unread_count'] ?? 0;

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'data'         => $result['data'],
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    #[OA\Post(
        path: '/api/organisasi/notifikasi/{id}/baca',
        operationId: 'markNotifikasiAsRead',
        summary: 'Tandai notifikasi sebagai dibaca',
        description: 'Menandai satu notifikasi sebagai sudah dibaca.',
        tags: ['Notifikasi Organisasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifikasi berhasil ditandai dibaca', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Notifikasi tidak ditemukan'),
        ]
    )]
    public function markAsRead(Request $request, int|string $id): JsonResponse
    {
        $result = $this->notifikasiService->tandaiDibaca($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data'],
        ]);
    }

    /**
     * Tandai seluruh notifikasi belum dibaca menjadi sudah dibaca.
     */
    #[OA\Post(
        path: '/api/organisasi/notifikasi/baca-semua',
        operationId: 'markAllNotifikasiAsRead',
        summary: 'Tandai semua notifikasi sebagai dibaca',
        description: 'Menandai seluruh notifikasi belum dibaca menjadi sudah dibaca.',
        tags: ['Notifikasi Organisasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Semua notifikasi berhasil ditandai dibaca', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $result = $this->notifikasiService->tandaiSemuaDibaca($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data'],
        ]);
    }
}
