<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortofolioEventRequest;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortofolioEventController extends Controller
{
    public function __construct(
        protected EventService $eventService,
    ) {}

    /**
     * ORMV-1-27: Ambil Daftar Event Selesai Milik Organisasi
     */
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
