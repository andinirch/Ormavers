<?php

namespace App\Http\Controllers;

use App\Http\Requests\JawabAjakanRequest;
use App\Http\Requests\KirimAjakanRequest;
use App\Services\AjakanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjakanController extends Controller
{
    public function __construct(
        protected AjakanService $ajakanService,
    ) {}

    /**
     * ORMV-1-22 & ORMV-1-23:
     * Ambil daftar organisasi yang tersedia untuk diajak kolaborasi atau diundang.
     */
    public function organisasiTersedia(Request $request, int|string $id): JsonResponse
    {
        $jenis = $request->query('jenis', 'kolaborator');
        $result = $this->ajakanService->getOrganisasiTersedia(
            $request->user(),
            (int) $id,
            $jenis,
            $request->query()
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar organisasi tersedia berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-22:
     * Kirim ajakan kolaborasi ke satu atau lebih organisasi.
     */
    public function ajakKolaborasi(KirimAjakanRequest $request, int|string $id): JsonResponse
    {
        $result = $this->ajakanService->kirimAjakan(
            $request->user(),
            (int) $id,
            'kolaborator',
            $request->validated('organisasi_ids')
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data']['ajakan'],
        ], 201);
    }

    /**
     * ORMV-1-22:
     * Batalkan ajakan kolaborasi yang belum dijawab.
     */
    public function batalkanKolaborasi(Request $request, int|string $id, int|string $id_ajakan): JsonResponse
    {
        $result = $this->ajakanService->batalkanAjakan(
            $request->user(),
            (int) $id,
            (int) $id_ajakan
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data']['ajakan'],
        ]);
    }

    /**
     * ORMV-1-23:
     * Kirim undangan event ke satu atau lebih organisasi.
     */
    public function undangOrganisasi(KirimAjakanRequest $request, int|string $id): JsonResponse
    {
        $result = $this->ajakanService->kirimAjakan(
            $request->user(),
            (int) $id,
            'undangan',
            $request->validated('organisasi_ids')
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data']['ajakan'],
        ], 201);
    }

    /**
     * ORMV-1-23:
     * Batalkan undangan yang belum dijawab.
     */
    public function batalkanUndangan(Request $request, int|string $id, int|string $id_ajakan): JsonResponse
    {
        $result = $this->ajakanService->batalkanAjakan(
            $request->user(),
            (int) $id,
            (int) $id_ajakan
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data']['ajakan'],
        ]);
    }

    /**
     * Rekapitulasi status ajakan/undangan yang dikirimkan pembuat event.
     */
    public function ajakanEvent(Request $request, int|string $id): JsonResponse
    {
        $result = $this->ajakanService->getAjakanEvent(
            $request->user(),
            (int) $id,
            $request->query('jenis')
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar ajakan event berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-28:
     * Ambil kartu Ajakan & Undangan yang diterima oleh organisasi pengguna.
     */
    public function indexAjakanMasuk(Request $request): JsonResponse
    {
        $result = $this->ajakanService->getAjakanMasuk(
            $request->user(),
            $request->only(['status', 'jenis'])
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => empty($result['data']) ? 'Tidak ada ajakan/undangan terbaru' : 'Daftar ajakan masuk berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-29:
     * Lihat detail popup ajakan atau undangan masuk.
     */
    public function showAjakanMasuk(Request $request, int|string $id_ajakan): JsonResponse
    {
        $result = $this->ajakanService->getDetailAjakanMasuk(
            $request->user(),
            (int) $id_ajakan
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail ajakan berhasil diambil',
            'data'    => $result['data'],
        ]);
    }

    /**
     * ORMV-1-29:
     * Jawab ajakan atau undangan masuk (terima atau tolak dengan alasan).
     */
    public function jawab(JawabAjakanRequest $request, int|string $id_ajakan): JsonResponse
    {
        $result = $this->ajakanService->jawabAjakan(
            $request->user(),
            (int) $id_ajakan,
            $request->validated('keputusan'),
            $request->validated('alasan_penolakan')
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['data']['pesan'],
            'data'    => $result['data']['ajakan'],
        ]);
    }
}
