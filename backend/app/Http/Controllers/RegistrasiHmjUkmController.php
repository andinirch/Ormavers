<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrasiHmjUkmRequest;
use App\Services\RegistrasiHmjUkmService;
use Illuminate\Http\JsonResponse;

class RegistrasiHmjUkmController extends Controller
{
    public function __construct(
        protected RegistrasiHmjUkmService $registrasiHmjUkmService,
    ) {}

    public function register(RegistrasiHmjUkmRequest $request): JsonResponse
    {
        $data = $request->validated();

        $files = [
            'foto_ktm'        => $request->file('foto_ktm'),
            'sk_organisasi'   => $request->file('sk_organisasi'),
            'sk_kepengurusan' => $request->file('sk_kepengurusan'),
        ];

        $pendaftaran = $this->registrasiHmjUkmService->register($data, $files);

        $jenisLabel = strtoupper($pendaftaran['jenis_organisasi']);

        return response()->json([
            'success' => true,
            'message' => "Registrasi {$jenisLabel} berhasil dikirim",
            'data'    => [
                'pendaftaran' => $pendaftaran,
            ],
        ], 201);
    }
}
