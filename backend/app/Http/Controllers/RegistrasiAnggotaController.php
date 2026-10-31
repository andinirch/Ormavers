<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrasiAnggotaRequest;
use App\Http\Requests\AjukanUlangFotoKtmRequest;
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
        $user = $request->user();

        // 1. Ambil data keanggotaan terbaru dari relasi user
        $keanggotaan = $user->keanggotaan()->latest('id_keanggotaan')->first();
        if (!$keanggotaan) {
            return response()->json([
                'success' => false,
                'message' => 'Data keanggotaan tidak ditemukan',
            ], 404);
        }

        $idKeanggotaan = $keanggotaan->id_keanggotaan;
        $data = $request->validated();
        $fotoKtm = $request->file('foto_ktm');

        // 2. Panggil service
        $hasil = $this->registrasiAnggotaService->ajukanUlang($idKeanggotaan, $data, $fotoKtm, $user);

        // 3. Tangani jika service mengembalikan error
        if (isset($hasil['error'])) {
            return response()->json([
                'success' => false,
                'message' => $hasil['error'],
            ], $hasil['status'] ?? 400);
        }

        return response()->json([
            'success' => true,
            'message' => $hasil['message'] ?? 'Foto KTM berhasil diajukan ulang',
            'data'    => $hasil,
        ], 200);
    }
}
