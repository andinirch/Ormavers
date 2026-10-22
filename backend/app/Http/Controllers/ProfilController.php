<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfilRequest;
use App\Http\Requests\UploadFotoProfilRequest;
use App\Services\ProfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilController extends Controller
{
    public function __construct(
        protected ProfilService $profilService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profil = $this->profilService->getProfil($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data'    => $profil,
        ]);
    }

    public function update(UpdateProfilRequest $request): JsonResponse
    {
        $profil = $this->profilService->updateProfil(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Data Berhasil Diperbarui',
            'data'    => $profil,
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profilService->updatePassword(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Password Berhasil Diubah',
        ]);
    }

    public function uploadFoto(UploadFotoProfilRequest $request): JsonResponse
    {
        $result = $this->profilService->uploadFoto(
            $request->user(),
            $request->file('foto'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto Profil Berhasil Diubah',
            'data'    => $result,
        ]);
    }
}
