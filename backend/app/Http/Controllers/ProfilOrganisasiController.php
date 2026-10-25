<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDeskripsiOrganisasiRequest;
use App\Services\ProfilOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilOrganisasiController extends Controller
{
    public function __construct(
        protected ProfilOrganisasiService $profilOrganisasiService,
    ) {}

    public function showDeskripsi(Request $request): JsonResponse
    {
        $result = $this->profilOrganisasiService->getDeskripsi($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deskripsi organisasi berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    public function updateDeskripsi(UpdateDeskripsiOrganisasiRequest $request): JsonResponse
    {
        $result = $this->profilOrganisasiService->updateDeskripsi(
            $request->user(),
            $request->validated('deskripsi'),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deskripsi organisasi berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }
}
