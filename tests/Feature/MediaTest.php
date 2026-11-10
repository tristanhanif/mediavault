<?php

namespace Tests\Feature;

use App\Models\Medium;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_incremental_name_on_duplicate_file_name(): void
    {
        $user = User::factory()->create();
        $service = app(MediaService::class);

        $this->createMedia($user->id, 'keuangan.pdf');

        $first = $service->resolveUniqueName($user->id, null, 'keuangan.pdf');
        $this->assertSame('keuangan (1).pdf', $first);

        $this->createMedia($user->id, $first);

        $second = $service->resolveUniqueName($user->id, null, 'keuangan.pdf');
        $this->assertSame('keuangan (2).pdf', $second);
    }

    public function test_same_name_allowed_in_different_folders(): void
    {
        $user = User::factory()->create();
        $service = app(MediaService::class);

        $folder = \App\Models\Folder::create([
            'user_id' => $user->id,
            'parent_id' => null,
            'name' => 'Laporan',
            'depth' => 1,
        ]);

        $this->createMedia($user->id, 'data.xlsx', null);

        $resolved = $service->resolveUniqueName($user->id, $folder->id, 'data.xlsx');
        $this->assertSame('data.xlsx', $resolved);
    }

    public function test_upload_rolls_back_cloudinary_when_db_fails(): void
    {
        $user = User::factory()->create();

        $mock = $this->createStub(\App\Services\CloudinaryService::class);
        $mock->method('upload')->willReturn([
            'public_id' => 'mediavault/users/'.$user->id.'/1_abc_test',
            'resource_type' => 'image',
            'bytes' => 100,
            'format' => 'png',
        ]);

        $deleted = false;
        $mock->method('delete')->willReturnCallback(function () use (&$deleted) {
            $deleted = true;

            return true;
        });

        $service = new MediaService($mock);

        $file = \Illuminate\Http\UploadedFile::fake()->create('tes.png', 100);

        \Illuminate\Support\Facades\DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \RuntimeException('DB gagal'));

        try {
            $service->upload([$file], $user->id, null, null);
            $this->fail('Upload harus gagal saat DB bermasalah.');
        } catch (\RuntimeException $e) {
            $this->assertTrue($deleted, 'Rollback Cloudinary harus dipanggil saat DB gagal');
        }
    }

    public function test_guest_cannot_access_media_pages(): void
    {
        $this->get('/media')->assertRedirect(route('login'));
    }

    public function test_upload_rejects_disallowed_file_formats(): void
    {
        $user = User::factory()->create();

        $file = \Illuminate\Http\UploadedFile::fake()->create('berkas-berbahaya.php', 100);

        $response = $this->actingAs($user)->post('/media', ['files' => [$file]]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('media', ['name' => 'berkas-berbahaya.php']);
    }

    public function test_upload_rejects_disallowed_document_extensions(): void
    {
        $user = User::factory()->create();

        $file = \Illuminate\Http\UploadedFile::fake()->create('data.xls', 100);

        $this->actingAs($user)->post('/media', ['files' => [$file]])->assertSessionHas('error');
        $this->assertDatabaseMissing('media', ['name' => 'data.xls']);
    }

    public function test_upload_accepts_allowed_image_format(): void
    {
        $user = User::factory()->create();

        $mock = $this->mock(\App\Services\CloudinaryService::class);
        $mock->shouldReceive('upload')->once()->andReturn([
            'public_id' => 'mediavault/users/'.$user->id.'/1_abc_gambar',
            'resource_type' => 'image',
            'bytes' => 95,
            'format' => 'png',
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('gambar.png', $png);

        $response = $this->actingAs($user)->post('/media', ['files' => [$file]]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('media', ['user_id' => $user->id, 'name' => 'gambar.png']);
    }

    public function test_upload_accepts_pdf_document(): void
    {
        $user = User::factory()->create();

        $mock = $this->mock(\App\Services\CloudinaryService::class);
        $mock->shouldReceive('upload')->once()->andReturn([
            'public_id' => 'mediavault/users/'.$user->id.'/1_abc_dokumen',
            'resource_type' => 'raw',
            'bytes' => 300,
            'format' => 'pdf',
        ]);

        $pdf = "%PDF-1.4\n1 0 obj\nendobj\ntrailer\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('laporan.pdf', $pdf);

        $response = $this->actingAs($user)->post('/media', ['files' => [$file]]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('media', ['user_id' => $user->id, 'name' => 'laporan.pdf']);
    }

    public function test_user_cannot_see_other_users_media_details(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $media = Medium::create([
            'user_id' => $owner->id,
            'folder_id' => null,
            'name' => 'rahasia.pdf',
            'public_id' => 'mediavault/users/'.$owner->id.'/1_abc_rahasia',
            'resource_type' => 'raw',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 2048,
        ]);

        $this->actingAs($other)->get("/media/{$media->id}")->assertForbidden();
    }

    protected function createMedia(int $userId, string $name, ?int $folderId = null): Medium
    {
        return Medium::create([
            'user_id' => $userId,
            'folder_id' => $folderId,
            'name' => $name,
            'public_id' => 'mediavault/users/'.$userId.'/1_abc_'.uniqid(),
            'resource_type' => 'raw',
            'mime_type' => 'application/octet-stream',
            'extension' => pathinfo($name, PATHINFO_EXTENSION),
            'size_bytes' => 1024,
        ]);
    }
}
