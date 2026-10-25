<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortofolioRequest;
use App\Services\PortofolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortofolioController extends Controller
{
    public function __construct(
        protected PortofolioService $portofolioService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->portofolioService->getDaftarPortofolio($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar portofolio berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    public function store(StorePortofolioRequest $request): JsonResponse
    {
        $result = $this->portofolioService->tambahPortofolio(
            $request->user(),
            $request->validated(),
            $request->file('dokumentasi'),
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
