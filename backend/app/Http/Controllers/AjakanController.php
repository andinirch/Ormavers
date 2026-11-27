<?php

namespace App\Http\Controllers;

use App\Http\Requests\JawabAjakanRequest;
use App\Http\Requests\KirimAjakanRequest;
use App\Services\AjakanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AjakanController extends Controller
{
    public function __construct(
        protected AjakanService $ajakanService,
    ) {}

    /**
     * ORMV-1-22 & ORMV-1-23:
     * Ambil daftar organisasi yang tersedia untuk diajak kolaborasi atau diundang.
     */
    #[OA\Get(
        path: '/api/organisasi/event/{id}/organisasi-tersedia',
        operationId: 'organisasiTersedia',
        summary: 'Daftar organisasi tersedia untuk ajakan/undangan',
        description: 'Mengambil daftar organisasi yang tersedia untuk diajak kolaborasi atau diundang ke event.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'jenis', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar organisasi tersedia berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/organisasi/event/{id}/ajak-kolaborasi',
        operationId: 'ajakKolaborasi',
        summary: 'Kirim ajakan kolaborasi',
        description: 'Mengirim ajakan kolaborasi ke satu atau lebih organisasi.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['organisasi_ids'],
                properties: [
                    new OA\Property(property: 'organisasi_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Ajakan kolaborasi berhasil dikirim', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/organisasi/event/{id}/kolaborasi/{id_ajakan}/batalkan',
        operationId: 'batalkanKolaborasi',
        summary: 'Batalkan ajakan kolaborasi',
        description: 'Membatalkan ajakan kolaborasi yang belum dijawab.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id_ajakan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ajakan kolaborasi berhasil dibatalkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Ajakan tidak ditemukan'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/organisasi/event/{id}/undang',
        operationId: 'undangOrganisasi',
        summary: 'Kirim undangan event',
        description: 'Mengirim undangan event ke satu atau lebih organisasi.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['organisasi_ids'],
                properties: [
                    new OA\Property(property: 'organisasi_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Undangan berhasil dikirim', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/organisasi/event/{id}/undangan/{id_ajakan}/batalkan',
        operationId: 'batalkanUndangan',
        summary: 'Batalkan undangan event',
        description: 'Membatalkan undangan event yang belum dijawab.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id_ajakan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Undangan berhasil dibatalkan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Undangan tidak ditemukan'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/organisasi/event/{id}/ajakan',
        operationId: 'ajakanEvent',
        summary: 'Rekapitulasi ajakan/undangan event',
        description: 'Mengambil rekapitulasi status ajakan/undangan yang dikirimkan oleh pembuat event.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'jenis', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar ajakan event berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/organisasi/ajakan-masuk',
        operationId: 'indexAjakanMasuk',
        summary: 'Daftar ajakan/undangan masuk',
        description: 'Mengambil daftar ajakan dan undangan yang diterima oleh organisasi pengguna.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'jenis', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar ajakan masuk berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
    #[OA\Get(
        path: '/api/organisasi/ajakan-masuk/{id_ajakan}',
        operationId: 'showAjakanMasuk',
        summary: 'Detail ajakan/undangan masuk',
        description: 'Melihat detail popup ajakan atau undangan masuk.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id_ajakan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail ajakan berhasil diambil', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Ajakan tidak ditemukan'),
        ]
    )]
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
    #[OA\Post(
        path: '/api/organisasi/ajakan-masuk/{id_ajakan}/jawab',
        operationId: 'jawabAjakan',
        summary: 'Jawab ajakan/undangan masuk',
        description: 'Menjawab ajakan atau undangan masuk dengan keputusan terima atau tolak.',
        tags: ['Ajakan Kolaborasi & Undangan Event'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id_ajakan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['keputusan'],
                properties: [
                    new OA\Property(property: 'keputusan', type: 'string', enum: ['terima', 'tolak']),
                    new OA\Property(property: 'alasan_penolakan', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ajakan berhasil dijawab', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Ajakan tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
