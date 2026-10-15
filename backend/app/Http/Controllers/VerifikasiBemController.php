<?php

namespace App\Http\Controllers;

use App\Http\Requests\TolakRegistrasiRequest;
use App\Services\VerifikasiBemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiBemController extends Controller
{
    public function __construct(
        protected VerifikasiBemService $verifikasiBemService,
    ) {}

    public function index(): JsonResponse
    {
        $pendaftaran = $this->verifikasiBemService->daftarPendaftaran();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pendaftaran BEM berhasil diambil',
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->verifikasiBemService->detailPendaftaran($id);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pendaftaran BEM berhasil diambil',
            'data'    => $result,
        ]);
    }

    public function dokumen(int $id, string $jenis): StreamedResponse|JsonResponse
    {
        $result = $this->verifikasiBemService->ambilDokumen($id, $jenis);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($result['path'])) {
            return response()->json([
                'success' => false,
                'message' => 'File dokumen tidak ditemukan',
            ], 404);
        }

        return $disk->response($result['path']);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $result = $this->verifikasiBemService->setujui($id, $request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function reject(int $id, TolakRegistrasiRequest $request): JsonResponse
    {
        $result = $this->verifikasiBemService->tolak(
            $id,
            $request->validated()['alasan'],
            $request->user(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }
}
