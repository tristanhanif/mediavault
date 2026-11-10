<?php

namespace App\Services;

use App\Models\Medium;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MediaService
{
    public function __construct(private readonly CloudinaryService $cloudinary)
    {
    }

    /**
     * @param  UploadedFile[]  $files
     * @return array{success: list<string>, failed: list<string>}
     */
    public function upload(array $files, int $userId, ?int $folderId, ?int $categoryId): array
    {
        $success = [];
        $failed = [];

        foreach ($files as $file) {
            try {
                $media = $this->uploadSingle($file, $userId, $folderId, $categoryId);
                $success[] = $media->name;
            } catch (\Throwable $e) {
                $failed[] = $file->getClientOriginalName().' : '.$e->getMessage();
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    protected function uploadSingle(
        UploadedFile $file,
        int $userId,
        ?int $folderId,
        ?int $categoryId,
    ): Medium {
        if ($file->getSize() > Medium::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Ukuran file melebihi 20 MB.');
        }

        $originalName = $file->getClientOriginalName();
        $name = $this->resolveUniqueName($userId, $folderId, $originalName);

        $result = $this->cloudinary->upload($file, $userId);

        try {
            $media = DB::transaction(function () use ($result, $userId, $folderId, $categoryId, $file, $name) {
                return Medium::create([
                    'user_id' => $userId,
                    'folder_id' => $folderId,
                    'category_id' => $categoryId,
                    'name' => $name,
                    'public_id' => $result['public_id'],
                    'resource_type' => $result['resource_type'],
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'extension' => strtolower($file->getClientOriginalExtension()),
                    'size_bytes' => $result['bytes'] ?: $file->getSize(),
                ]);
            });
        } catch (\Throwable $dbError) {
            // Rollback: file di Cloudinary dihapus karena metadata gagal tersimpan.
            try {
                $this->cloudinary->delete($result['public_id'], $result['resource_type']);
            } catch (\Throwable) {
                // Abaikan kegagalan rollback storage; log tetap dilaporkan ke user.
            }

            throw new InvalidArgumentException('Gagal menyimpan metadata file.', 0, $dbError);
        }

        return $media;
    }

    /**
     * Nama inkremental bila bentrok: keuangan.pdf → keuangan (1).pdf → keuangan (2).pdf
     */
    public function resolveUniqueName(int $userId, ?int $folderId, string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);

        $exists = fn (string $candidate): bool => Medium::where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('name', $candidate)
            ->withTrashed()
            ->exists();

        $candidate = $originalName;

        if (! $exists($candidate)) {
            return $candidate;
        }

        $index = 1;

        do {
            $candidate = $extension !== ''
                ? "{$baseName} ({$index}).{$extension}"
                : "{$baseName} ({$index})";

            $index++;
        } while ($exists($candidate));

        return $candidate;
    }

    public function delete(Medium $media, int $userId): void
    {
        $media->delete();

        app(ActivityLogService::class)->log(
            $userId,
            \App\Models\ActivityLog::ACTION_DELETE,
            "Menghapus file \"{$media->name}\" (masuk recycle bin)",
            Medium::class,
            $media->id,
        );
    }

    public function bulkDelete(array $mediaIds, int $userId): int
    {
        $count = 0;

        Medium::where('user_id', $userId)
            ->whereIn('id', $mediaIds)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (Medium $media) use (&$count, $userId) {
                $this->delete($media, $userId);
                $count++;
            });

        return $count;
    }

    public function restore(int $mediaId, int $userId): Medium
    {
        $media = Medium::withTrashed()->where('user_id', $userId)->findOrFail($mediaId);
        $media->restore();

        app(ActivityLogService::class)->log(
            $userId,
            \App\Models\ActivityLog::ACTION_RESTORE,
            "Mengembalikan file \"{$media->name}\" dari recycle bin",
            Medium::class,
            $media->id,
        );

        return $media;
    }

    public function forceDelete(Medium $media, int $userId): void
    {
        try {
            $this->cloudinary->delete($media->public_id, $media->resource_type);
        } catch (\Throwable) {
            // Tetap lanjut menghapus metadata agar recycle bin konsisten.
        }

        app(ActivityLogService::class)->log(
            $userId,
            \App\Models\ActivityLog::ACTION_FORCE_DELETE,
            "Menghapus permanen file \"{$media->name}\"",
            Medium::class,
            $media->id,
        );

        $media->forceDelete();
    }

    public function move(Medium $media, ?int $targetFolderId, int $userId): void
    {
        $media->update(['folder_id' => $targetFolderId]);

        app(ActivityLogService::class)->log(
            $userId,
            \App\Models\ActivityLog::ACTION_MOVE,
            "Memindahkan file \"{$media->name}\"",
            Medium::class,
            $media->id,
        );
    }
}
