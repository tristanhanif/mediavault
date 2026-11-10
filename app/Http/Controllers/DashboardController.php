<?php

namespace App\Http\Controllers;

use App\Models\Medium;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $stats = Medium::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COUNT(CASE WHEN resource_type = 'image' THEN 1 END) as images")
            ->selectRaw("COUNT(CASE WHEN resource_type = 'video' THEN 1 END) as videos")
            ->selectRaw("COUNT(CASE WHEN resource_type = 'raw' THEN 1 END) as documents")
            ->selectRaw('COALESCE(SUM(size_bytes), 0) as storage_used')
            ->first();

        $recentUploads = Medium::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $storageQuota = 2 * 1024 * 1024 * 1024; // 2 GB indikator lokal

        return view('dashboard', [
            'stats' => $stats,
            'recentUploads' => $recentUploads,
            'storageQuota' => $storageQuota,
            'storagePercent' => min(100, round($stats->storage_used / $storageQuota * 100, 1)),
        ]);
    }
}
