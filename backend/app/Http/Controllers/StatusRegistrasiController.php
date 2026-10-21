<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResubmitSkRequest;
use App\Services\LogAktivitasService;
use App\Services\StatusRegistrasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusRegistrasiController extends Controller
{
    public function __construct(
        protected StatusRegistrasiService $statusRegistrasiService,
        protected LogAktivitasService $logAktivitasService,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $result = $this->statusRegistrasiService->ambilStatus($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status registrasi berhasil diambil',
            'data'    => $result,
        ]);
    }

    public function resubmitSk(ResubmitSkRequest $request): JsonResponse
    {
        $idPelaku = $request->user()->id_pengguna;

        $files = [
            'sk_organisasi'   => $request->file('sk_organisasi'),
            'sk_kepengurusan' => $request->file('sk_kepengurusan'),
        ];

        $result = $this->statusRegistrasiService->resubmitSk(
            $request->user(),
            $files,
            $idPelaku,
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen SK Berhasil Diperbarui',
            'data'    => $result,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $pengguna = $request->user();

        $pengguna->currentAccessToken()->delete();

        $this->logAktivitasService->catat(
            $pengguna->id_pengguna,
            'logout',
            'Keluar dari sistem'
        );

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}
