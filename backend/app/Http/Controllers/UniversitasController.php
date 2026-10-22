<?php

namespace App\Http\Controllers;

use App\Models\Universitas;
use Illuminate\Http\JsonResponse;

class UniversitasController extends Controller
{
    public function index(): JsonResponse
    {
        $universitas = Universitas::orderBy('nama_universitas', 'asc')
            ->get(['id_universitas', 'nama_universitas', 'logo']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar universitas berhasil diambil',
            'data'    => [
                'universitas' => $universitas,
            ],
        ]);
    }
}
