<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\User;
use App\Services\FolderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_folder_at_root(): void
    {
        $user = User::factory()->create();
        $service = app(FolderService::class);

        $folder = $service->create($user->id, null, 'Dokumen Keuangan');

        $this->assertSame('Dokumen Keuangan', $folder->name);
        $this->assertSame(1, $folder->depth);
        $this->assertNull($folder->parent_id);
    }

    public function test_cannot_exceed_three_levels_of_subfolders(): void
    {
        $user = User::factory()->create();
        $service = app(FolderService::class);

        $level1 = $service->create($user->id, null, 'Level 1');
        $level2 = $service->create($user->id, $level1->id, 'Level 2');
        $level3 = $service->create($user->id, $level2->id, 'Level 3');

        $this->assertSame(3, $level3->depth);

        $this->expectException(\InvalidArgumentException::class);
        $service->create($user->id, $level3->id, 'Level 4');
    }

    public function test_duplicate_folder_name_in_same_location_is_rejected(): void
    {
        $user = User::factory()->create();
        $service = app(FolderService::class);

        $service->create($user->id, null, 'Laporan');

        $this->expectException(\InvalidArgumentException::class);
        $service->create($user->id, null, 'Laporan');
    }

    public function test_cannot_move_folder_into_its_own_descendant(): void
    {
        $user = User::factory()->create();
        $service = app(FolderService::class);

        $parent = $service->create($user->id, null, 'Parent');
        $child = $service->create($user->id, $parent->id, 'Child');

        $this->expectException(\InvalidArgumentException::class);
        $service->move($parent, $child->id);
    }

    public function test_soft_delete_folder_cascades_to_media(): void
    {
        $user = User::factory()->create();
        $service = app(FolderService::class);
        $folder = $service->create($user->id, null, 'Arsip');

        $media = \App\Models\Medium::create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'name' => 'laporan.pdf',
            'public_id' => 'mediavault/users/'.$user->id.'/1_abc_laporan',
            'resource_type' => 'raw',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
        ]);

        $service->delete($folder);

        $this->assertSoftDeleted($folder);
        $this->assertSoftDeleted($media);

        $service->restore($folder->id, $user->id);

        $this->assertNotSoftDeleted($folder->fresh());
        $this->assertNotSoftDeleted($media->fresh());
    }
}
