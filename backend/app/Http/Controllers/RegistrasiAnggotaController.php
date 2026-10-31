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

    public function ajukanUlang(AjukanUlangFotoKtmRequest $request): JsonResponse
    {
        $data = $request->validated();
        $fotoKtm = $request->file('foto_ktm');

        // Memanggil service untuk menangani logika resubmit foto KTM
        $pendaftaran = $this->registrasiAnggotaService->ajukanUlang($request->user(), $data, $fotoKtm);

        return response()->json([
            'success' => true,
            'message' => 'Foto KTM berhasil diajukan ulang',
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ], 200);
    }
}
