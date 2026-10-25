<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKontakRequest;
use App\Http\Requests\UpdateKontakRequest;
use App\Services\KontakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KontakController extends Controller
{
    public function __construct(
        protected KontakService $kontakService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->kontakService->getDaftarKontak($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar kontak berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    public function store(StoreKontakRequest $request): JsonResponse
    {
        $result = $this->kontakService->tambahKontak(
            $request->user(),
            $request->validated(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil ditambahkan',
            'data'    => $result['data'],
        ], 201);
    }

    public function update(UpdateKontakRequest $request, int|string $id): JsonResponse
    {
        $result = $this->kontakService->updateKontak(
            $request->user(),
            (int) $id,
            $request->validated(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $result = $this->kontakService->hapusKontak($request->user(), (int) $id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil dihapus',
            'data'    => $result['data'],
        ]);
    }
}
