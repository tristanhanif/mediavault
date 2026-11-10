<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsRefresh
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $payload = auth('api')->payload();
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
            return response()->json(['message' => 'Refresh token tidak ditemukan atau tidak valid.'], 401);
        }

        if ($payload?->get('type') !== 'refresh') {
            return response()->json([
                'message' => 'Endpoint ini hanya menerima refresh token. Gunakan access token untuk akses resource.',
            ], 401);
        }

        return $next($request);
    }
}
