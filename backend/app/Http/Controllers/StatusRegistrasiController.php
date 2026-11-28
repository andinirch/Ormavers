<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResubmitSkRequest;
use App\Services\LogAktivitasService;
use App\Services\StatusRegistrasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StatusRegistrasiController extends Controller
{
    public function __construct(
        protected StatusRegistrasiService $statusRegistrasiService,
        protected LogAktivitasService $logAktivitasService,
    ) {}

    #[OA\Get(
        path: '/api/registration/status',
        summary: 'Cek status registrasi akun',
        description: 'Mendapatkan status verifikasi pendaftaran akun organisasi (BEM / HMJ / UKM) atau anggota yang sedang dalam status menunggu atau ditolak.',
        tags: ['Autentikasi & Registrasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status registrasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Status registrasi berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'menunggu'),
                                new OA\Property(property: 'tipe', type: 'string', example: 'organisasi'),
                                new OA\Property(property: 'nama_organisasi', type: 'string', example: 'BEM UI 2026'),
                                new OA\Property(property: 'alasan_penolakan', type: 'string', nullable: true),
                                new OA\Property(property: 'bisa_unggah_ulang', type: 'boolean', example: false)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Data pendaftaran tidak ditemukan')
        ]
    )]
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

    #[OA\Post(
        path: '/api/registration/resubmit-sk',
        summary: 'Unggah ulang dokumen SK registrasi yang ditolak',
        description: 'Mengunggah ulang berkas dokumen SK / kepengurusan / KTM bagi organisasi yang pendaftarannya berstatus ditolak.',
        tags: ['Autentikasi & Registrasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'foto_ktm', type: 'string', format: 'binary', nullable: true, description: 'Foto KTM JPG/JPEG max 1MB'),
                        new OA\Property(property: 'sk_organisasi', type: 'string', format: 'binary', nullable: true, description: 'File SK Organisasi PDF max 2MB'),
                        new OA\Property(property: 'sk_kepengurusan', type: 'string', format: 'binary', nullable: true, description: 'File SK Kepengurusan PDF max 2MB')
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dokumen SK Berhasil Diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Dokumen SK Berhasil Diperbarui'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Bukan akun organisasi'),
            new OA\Response(response: 409, description: 'Status pendaftaran bukan ditolak'),
            new OA\Response(response: 422, description: 'Validasi form atau file gagal')
        ]
    )]
    public function resubmitSk(ResubmitSkRequest $request): JsonResponse
    {
        $idPelaku = $request->user()->id_pengguna;

        $files = [
            'foto_ktm'        => $request->file('foto_ktm'),
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
}
