<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanPermissionRequest;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    public function store(SimpanPermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->buat($request->validated(), $request->user()->id_pengguna);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil dibuat',
            'data' => ['permission' => $permission],
        ], 201);
    }

    public function update(SimpanPermissionRequest $request, int $id): JsonResponse
    {
        $result = $this->permissionService->ubah($id, $request->validated(), $request->user()->id_pengguna);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil diperbarui',
            'data' => ['permission' => $result],
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->permissionService->hapus($id, $request->user()->id_pengguna);

        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], $result['status']);
        }

        return response()->json(['success' => true, 'message' => $result['message']]);
    }
}
