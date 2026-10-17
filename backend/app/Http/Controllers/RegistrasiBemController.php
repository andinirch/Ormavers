<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrasiBemRequest;
use App\Services\RegistrasiBemService;
use Illuminate\Http\JsonResponse;

class RegistrasiBemController extends Controller
{
    public function __construct(
        protected RegistrasiBemService $registrasiBemService,
    ) {}

    public function register(RegistrasiBemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $files = [
            'foto_ktm'        => $request->file('foto_ktm'),
            'sk_organisasi'   => $request->file('sk_organisasi'),
            'sk_kepengurusan' => $request->file('sk_kepengurusan'),
        ];

        $pendaftaran = $this->registrasiBemService->registerBem($data, $files);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi BEM berhasil dikirim',
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ], 201);
    }
}
