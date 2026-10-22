<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectMemberRequest;
use App\Services\OrgMemberRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrgMemberRequestController extends Controller
{
    public function __construct(
        protected OrgMemberRequestService $memberRequestService,
    ) {}

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

    public function reject(int $id, RejectMemberRequest $request): JsonResponse
    {
        $result = $this->memberRequestService->reject(
            $id,
            $request->validated()['alasan'],
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
}
