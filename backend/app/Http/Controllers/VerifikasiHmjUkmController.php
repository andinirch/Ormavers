<?php

namespace App\Http\Controllers;

use App\Http\Requests\TolakRegistrasiRequest;
use App\Services\VerifikasiHmjUkmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiHmjUkmController extends Controller
{
    public function __construct(
        protected VerifikasiHmjUkmService $verifikasiHmjUkmService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->verifikasiHmjUkmService->daftarPendaftaran($request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar pendaftaran HMJ/UKM berhasil diambil',
            'data'    => $result,
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $result = $this->verifikasiHmjUkmService->detailPendaftaran($id, $request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pendaftaran HMJ/UKM berhasil diambil',
            'data'    => $result,
        ]);
    }

    public function dokumen(int $id, string $jenis, Request $request): StreamedResponse|JsonResponse
    {
        $result = $this->verifikasiHmjUkmService->ambilDokumen($id, $jenis, $request->user());

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
        $result = $this->verifikasiHmjUkmService->setujui($id, $request->user(), $request->user()->id_pengguna);

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
        $result = $this->verifikasiHmjUkmService->tolak(
            $id,
            $request->validated()['alasan'],
            $request->user(),
            $request->user()->id_pengguna,
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
