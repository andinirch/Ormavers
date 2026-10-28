<?php

namespace App\Http\Controllers;

use App\Http\Requests\IdentitasOrganisasiRequest;
use App\Services\IdentitasOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentitasOrganisasiController extends Controller
{
    public function __construct(
        protected IdentitasOrganisasiService $identitasOrganisasiService,
    ) {}

    /**
     * Tampilkan data identitas organisasi milik pengguna saat ini.
     */
    public function show(Request $request): JsonResponse
    {
        $result = $this->identitasOrganisasiService->getIdentitas($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas organisasi berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * Perbarui data identitas organisasi (beserta logo dan masa berlaku SK).
     */
    public function update(IdentitasOrganisasiRequest $request): JsonResponse
    {
        $result = $this->identitasOrganisasiService->updateIdentitas(
            pengguna: $request->user(),
            data: $request->validated(),
            logoFile: $request->file('logo'),
            logoUniversitasFile: $request->file('logo_universitas'),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Identitas organisasi berhasil diperbarui',
            'data'    => $result['data'],
        ]);
    }
}
