<?php

namespace App\Http\Controllers;

use App\Http\Requests\TolakRegistrasiRequest;
use App\Services\VerifikasiHmjUkmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiHmjUkmController extends Controller
{
    public function __construct(
        protected VerifikasiHmjUkmService $verifikasiHmjUkmService,
    ) {}

    #[OA\Get(
        path: '/api/org/registrations/hmj-ukm',
        operationId: 'indexHmjUkmRegistrations',
        summary: 'Daftar pendaftaran HMJ/UKM',
        description: 'Mengambil daftar pendaftaran HMJ/UKM untuk verifikasi.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar pendaftaran HMJ/UKM berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/org/registrations/hmj-ukm/{id}',
        operationId: 'showHmjUkmRegistration',
        summary: 'Detail pendaftaran HMJ/UKM',
        description: 'Mengambil detail pendaftaran HMJ/UKM berdasarkan ID.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail pendaftaran HMJ/UKM berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Pendaftaran tidak ditemukan'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/org/registrations/hmj-ukm/{id}/dokumen/{jenis}',
        operationId: 'dokumenHmjUkm',
        summary: 'Ambil dokumen pendaftaran HMJ/UKM',
        description: 'Mengambil file dokumen pendaftaran HMJ/UKM berdasarkan jenis.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'jenis', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['foto_ktm', 'sk_organisasi', 'sk_kepengurusan'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File dokumen berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Dokumen tidak ditemukan'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/org/registrations/hmj-ukm/{id}/approve',
        operationId: 'approveHmjUkm',
        summary: 'Setujui pendaftaran HMJ/UKM',
        description: 'Menyetujui pendaftaran HMJ/UKM berdasarkan ID.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pendaftaran HMJ/UKM berhasil disetujui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Pendaftaran tidak ditemukan'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/org/registrations/hmj-ukm/{id}/reject',
        operationId: 'rejectHmjUkm',
        summary: 'Tolak pendaftaran HMJ/UKM',
        description: 'Menolak pendaftaran HMJ/UKM berdasarkan ID dengan alasan.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alasan'],
                properties: [
                    new OA\Property(property: 'alasan', type: 'string'),
                    new OA\Property(property: 'dokumen_ditolak', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pendaftaran HMJ/UKM berhasil ditolak', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Pendaftaran tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function reject(int $id, TolakRegistrasiRequest $request): JsonResponse
    {
        $result = $this->verifikasiHmjUkmService->tolak(
            $id,
            $request->validated()['alasan'],
            $request->user(),
            $request->user()->id_pengguna,
            $request->getDokumenDitolak(),
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
