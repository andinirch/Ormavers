<?php

namespace App\Http\Controllers;

use App\Services\DirektoriOrganisasiService;
use Illuminate\Http\JsonResponse;

class DirektoriOrganisasiController extends Controller
{
    public function __construct(
        protected DirektoriOrganisasiService $direktoriService,
    ) {}

    /**
     * Menampilkan daftar direktori universitas.
     */
    public function universitas(): JsonResponse
    {
        $data = $this->direktoriService->getDaftarUniversitas();

        return response()->json([
            'success' => true,
            'message' => 'Daftar universitas berhasil diambil',
            'data'    => $data,
        ]);
    }

    /**
     * Menampilkan daftar organisasi mahasiswa terverifikasi pada universitas yang dipilih.
     */
    public function organisasi(int $id_universitas): JsonResponse
    {
        $result = $this->direktoriService->getOrganisasiByUniversitas($id_universitas);

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
