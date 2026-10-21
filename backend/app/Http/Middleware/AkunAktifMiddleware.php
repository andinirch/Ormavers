<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AkunAktifMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna) {
            if (! $pengguna->tokenCan('*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login ulang',
                ], 403);
            }

            if ($pengguna->status_akun !== 'aktif') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun dinonaktifkan',
                ], 403);
            }
        }

        return $next($request);
    }
}
