<?php

namespace App\Http\Controllers;

use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function __construct(
        protected NotifikasiService $notifikasiService,
    ) {}

    /**
     * Tampilkan daftar notifikasi milik organisasi.
     */
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
    public function unreadCount(Request $request): JsonResponse
    {
        $result = $this->notifikasiService->getUnreadCount($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data'],
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
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
