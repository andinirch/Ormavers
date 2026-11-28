<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

/**
 * Virtual documentation for admin event moderation routes 
 * (served by JelajahEventController but on /api/admin/events prefix)
 */
#[OA\Get(
    path: '/api/admin/events',
    operationId: 'adminModerasiEventIndex',
    summary: 'Moderasi daftar event (Administrator)',
    description: 'Administrator melihat seluruh event dari semua organisasi untuk keperluan moderasi.',
    tags: ['Jelajah & Share Event'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'kategori', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Daftar event berhasil diambil', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
        ])),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/admin/events/{id}',
    operationId: 'adminModerasiEventShow',
    summary: 'Detail event moderasi (Administrator)',
    description: 'Administrator melihat detail lengkap event tertentu untuk moderasi.',
    tags: ['Jelajah & Share Event'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Detail event berhasil diambil', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'success', type: 'boolean', example: true),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'data', type: 'object'),
        ])),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Event tidak ditemukan'),
    ]
)]
class AdminEventSwagger
{
}
