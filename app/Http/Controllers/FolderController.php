<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Services\ActivityLogService;
use App\Services\FolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class FolderController extends Controller
{
    public function __construct(
        private readonly FolderService $folders,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        try {
            $folder = $this->folders->create(
                Auth::id(),
                $validated['parent_id'] ?? null,
                $validated['name'],
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_FOLDER_CREATE,
            "Membuat folder \"{$folder->name}\"",
            Folder::class,
            $folder->id,
        );

        return back()->with('success', "Folder \"{$folder->name}\" berhasil dibuat.");
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        $this->authorizeFolder($folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $folder = $this->folders->rename($folder, $validated['name']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_RENAME,
            "Mengganti nama folder menjadi \"{$folder->name}\"",
            Folder::class,
            $folder->id,
        );

        return back()->with('success', 'Folder berhasil diganti nama.');
    }

    public function move(Request $request, Folder $folder): RedirectResponse
    {
        $this->authorizeFolder($folder);

        $validated = $request->validate([
            'target_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        try {
            $this->folders->move($folder, $validated['target_id'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_MOVE,
            "Memindahkan folder \"{$folder->name}\"",
            Folder::class,
            $folder->id,
        );

        return back()->with('success', "Folder \"{$folder->name}\" berhasil dipindahkan.");
    }

    public function destroy(Folder $folder): RedirectResponse
    {
        $this->authorizeFolder($folder);

        $name = $folder->name;

        $this->folders->delete($folder);

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_FOLDER_DELETE,
            "Menghapus folder \"{$name}\" (masuk recycle bin)",
            Folder::class,
            $folder->id,
        );

        return redirect()
            ->route('media.index')
            ->with('success', "Folder \"{$name}\" dipindahkan ke recycle bin.");
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        try {
            $folder = $this->folders->restore($id, Auth::id());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengembalikan folder.');
        }

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_RESTORE,
            "Mengembalikan folder \"{$folder->name}\" dari recycle bin",
            Folder::class,
            $folder->id,
        );

        return back()->with('success', "Folder \"{$folder->name}\" berhasil dikembalikan.");
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $folder = Folder::withTrashed()->where('user_id', Auth::id())->findOrFail($id);

        $name = $folder->name;

        $this->folders->forceDelete($folder, Auth::id());

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_FORCE_DELETE,
            "Menghapus permanen folder \"{$name}\" beserta isinya",
            Folder::class,
            $folder->id,
        );

        return back()->with('success', "Folder \"{$name}\" dihapus permanen beserta isinya.");
    }

    protected function authorizeFolder(Folder $folder): void
    {
        abort_unless($folder->user_id === Auth::id() || Auth::user()->isAdmin(), 403);
    }
}
