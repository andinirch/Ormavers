<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JelajahEventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
    ) {}

    /**
     * ORMV-1-30 & ORMV-1-31: Jelajah Event, Cari & Filter
     */
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
