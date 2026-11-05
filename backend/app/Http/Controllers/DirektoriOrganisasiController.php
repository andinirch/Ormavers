<?php

namespace App\Http\Controllers;

use App\Services\DirektoriOrganisasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirektoriOrganisasiController extends Controller
{
    public function __construct(
        protected DirektoriOrganisasiService $direktoriService,
    ) {}

    /**
     * Menampilkan daftar direktori universitas dengan fitur pencarian dan pagination.
     */
    public function universitas(Request $request): JsonResponse
    {
        $search  = $request->input('search');
        $perPage = (int) $request->input('per_page', 8);
        $sort    = $request->input('sort');

        // Mengirim parameter pencarian, limit pagination, dan pengurutan ke service
        $paginator = $this->direktoriService->getDaftarUniversitas($search, $perPage, $sort);

        return response()->json([
            'success' => true,
            'message' => 'Daftar universitas berhasil diambil',
            'data'    => $paginator->items(),
            'links'   => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'from'         => $paginator->firstItem(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
            ],
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