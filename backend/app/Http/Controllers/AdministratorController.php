<?php

namespace App\Http\Controllers;

use App\Http\Requests\TambahAdministratorRequest;
use App\Services\AdministratorService;
use Illuminate\Http\JsonResponse;

class AdministratorController extends Controller
{
    public function __construct(
        protected AdministratorService $administratorService,
    ) {}

    public function index(): JsonResponse
    {
        $administrators = $this->administratorService->daftarAdministrator();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Administrator berhasil diambil',
            'data'    => [
                'administrators' => $administrators,
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar seluruh pengguna berhasil diambil',
            'data' => [
                'users' => $this->administratorService->daftarSemuaPengguna(),
            ],
        ]);
    }

    public function store(TambahAdministratorRequest $request): JsonResponse
    {
        $administrator = $this->administratorService->tambahAdministrator(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Ditambahkan',
            'data'    => [
                'administrator' => $administrator,
            ],
        ], 201);
    }

    public function nonaktifkan(int $id): JsonResponse
    {
        $result = $this->administratorService->nonaktifkanAdministrator($id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Akun Administrator Berhasil Dinonaktifkan',
            'data'    => [
                'administrator' => $result['administrator'],
            ],
        ]);
    }
}
