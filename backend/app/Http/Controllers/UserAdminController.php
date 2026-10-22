<?php

namespace App\Http\Controllers;

use App\Http\Requests\DaftarUserRequest;
use App\Http\Requests\TambahUserAdminRequest;
use App\Http\Requests\UbahUserAdminRequest;
use App\Services\UserAdminService;
use Illuminate\Http\JsonResponse;

class UserAdminController extends Controller
{
    public function __construct(
        protected UserAdminService $userAdminService,
    ) {}

    public function index(DaftarUserRequest $request): JsonResponse
    {
        $result = $this->userAdminService->daftarPengguna($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil',
            'data'    => $result,
        ]);
    }

    public function store(TambahUserAdminRequest $request): JsonResponse
    {
        $user = $this->userAdminService->tambahAdministrator(
            $request->validated(),
            $request->user()->id_pengguna,
        );

        return response()->json([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Ditambahkan',
            'data'    => [
                'user' => $user,
            ],
        ], 201);
    }

    public function update(UbahUserAdminRequest $request, int $id): JsonResponse
    {
        $result = $this->userAdminService->ubahPengguna(
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
            'message' => 'Akun Administrator Berhasil Diperbarui',
            'data'    => $result,
        ]);
    }
}
