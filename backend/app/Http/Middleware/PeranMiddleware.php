<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeranMiddleware
{
    public function handle(Request $request, Closure $next, string ...$peranDiizinkan): Response
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! in_array($pengguna->peran, $peranDiizinkan, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak',
            ], 403);
        }

        return $next($request);
    }
}
