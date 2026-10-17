<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Log aktivitas berhasil diambil',
            'data' => [
                'activities' => $this->activityLogService->daftarAktivitas(),
            ],
        ]);
    }
}
