<?php

namespace Tests\Feature;

use App\Models\Medium;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user_permanently(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create();

        $media = Medium::create([
            'user_id' => $target->id,
            'folder_id' => null,
            'name' => 'file-user.pdf',
            'public_id' => 'mediavault/users/'.$target->id.'/1_abc_file',
            'resource_type' => 'raw',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
        ]);

        $mock = $this->mock(\App\Services\CloudinaryService::class);
        $mock->shouldReceive('delete')->once()->with('mediavault/users/'.$target->id.'/1_abc_file', 'raw')->andReturn(true);

        $response = $this->actingAs($admin)->delete("/admin/users/{$target->id}");

        $response->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $other = User::factory()->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->delete("/admin/users/{$other->id}")->assertForbidden();
    }
}
