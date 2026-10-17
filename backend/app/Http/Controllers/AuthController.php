<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('username'),
            $request->input('password'),
            $request->ip(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token'       => $result['token'],
                'token_type'  => 'Bearer',
                'pengguna'    => $result['pengguna'],
                'roles'       => $result['roles'],
                'permissions' => $result['permissions'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $spatie   = $this->authService->dataSpatie($pengguna);

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diambil',
            'data'    => [
                'pengguna'    => $this->authService->dataPengguna($pengguna),
                'roles'       => $spatie['roles'],
                'permissions' => $spatie['permissions'],
            ],
        ]);
    }
}
