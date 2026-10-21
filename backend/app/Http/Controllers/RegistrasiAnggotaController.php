<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrasiAnggotaRequest;
use App\Services\RegistrasiAnggotaService;
use Illuminate\Http\JsonResponse;

class RegistrasiAnggotaController extends Controller
{
    public function __construct(
        protected RegistrasiAnggotaService $registrasiAnggotaService,
    ) {}

    public function register(RegistrasiAnggotaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $fotoKtm = $request->file('foto_ktm');

        $pendaftaran = $this->registrasiAnggotaService->register($data, $fotoKtm);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran anggota berhasil dikirim',
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ], 201);
    }
}
