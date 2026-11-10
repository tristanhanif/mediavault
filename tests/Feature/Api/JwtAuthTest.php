<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_access_and_refresh_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token_type',
                'access_token',
                'access_token_expires_in',
                'refresh_token',
                'refresh_token_expires_in',
            ]);

        $this->assertNotSame(
            $response->json('access_token'),
            $response->json('refresh_token'),
        );
    }

    public function test_access_token_grants_me_endpoint(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tokens = $this->login($user);

        $this->withToken($tokens['access_token'])
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_refresh_token_is_rejected_on_resource_endpoints(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tokens = $this->login($user);

        $this->withToken($tokens['refresh_token'])
            ->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Access token tidak valid. Gunakan access token, bukan refresh token.');
    }

    public function test_refresh_endpoint_rotates_tokens_and_blacklists_old_refresh_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tokens = $this->login($user);

        $refreshed = $this->withToken($tokens['refresh_token'])
            ->postJson('/api/auth/refresh');

        $refreshed->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token']);

        $this->assertNotSame($tokens['access_token'], $refreshed->json('access_token'));
        $this->assertNotSame($tokens['refresh_token'], $refreshed->json('refresh_token'));

        // Refresh token lama sudah masuk blacklist.
        $this->withToken($tokens['refresh_token'])
            ->postJson('/api/auth/refresh')
            ->assertStatus(401);
    }

    public function test_access_token_cannot_be_used_on_refresh_endpoint(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tokens = $this->login($user);

        $this->withToken($tokens['access_token'])
            ->postJson('/api/auth/refresh')
            ->assertStatus(401);
    }

    public function test_logout_blacklists_access_token(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tokens = $this->login($user);

        $this->withToken($tokens['access_token'])
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->withToken($tokens['access_token'])
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_register_via_api_returns_tokens(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'api@example.com')
            ->assertJsonStructure(['access_token', 'refresh_token']);

        $this->assertDatabaseHas('users', ['email' => 'api@example.com', 'role' => 'user']);
    }

    public function test_inactive_user_cannot_login_via_api(): void
    {
        $user = User::factory()->create(['password' => 'password123', 'is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertStatus(403);
    }

    protected function login(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertTrue($response->isOk(), 'Login harus sukses dalam helper test.');

        return [
            'access_token' => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
        ];
    }
}
