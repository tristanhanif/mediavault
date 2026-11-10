<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\JWT;

class AuthController extends Controller
{
    public function __construct(
        private readonly JWT $jwt,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_USER,
            'is_active' => true,
        ]);

        $this->activityLog->log(
            $user->id,
            ActivityLog::ACTION_REGISTER,
            "Registrasi akun via API: {$user->email}",
        );

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => $this->userPayload($user),
            ...$this->issueTokens($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun dinonaktifkan. Hubungi administrator.'], 403);
        }

        $this->activityLog->log(
            $user->id,
            ActivityLog::ACTION_LOGIN,
            "Login via API: {$user->email}",
        );

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => $this->userPayload($user),
            ...$this->issueTokens($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $jwt = $this->jwt->setRequest($request)->parseToken();
        $payload = $jwt->payload();

        if ($payload->get('type') !== 'refresh') {
            return response()->json([
                'message' => 'Token bukan refresh token. Gunakan access token untuk akses resource.',
            ], 401);
        }

        $user = User::find($payload->get('sub'));

        if (! $user) {
            return response()->json(['message' => 'Pengguna tidak ditemukan.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun dinonaktifkan. Hubungi administrator.'], 403);
        }

        // Rotasi: refresh token lama masuk blacklist.
        $jwt->invalidate();

        return response()->json([
            'message' => 'Token berhasil diperbarui.',
            'user' => $this->userPayload($user),
            ...$this->issueTokens($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->jwt->setRequest($request)->parseToken()->invalidate();
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
            // Token tidak ada / sudah kedaluwarsa: anggap sudah logout.
        }

        return response()->json(['message' => 'Logout berhasil. Access token telah dicabut.']);
    }

    /**
     * Terbitkan pasangan access token (TTL pendek) + refresh token (TTL panjang).
     */
    protected function issueTokens(User $user): array
    {
        $accessTtl = (int) config('jwt.ttl', 60);
        $refreshTtl = (int) config('jwt.refresh_ttl', 10080);

        $this->jwt->factory()->setTTL($accessTtl);
        $accessToken = $this->jwt->claims(['type' => 'access'])->fromUser($user);

        $this->jwt->factory()->setTTL($refreshTtl);
        $refreshToken = $this->jwt->claims(['type' => 'refresh'])->fromUser($user);

        // Bersihkan custom claims agar tidak bocor ke token berikutnya.
        $this->jwt->claims([]);

        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'access_token_expires_in' => $accessTtl * 60,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_in' => $refreshTtl * 60,
        ];
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }
}
