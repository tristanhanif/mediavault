<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const ACTION_UPLOAD = 'upload';
    public const ACTION_DELETE = 'delete';
    public const ACTION_RESTORE = 'restore';
    public const ACTION_FORCE_DELETE = 'force_delete';
    public const ACTION_RENAME = 'rename';
    public const ACTION_MOVE = 'move';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_FOLDER_CREATE = 'folder_create';
    public const ACTION_FOLDER_DELETE = 'folder_delete';
    public const ACTION_SHARE_CREATE = 'share_create';
    public const ACTION_SHARE_REVOKE = 'share_revoke';
    public const ACTION_LOGIN = 'login';
    public const ACTION_REGISTER = 'register';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
