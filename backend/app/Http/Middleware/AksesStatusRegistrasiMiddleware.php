<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AksesStatusRegistrasiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (in_array($pengguna->status_akun, ['menunggu', 'ditolak', 'aktif'], true)) {
            return $next($request);
        }

        $pesan = match ($pengguna->status_akun) {
            'ditangguhkan' => 'Akun ditangguhkan',
            default        => 'Akun dinonaktifkan',
        };

        return response()->json([
            'success' => false,
            'message' => $pesan,
        ], 403);
    }
}
