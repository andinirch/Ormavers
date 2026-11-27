<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login pengguna',
        description: 'Autentikasi pengguna menggunakan username dan password. Mengembalikan bearer token Sanctum serta data peran dan permissions.',
        tags: ['Autentikasi & Registrasi'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'superadmin'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil atau login akses terbatas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|xyz123abc456'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                                new OA\Property(property: 'akses_terbatas', type: 'boolean', example: false),
                                new OA\Property(property: 'pengguna', type: 'object'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'super_admin')),
                                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string', example: 'view roles'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Kredensial tidak valid'),
            new OA\Response(response: 403, description: 'Akun dinonaktifkan / ditangguhkan'),
            new OA\Response(response: 422, description: 'Validasi form gagal'),
            new OA\Response(response: 429, description: 'Terlalu banyak percobaan login')
        ]
    )]
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

        if (isset($result['akses_terbatas']) && $result['akses_terbatas']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data'    => [
                    'token'          => $result['token'],
                    'token_type'     => 'Bearer',
                    'akses_terbatas' => true,
                    'pengguna'       => $result['pengguna'],
                    'roles'          => $result['roles'],
                    'permissions'    => $result['permissions'],
                ],
            ]);
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

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout pengguna',
        description: 'Mencabut token autentikasi saat ini.',
        tags: ['Autentikasi & Registrasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Data profil pengguna login saat ini',
        description: 'Mendapatkan informasi data pengguna saat ini beserta peran dan permission Spatie.',
        tags: ['Autentikasi & Registrasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data pengguna berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data pengguna berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'pengguna', type: 'object'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
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
