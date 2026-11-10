<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::query()
            ->with(['user'])
            ->when(
                $request->query('action'),
                fn ($q, $action) => $q->where('action', $action),
            )
            ->when(
                $request->query('user_id'),
                fn ($q, $userId) => $q->where('user_id', $userId),
            )
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $users = ActivityLog::query()
            ->join('users', 'users.id', '=', 'activity_logs.user_id')
            ->distinct()
            ->orderBy('users.name')
            ->pluck('users.name', 'users.id');

        return view('admin.activity-logs.index', compact('logs', 'users'));
    }
}
