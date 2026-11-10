<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medium extends Model
{
    use SoftDeletes;

    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_DOCUMENT = 'document';

    public const MAX_FILE_SIZE = 20 * 1024 * 1024;

    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'folder_id',
        'category_id',
        'name',
        'public_id',
        'resource_type',
        'mime_type',
        'extension',
        'size_bytes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'media_tag', 'media_id', 'tag_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(MediaShare::class, 'media_id');
    }

    public function activeShare(): HasMany
    {
        return $this->shares()->whereNull('revoked_at');
    }

    public function getTypeAttribute(): string
    {
        return match ($this->resource_type) {
            self::TYPE_IMAGE => self::TYPE_IMAGE,
            self::TYPE_VIDEO => self::TYPE_VIDEO,
            default => self::TYPE_DOCUMENT,
        };
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 2).' '.$units[$i];
    }

    public function isImage(): bool
    {
        return $this->resource_type === self::TYPE_IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->resource_type === self::TYPE_VIDEO;
    }
}
