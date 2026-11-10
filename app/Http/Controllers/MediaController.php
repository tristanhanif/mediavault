<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Folder;
use App\Models\MediaShare;
use App\Models\Medium;
use App\Models\Tag;
use App\Services\ActivityLogService;
use App\Services\CloudinaryService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt'];

    public const ALLOWED_IMAGE_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'svg', 'avif', 'jfif', 'tif', 'tiff', 'ico', 'heic', 'heif',
    ];

    public const ALLOWED_VIDEO_EXTENSIONS = [
        'mp4', 'mkv', 'avi', 'mov', 'webm', 'wmv', 'flv', 'm4v', 'mpeg', 'mpg', '3gp', 'ts', 'ogv',
    ];
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly CloudinaryService $cloudinary,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $folderId = $request->filled('folder') ? (int) $request->query('folder') : null;
        $folder = null;

        if ($folderId !== null) {
            $folder = Folder::where('user_id', $user->id)->findOrFail($folderId);
        }

        $folders = Folder::where('user_id', $user->id)
            ->where('parent_id', $folderId)
            ->orderBy('name')
            ->get();

        $query = Medium::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('folder_id', $folderId)
            ->with(['category', 'tags']);

        $query = $this->applyFilters($request, $query);

        $media = $query->orderBy($request->query('sort', 'created_at'), $request->query('direction', 'desc'))
            ->paginate(24)
            ->withQueryString();

        $categories = Category::where('user_id', $user->id)->orderBy('name')->get();
        $tags = Tag::where('user_id', $user->id)->orderBy('name')->get();

        return view('media.index', compact('folders', 'folder', 'media', 'categories', 'tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['required', 'file', 'max:'.(Medium::MAX_FILE_SIZE / 1024)],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $rejected = [];
        foreach ($request->file('files', []) as $file) {
            if (! $this->isAllowedFormat($file)) {
                $rejected[] = $file->getClientOriginalName();
            }
        }

        if ($rejected !== []) {
            return back()->with('error', 'Format tidak didukung: '.implode(', ', $rejected)
                .'. Didukung: Dokumen (PDF, DOC, DOCX, TXT), Gambar, dan Video.');
        }

        $result = $this->mediaService->upload(
            $request->file('files', []),
            Auth::id(),
            $request->integer('folder_id') ?: null,
            $request->integer('category_id') ?: null,
        );

        if ($result['failed'] !== []) {
            return back()
                ->with('error', 'Sebagian file gagal diunggah: '.implode('; ', $result['failed']))
                ->with('success', count($result['success']).' file berhasil diunggah.');
        }

        return back()->with('success', count($result['success']).' file berhasil diunggah.');
    }

    public function show(Medium $medium)
    {
        $this->authorizeMedia($medium);

        return view('media.show', [
            'media' => $medium->load(['category', 'tags', 'folder.parent', 'user']),
            'categories' => Category::where('user_id', $medium->user_id)->orderBy('name')->get(),
            'tags' => Tag::where('user_id', $medium->user_id)->orderBy('name')->get(),
            'activeShares' => $medium->shares()->whereNull('revoked_at')->get(),
        ]);
    }

    /**
     * Streaming proxy privat: file hanya dapat diakses oleh pemilik, admin,
     * atau penerima share link yang sedang login.
     */
    public function stream(Request $request, Medium $medium): StreamedResponse
    {
        $this->authorizeMedia($medium);

        $url = $this->cloudinary->signedUrl($medium->public_id, $medium->resource_type);

        $headers = [];

        if ($request->header('Range')) {
            $headers['Range'] = $request->header('Range');
        }

        return response()->stream(function () use ($url) {
            $response = Http::withOptions(['stream' => true])->get($url);

            $body = $response->toPsrResponse()->getBody();

            while (! $body->eof()) {
                echo $body->read(1024 * 64);

                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => $medium->mime_type,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function download(Medium $medium): StreamedResponse
    {
        $this->authorizeMedia($medium);

        $url = $this->cloudinary->signedUrl($medium->public_id, $medium->resource_type);

        $this->activityLog->log(
            Auth::id(),
            ActivityLog::ACTION_DOWNLOAD,
            "Mengunduh file \"{$medium->name}\"",
            Medium::class,
            $medium->id,
        );

        return response()->stream(function () use ($url) {
            Http::withOptions(['stream' => true])->get($url)
                ->toPsrResponse()
                ->getBody()
                ->read(PHP_INT_MAX);
        }, 200, [
            'Content-Type' => $medium->mime_type,
            'Content-Disposition' => 'attachment; filename="'.$medium->name.'"',
        ]);
    }

    public function destroy(Medium $medium): RedirectResponse
    {
        abort_unless($medium->user_id === Auth::id() || Auth::user()->isAdmin(), 403);

        $this->mediaService->delete($medium, Auth::id());

        return back()->with('success', "File \"{$medium->name}\" dipindahkan ke recycle bin.");
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $media = $this->mediaService->restore($id, Auth::id());

        return back()->with('success', "File \"{$media->name}\" berhasil dikembalikan.");
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $media = Medium::withTrashed()->where('user_id', Auth::id())->findOrFail($id);

        $this->mediaService->forceDelete($media, Auth::id());

        return back()->with('success', "File \"{$media->name}\" dihapus permanen.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['integer'],
        ]);

        $count = $this->mediaService->bulkDelete($validated['media_ids'], Auth::id());

        return back()->with('success', "{$count} file dipindahkan ke recycle bin.");
    }

    public function move(Request $request, Medium $medium): RedirectResponse
    {
        abort_unless($medium->user_id === Auth::id() || Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'target_folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $this->mediaService->move($medium, $validated['target_folder_id'] ?? null, Auth::id());

        return back()->with('success', "File \"{$medium->name}\" berhasil dipindahkan.");
    }

    public function update(Request $request, Medium $medium): RedirectResponse
    {
        abort_unless($medium->user_id === Auth::id() || Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ]);

        $medium->update([
            'category_id' => $validated['category_id'] ?? null,
        ]);

        $tagIds = Tag::where('user_id', $medium->user_id)
            ->whereIn('id', $validated['tags'] ?? [])
            ->pluck('id')
            ->all();

        $medium->tags()->sync($tagIds);

        return back()->with('success', 'Metadata media berhasil diperbarui.');
    }

    public function trash(Request $request)
    {
        $media = Medium::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->with(['folder', 'category'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(24, ['*'], 'media_page');

        $folders = Folder::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('media.trash', compact('media', 'folders'));
    }

    public function sharedWithMe(Request $request)
    {
        $media = Medium::query()
            ->whereHas('shares', function ($q) {
                $q->whereNull('revoked_at');
            })
            ->where('user_id', '!=', $request->user()->id)
            ->with(['user', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        return view('media.shared', compact('media'));
    }

    /**
     * Whitelist format sesuai PRD: Dokumen (PDF/DOC/DOCX/TXT), Gambar, Video.
     * Pengecekan berbasis isi file (mime) + ekstensi untuk mencegah bypass.
     */
    protected function isAllowedFormat($file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType() ?? '';

        if (in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)
            || str_starts_with($mime, 'image/')) {
            return in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)
                && str_starts_with($mime, 'image/');
        }

        if (in_array($extension, self::ALLOWED_VIDEO_EXTENSIONS, true)
            || str_starts_with($mime, 'video/')) {
            return in_array($extension, self::ALLOWED_VIDEO_EXTENSIONS, true)
                && str_starts_with($mime, 'video/');
        }

        return in_array($extension, self::ALLOWED_DOCUMENT_EXTENSIONS, true)
            && $this->isAllowedDocumentMime($mime, $extension);
    }

    protected function isAllowedDocumentMime(string $mime, string $extension): bool
    {
        return in_array($mime, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/html',
            'application/octet-stream',
        ], true);
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($type = $request->query('type')) {
            $query->where('resource_type', $type);
        }

        if ($categoryId = $request->query('category')) {
            $query->where('category_id', $categoryId);
        }

        if ($tagId = $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    protected function authorizeMedia(Medium $medium): void
    {
        $user = Auth::user();

        if ($medium->user_id === $user->id || $user->isAdmin()) {
            return;
        }

        $hasAccess = MediaShare::where('media_id', $medium->id)
            ->whereNull('revoked_at')
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke media ini.');
    }
}
