<?php

namespace App\Http\Controllers;

use App\Services\PencarianProfilOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PencarianProfilOrganisasiController extends Controller
{
    public function __construct(
        protected PencarianProfilOrganisasiService $pencarianProfilService,
    ) {}

    /**
     * Cari dan filter daftar organisasi aktif.
     */
    public function cari(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'id_universitas', 'jenis_organisasi', 'id_bidang']);

        $result = $this->pencarianProfilService->cariOrganisasi($filters);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'],
        ], $result['status']);
    }

    /**
     * Lihat profil lengkap organisasi.
     */
    public function show(int|string $id_organisasi): JsonResponse
    {
        $result = $this->pencarianProfilService->getProfilOrganisasi((int) $id_organisasi);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ], $result['status']);
    }
}
