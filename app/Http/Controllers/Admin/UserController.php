<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medium;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly CloudinaryService $cloudinary,
    ) {
    }

    public function index(Request $request)
    {
        $users = User::query()
            ->withCount(['media' => fn ($q) => $q->whereNull('deleted_at')])
            ->when(
                $request->query('q'),
                fn ($q, $search) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(Request $request, User $user)
    {
        $stats = Medium::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COUNT(CASE WHEN resource_type = 'image' THEN 1 END) as images")
            ->selectRaw("COUNT(CASE WHEN resource_type = 'video' THEN 1 END) as videos")
            ->selectRaw("COUNT(CASE WHEN resource_type = 'raw' THEN 1 END) as documents")
            ->selectRaw('COALESCE(SUM(size_bytes), 0) as storage_used')
            ->first();

        $media = Medium::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->with(['category', 'folder'])
            ->when(
                $request->query('q'),
                fn ($q, $search) => $q->where('name', 'like', "%{$search}%"),
            )
            ->when(
                $request->query('type'),
                fn ($q, $type) => $q->where('resource_type', $type),
            )
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.show', compact('user', 'stats', 'media'));
    }

    public function changeRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Tidak bisa mengubah role sendiri.');

        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ]);

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        $this->activityLog->log(
            $request->user()->id,
            'role_change',
            "Mengubah role {$user->email} dari {$oldRole} menjadi {$validated['role']}",
            User::class,
            $user->id,
        );

        return back()->with('success', "Role {$user->name} diubah menjadi {$validated['role']}.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Tidak bisa menonaktifkan akun sendiri.');

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->activityLog->log(
            $request->user()->id,
            'account_toggle',
            "Akun {$user->email} {$status}",
            User::class,
            $user->id,
        );

        return back()->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    /**
     * Hapus akun secara permanen beserta seluruh media di Cloudinary.
     * Children dihapus eksplisit (bukan mengandalkan cascade FK) agar aman
     * terhadap keterbatasan pengurutan cascade di MySQL/MariaDB.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Tidak bisa menghapus akun sendiri.');

        $email = $user->email;
        $deletedFiles = 0;
        $failedFiles = 0;

        DB::transaction(function () use ($user, &$deletedFiles, &$failedFiles) {
            $mediaIds = Medium::withTrashed()->where('user_id', $user->id)->pluck('id');
            $tagIds = DB::table('tags')->where('user_id', $user->id)->pluck('id');

            $mediaItems = Medium::withTrashed()->where('user_id', $user->id)->get();

            foreach ($mediaItems as $item) {
                try {
                    if ($this->cloudinary->delete($item->public_id, $item->resource_type)) {
                        $deletedFiles++;
                    }
                } catch (\Throwable) {
                    $failedFiles++;
                }
            }

            DB::table('media_tag')->whereIn('media_id', $mediaIds)->delete();
            DB::table('media_shares')->whereIn('media_id', $mediaIds)->delete();
            DB::table('media_shares')->where('owner_id', $user->id)->delete();
            Medium::withTrashed()->where('user_id', $user->id)->forceDelete();

            DB::table('folders')->where('user_id', $user->id)->delete();
            DB::table('tags')->whereIn('id', $tagIds)->delete();
            DB::table('categories')->where('user_id', $user->id)->delete();

            $user->delete();
        });

        $this->activityLog->log(
            $request->user()->id,
            'account_delete',
            "Menghapus permanen akun {$email} beserta {$deletedFiles} file dari Cloudinary"
                .($failedFiles > 0 ? " ({$failedFiles} file gagal dihapus dari storage)" : ''),
            User::class,
            $user->id,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$email} dihapus permanen beserta {$deletedFiles} file media."
                .($failedFiles > 0 ? " Perhatian: {$failedFiles} file gagal dihapus dari storage." : ''));
    }
}
