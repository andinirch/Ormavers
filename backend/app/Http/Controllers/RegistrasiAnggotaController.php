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

    public function ajukanUlang(AjukanUlangFotoKtmRequest $request)
    {
        $user = $request->user();
        $profilAnggota = \App\Models\ProfilAnggota::where('id_pengguna', $user->id_pengguna)->first();
        $idKeanggotaan = $profilAnggota ? $profilAnggota->id_keanggotaan : null;

        $data = $request->validated();
        $fotoKtm = $request->file('foto_ktm');

        $pendaftaran = $this->registrasiAnggotaService->ajukanUlang($idKeanggotaan, $data, $fotoKtm, $user);

        return response()->json([
            'success' => true,
            'message' => 'Foto KTM berhasil diajukan ulang',
            'data' => [
            'pendaftaran' => $pendaftaran,
            ]
        ], 200);
    }
}
