<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectMemberRequest;
use App\Services\OrgMemberRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrgMemberRequestController extends Controller
{
    public function __construct(
        protected OrgMemberRequestService $memberRequestService,
    ) {}

    #[OA\Get(
        path: '/api/org/member-requests',
        operationId: 'indexMemberRequests',
        summary: 'Daftar permintaan keanggotaan',
        description: 'Mengambil daftar permintaan keanggotaan organisasi.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar permintaan keanggotaan berhasil diambil', content: new OA\JsonContent(properties: [
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
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $result = $this->memberRequestService->daftarRequests($request->user(), $page, $perPage);

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar permintaan keanggotaan berhasil diambil',
            'data'    => $result,
        ]);
    }

    #[OA\Get(
        path: '/api/org/member-requests/{id}',
        operationId: 'showMemberRequest',
        summary: 'Detail permintaan keanggotaan',
        description: 'Mengambil detail permintaan keanggotaan berdasarkan ID.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail permintaan keanggotaan berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Permintaan tidak ditemukan'),
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        $result = $this->memberRequestService->detailRequest($id, $request->user());

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail permintaan keanggotaan berhasil diambil',
            'data'    => $result,
        ]);
    }

    #[OA\Get(
        path: '/api/org/member-requests/{id}/dokumen/ktm',
        operationId: 'dokumenKtmMember',
        summary: 'Ambil dokumen KTM anggota',
        description: 'Mengambil file dokumen KTM dari permintaan keanggotaan.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File dokumen KTM berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Dokumen tidak ditemukan'),
        ]
    )]
    public function dokumenKtm(int $id, Request $request): StreamedResponse|JsonResponse
    {
        $result = $this->memberRequestService->ambilKtmPath($id, $request->user());

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
        path: '/api/org/member-requests/{id}/approve',
        operationId: 'approveMember',
        summary: 'Setujui permintaan keanggotaan',
        description: 'Menyetujui permintaan keanggotaan berdasarkan ID.',
        tags: ['Verifikasi Registrasi & Keanggotaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Permintaan keanggotaan berhasil disetujui', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Permintaan tidak ditemukan'),
        ]
    )]
    public function approve(int $id, Request $request): JsonResponse
    {
        $result = $this->memberRequestService->approve(
            $id,
            $request->user(),
            $request->user()->id_pengguna
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

    #[OA\Post(
        path: '/api/org/member-requests/{id}/reject',
        operationId: 'rejectMember',
        summary: 'Tolak permintaan keanggotaan',
        description: 'Menolak permintaan keanggotaan berdasarkan ID dengan alasan.',
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
            new OA\Response(response: 200, description: 'Permintaan keanggotaan berhasil ditolak', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Permintaan tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function reject(int $id, RejectMemberRequest $request): JsonResponse
    {
        $result = $this->memberRequestService->reject(
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
