<?php

namespace App\Http\Controllers;

use App\Models\Universitas;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UniversitasController extends Controller
{
    #[OA\Get(
        path: '/api/universitas',
        summary: 'Daftar universitas publik',
        description: 'Mendapatkan daftar seluruh universitas dengan status aktif.',
        tags: ['Master Data & Publik'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar universitas berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Daftar universitas berhasil diambil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'universitas',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id_universitas', type: 'integer', example: 1),
                                            new OA\Property(property: 'nama_universitas', type: 'string', example: 'Universitas Indonesia'),
                                            new OA\Property(property: 'logo', type: 'string', nullable: true, example: 'logos/ui.png')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
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
