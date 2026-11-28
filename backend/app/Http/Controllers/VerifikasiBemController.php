<?php

namespace App\Http\Controllers;

use App\Http\Requests\TolakRegistrasiRequest;
use App\Services\VerifikasiBemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiBemController extends Controller
{
    public function __construct(
        protected VerifikasiBemService $verifikasiBemService,
    ) {}

    #[OA\Get(
        path: '/api/admin/registrations/bem',
        operationId: 'indexBemRegistrations',
        summary: 'Daftar pendaftaran BEM',
        description: 'Mengambil daftar pendaftaran BEM untuk verifikasi.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar pendaftaran BEM berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/admin/registrations/bem/{id}',
        operationId: 'showBemRegistration',
        summary: 'Detail pendaftaran BEM',
        description: 'Mengambil detail pendaftaran BEM berdasarkan ID.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail pendaftaran BEM berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Pendaftaran tidak ditemukan'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/admin/registrations/bem/{id}/dokumen/{jenis}',
        operationId: 'dokumenBem',
        summary: 'Ambil dokumen pendaftaran BEM',
        description: 'Mengambil file dokumen pendaftaran BEM berdasarkan jenis.',
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

    #[OA\Post(
        path: '/api/admin/registrations/bem/{id}/approve',
        operationId: 'approveBem',
        summary: 'Setujui pendaftaran BEM',
        description: 'Menyetujui pendaftaran BEM berdasarkan ID.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pendaftaran BEM berhasil disetujui', content: new OA\JsonContent(properties: [
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
        $result = $this->verifikasiBemService->setujui($id, $request->user()->id_pengguna);

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
        path: '/api/admin/registrations/bem/{id}/reject',
        operationId: 'rejectBem',
        summary: 'Tolak pendaftaran BEM',
        description: 'Menolak pendaftaran BEM berdasarkan ID dengan alasan.',
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
            new OA\Response(response: 200, description: 'Pendaftaran BEM berhasil ditolak', content: new OA\JsonContent(properties: [
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
        $result = $this->verifikasiBemService->tolak(
            $id,
            $request->validated()['alasan'],
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
