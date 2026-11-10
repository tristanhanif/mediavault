<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\MediaShare;
use App\Models\Medium;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ShareController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'media_id' => ['required', 'integer', 'exists:media,id'],
        ]);

        $media = Medium::where('user_id', Auth::id())->findOrFail($validated['media_id']);

        $share = $media->shares()
            ->whereNull('revoked_at')
            ->first();

        if (! $share) {
            $share = MediaShare::create([
                'media_id' => $media->id,
                'owner_id' => Auth::id(),
                'token' => Str::random(48),
            ]);

            $this->activityLog->log(
                Auth::id(),
                ActivityLog::ACTION_SHARE_CREATE,
                "Membuat link berbagi untuk \"{$media->name}\"",
                MediaShare::class,
                $share->id,
            );
        }

        return back()->with('share_url', route('shared.media', ['token' => $share->token]))
            ->with('success', 'Link berbagi privat berhasil dibuat.');
    }

    public function revoke(Request $request, MediaShare $share): RedirectResponse
    {
        abort_unless($share->owner_id === Auth::id(), 403);

        $share->update(['revoked_at' => now()]);

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_SHARE_REVOKE,
            "Mencabut akses berbagi untuk \"{$share->media->name}\"",
            MediaShare::class,
            $share->id,
        );

        return back()->with('success', 'Akses berbagi berhasil dicabut.');
    }

    /**
     * Halaman berbagi: wajib login, token aktif, dan bukan pemilik file.
     */
    public function show(Request $request, string $token)
    {
        $share = MediaShare::with(['media.user', 'media.category'])
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->firstOrFail();

        abort_if($share->owner_id === Auth::id(), 403, 'File ini milik Anda.');

        return view('media.share-view', ['media' => $share->media, 'share' => $share]);
    }
}
