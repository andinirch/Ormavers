<?php

namespace App\Http\Controllers;

use App\Models\BidangOrganisasi;
use App\Models\Universitas;
use Illuminate\Http\JsonResponse;

class MasterDataController extends Controller
{
    /**
     * Mendapatkan daftar universitas untuk dropdown/pilihan.
     */
    public function universitas(): JsonResponse
    {
        $universitas = Universitas::select('id_universitas', 'nama_universitas', 'logo')
            ->orderBy('nama_universitas', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $universitas,
        ]);
    }

    /**
     * Mendapatkan daftar bidang organisasi untuk dropdown/pilihan.
     */
    public function bidangOrganisasi(): JsonResponse
    {
        $bidang = BidangOrganisasi::select('id_bidang', 'nama_bidang')
            ->orderBy('id_bidang', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $bidang,
        ]);
    }
}
