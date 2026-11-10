<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Medium;
use Illuminate\Support\Facades\DB;

class FolderService
{
    public function __construct(private readonly CloudinaryService $cloudinary)
    {
    }

    public function create(int $userId, ?int $parentId, string $name): Folder
    {
        $depth = 1;

        if ($parentId !== null) {
            $parent = Folder::where('user_id', $userId)->findOrFail($parentId);
            $depth = $parent->depth + 1;

            if ($depth > Folder::MAX_DEPTH) {
                throw new \InvalidArgumentException(
                    'Subfolder maksimal '.Folder::MAX_DEPTH.' level dari direktori utama.'
                );
            }
        }

        $exists = Folder::where('user_id', $userId)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException("Folder dengan nama \"{$name}\" sudah ada di lokasi ini.");
        }

        return Folder::create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'name' => $name,
            'depth' => $depth,
        ]);
    }

    public function rename(Folder $folder, string $name): Folder
    {
        $exists = Folder::where('user_id', $folder->user_id)
            ->where('parent_id', $folder->parent_id)
            ->where('name', $name)
            ->where('id', '!=', $folder->id)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException("Folder dengan nama \"{$name}\" sudah ada di lokasi ini.");
        }

        $folder->update(['name' => $name]);

        return $folder;
    }

    /**
     * Pindahkan folder ke folder target lain, termasuk seluruh isinya.
     */
    public function move(Folder $folder, ?int $targetParentId): Folder
    {
        if ($targetParentId !== null) {
            if ($targetParentId === $folder->id) {
                throw new \InvalidArgumentException('Tidak bisa memindahkan folder ke dirinya sendiri.');
            }

            $target = Folder::where('user_id', $folder->user_id)->findOrFail($targetParentId);

            $ancestorIds = $this->ancestorIds($target);

            if (in_array($folder->id, $ancestorIds, true)) {
                throw new \InvalidArgumentException('Tidak bisa memindahkan folder ke dalam subfoldernya sendiri.');
            }

            $newDepth = $target->depth + 1;
            $maxSubtreeDepth = $this->maxSubtreeDepth($folder);

            if ($newDepth - 1 + $maxSubtreeDepth > Folder::MAX_DEPTH) {
                throw new \InvalidArgumentException(
                    'Pemindahan melebihi batas maksimal '.Folder::MAX_DEPTH.' level subfolder.'
                );
            }
        }

        return DB::transaction(function () use ($folder, $targetParentId) {
            $folder->update(['parent_id' => $targetParentId]);
            $this->recalculateDepth($folder->fresh());

            return $folder;
        });
    }

    /**
     * Soft delete folder beserta seluruh isinya (subfolder & media).
     * Media di dalamnya ikut masuk recycle bin.
     */
    public function delete(Folder $folder): void
    {
        DB::transaction(function () use ($folder) {
            $now = now();

            $folderIds = $this->descendantIds($folder);
            $folder->forceFill(['deleted_at' => $now])->saveQuietly();

            Folder::whereIn('parent_id', $folderIds)
                ->whereNull('deleted_at')
                ->get()
                ->each(function (Folder $child) use ($now) {
                    $this->deleteQuietly($child, $now);
                });

            Medium::whereIn('folder_id', $folderIds)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $now]);
        });
    }

    /**
     * Restore folder + rantai leluhur (bila ikut terhapus bersamaan) + media di dalamnya.
     */
    public function restore(int $folderId, int $userId): Folder
    {
        return DB::transaction(function () use ($folderId, $userId) {
            $folder = Folder::withTrashed()
                ->where('user_id', $userId)
                ->whereKey($folderId)
                ->firstOrFail();

            $deletedAt = $folder->deleted_at;

            $current = $folder;
            while ($current->parent_id !== null && $current->parent_id !== $folder->id) {
                $parent = Folder::withTrashed()->find($current->parent_id);

                if (! $parent) {
                    break;
                }

                if ($parent->deleted_at !== null && $parent->deleted_at->equalTo($deletedAt)) {
                    $parent->forceFill(['deleted_at' => null])->saveQuietly();
                }

                $current = $parent;
            }

            $folder->forceFill(['deleted_at' => null])->saveQuietly();
            $this->recalculateDepth($folder->fresh());

            $folderIds = $this->descendantIds($folder->fresh());

            Medium::withTrashed()
                ->whereIn('folder_id', $folderIds)
                ->where('deleted_at', $deletedAt)
                ->update(['deleted_at' => null]);

            return $folder->fresh();
        });
    }

    /**
     * Hapus permanen folder beserta isinya, termasuk file di Cloudinary.
     */
    public function forceDelete(Folder $folder, int $userId): void
    {
        DB::transaction(function () use ($folder, $userId) {
            $folderIds = $this->descendantIds($folder);

            $media = Medium::withTrashed()->whereIn('folder_id', $folderIds)->get();

            foreach ($media as $item) {
                try {
                    $this->cloudinary->delete($item->public_id, $item->resource_type);
                } catch (\Throwable) {
                    // Lanjutkan meski Cloudinary gagal; jangan blokir pembersihan DB.
                }
            }

            Medium::withTrashed()->whereIn('folder_id', $folderIds)->forceDelete();

            $this->deleteFoldersPermanently($folderIds, $userId);
        });
    }

    protected function deleteFoldersPermanently(array $folderIds, int $userId): void
    {
        $toDelete = $folderIds;
        $batch = $folderIds;

        while ($batch !== []) {
            $batch = Folder::withTrashed()
                ->where('user_id', $userId)
                ->whereIn('parent_id', $batch)
                ->pluck('id')
                ->all();

            $toDelete = array_merge($toDelete, $batch);
        }

        Folder::withTrashed()->whereIn('id', $toDelete)->forceDelete();
    }

    protected function deleteQuietly(Folder $folder, $timestamp): void
    {
        $folder->forceFill(['deleted_at' => $timestamp])->saveQuietly();

        $children = Folder::where('parent_id', $folder->id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($children as $child) {
            $this->deleteQuietly($child, $timestamp);
        }
    }

    protected function descendantIds(Folder $folder): array
    {
        $ids = [$folder->id];
        $batch = [$folder->id];

        while ($batch !== []) {
            $batch = Folder::whereIn('parent_id', $batch)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $batch);
        }

        return $ids;
    }

    protected function ancestorIds(Folder $folder): array
    {
        $ids = [];
        $current = $folder;

        while ($current->parent_id !== null) {
            $ids[] = $current->parent_id;
            $current = $current->parent()->withTrashed()->firstOrFail();
        }

        return $ids;
    }

    protected function maxSubtreeDepth(Folder $folder): int
    {
        $max = 1;
        $children = Folder::where('parent_id', $folder->id)->get();

        foreach ($children as $child) {
            $max = max($max, 1 + $this->maxSubtreeDepth($child));
        }

        return $max;
    }

    protected function recalculateDepth(Folder $folder): void
    {
        if ($folder->parent_id === null) {
            $folder->update(['depth' => 1]);
        } else {
            $parent = Folder::find($folder->parent_id);
            $folder->update(['depth' => $parent ? $parent->depth + 1 : 1]);
        }

        Folder::where('parent_id', $folder->id)->each(function (Folder $child) {
            $this->recalculateDepth($child);
        });
    }
}
