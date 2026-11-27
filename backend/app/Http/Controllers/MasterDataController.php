<?php

namespace App\Http\Controllers;

use App\Models\BidangOrganisasi;
use App\Models\Universitas;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class MasterDataController extends Controller
{
    /**
     * Mendapatkan daftar universitas untuk dropdown/pilihan.
     */
    #[OA\Get(
        path: '/api/master/universitas',
        summary: 'Mendapatkan daftar universitas',
        description: 'Mendapatkan daftar seluruh universitas terdaftar untuk keperluan dropdown/pilihan.',
        tags: ['Master Data & Publik'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar universitas berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id_universitas', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama_universitas', type: 'string', example: 'Universitas Indonesia'),
                                    new OA\Property(property: 'logo', type: 'string', nullable: true, example: 'storage/logos/ui.png')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function universitas(): JsonResponse
    {
        $universitas = Universitas::select('id_universitas', 'nama_universitas', 'logo')
            ->orderBy('nama_universitas', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $universitas,
        ]);
    }

    /**
     * Mendapatkan daftar bidang organisasi untuk dropdown/pilihan.
     */
    #[OA\Get(
        path: '/api/master/bidang-organisasi',
        summary: 'Mendapatkan daftar bidang organisasi',
        description: 'Mendapatkan master data bidang organisasi.',
        tags: ['Master Data & Publik'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar bidang organisasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id_bidang', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama_bidang', type: 'string', example: 'Teknologi & Penalaran')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function bidangOrganisasi(): JsonResponse
    {
        $bidang = BidangOrganisasi::select('id_bidang', 'nama_bidang')
            ->orderBy('id_bidang', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $bidang,
        ]);
    }

    /**
     * Mendapatkan daftar kategori event untuk dropdown/pilihan form event.
     */
    #[OA\Get(
        path: '/api/master/kategori-event',
        summary: 'Mendapatkan daftar kategori event',
        description: 'Mendapatkan master kategori event untuk form pembuatan atau filter event.',
        tags: ['Master Data & Publik'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar kategori event berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama_kategori', type: 'string', example: 'Seminar')
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function kategoriEvent(): JsonResponse
    {
        $kategori = \App\Models\KategoriEvent::select('id', 'nama_kategori')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $kategori,
        ]);
    }
}
