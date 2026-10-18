<?php

namespace App\Http\Controllers;

use App\Http\Requests\DaftarLogAktivitasRequest;
use App\Services\LogAktivitasQueryService;
use Illuminate\Http\JsonResponse;

class LogAktivitasAdminController extends Controller
{
    public function __construct(
        protected LogAktivitasQueryService $logQueryService,
    ) {}

    public function index(DaftarLogAktivitasRequest $request): JsonResponse
    {
        $result = $this->logQueryService->daftarAktivitas($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar aktivitas berhasil diambil',
            'data'    => $result,
        ]);
    }

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
