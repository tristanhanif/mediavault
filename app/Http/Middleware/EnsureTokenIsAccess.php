<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $type = auth('api')->payload()?->get('type');
        } catch (JWTException) {
            return response()->json([
                'message' => 'Token tidak valid atau telah dicabut.',
            ], 401);
        }

        if ($type !== 'access') {
            return response()->json([
                'message' => 'Access token tidak valid. Gunakan access token, bukan refresh token.',
            ], 401);
        }

        return $next($request);
    }
}
