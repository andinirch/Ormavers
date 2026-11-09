<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
    ) {}

    /**
     * ORMV-1-26: Lihat Event Saya
     */
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
