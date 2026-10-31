<?php

namespace App\Http\Controllers;

use App\Services\PublicOrganisasiService;
use Illuminate\Http\JsonResponse;

class PublicOrganisasiController extends Controller
{
    public function __construct(
        protected PublicOrganisasiService $publicOrganisasiService,
    ) {}

    public function bem(): JsonResponse
    {
        $bem = $this->publicOrganisasiService->daftarBemAktif();

        return response()->json([
            'success' => true,
            'message' => 'Daftar BEM aktif berhasil diambil',
            'data'    => [
                'bem' => $bem,
            ],
        ]);
    }

    public function organisasiDiBawahBem(int $id): JsonResponse
    {
        $result = $this->publicOrganisasiService->daftarOrganisasiDiBawahBem($id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar organisasi berhasil diambil',
            'data'    => $result,
        ]);
    }

    public function organisasiBerdasarkanUniversitas(int $id_universitas): JsonResponse
    {
        // Ambil semua organisasi (BEM, HMJ, UKM) yang terdaftar di universitas tersebut
        $organisasi = \App\Models\Organisasi::where('id_universitas', $id_universitas)
            ->where('status_organisasi', 'aktif')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar organisasi universitas berhasil diambil',
            'data'    => [
            'organisasi' => $organisasi,
            ],
        ]);
    }
}
