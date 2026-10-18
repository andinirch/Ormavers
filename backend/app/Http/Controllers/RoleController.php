<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanRoleRequest;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
    ) {}

    public function permissions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar permission berhasil diambil',
            'data'    => [
                'permissions' => $this->roleService->daftarPermission(),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar role berhasil diambil',
            'data'    => [
                'roles' => $this->roleService->daftarRole(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->roleService->detailRole($id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail role berhasil diambil',
            'data'    => [
                'role' => $result,
            ],
        ]);
    }

    public function store(SimpanRoleRequest $request): JsonResponse
    {
        $result = $this->roleService->buatRole(
            $request->validated(),
            $request->user()->id_pengguna,
        );

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dibuat',
            'data'    => [
                'role' => $result,
            ],
        ], 201);
    }

    public function update(SimpanRoleRequest $request, int $id): JsonResponse
    {
        $result = $this->roleService->ubahRole(
            $id,
            $request->validated(),
            $request->user()->id_pengguna,
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diperbarui',
            'data'    => [
                'role' => $result,
            ],
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->roleService->hapusRole(
            $id,
            $request->user()->id_pengguna,
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function ringkasan(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard berhasil diambil',
            'data'    => $this->roleService->ringkasan(),
        ]);
    }
}
