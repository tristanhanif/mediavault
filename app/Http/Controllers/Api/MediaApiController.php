<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medium;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaApiController extends Controller
{
    public function __construct(private readonly MediaService $mediaService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Medium::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->with(['category', 'tags']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($type = $request->query('type')) {
            $query->where('resource_type', $type);
        }

        $media = $query->orderBy($request->query('sort', 'created_at'), $request->query('direction', 'desc'))
            ->paginate((int) $request->query('per_page', 24));

        return response()->json([
            'data' => collect($media->items())->map(fn (Medium $m) => $this->formatMedia($m)),
            'meta' => [
                'current_page' => $media->currentPage(),
                'total' => $media->total(),
                'per_page' => $media->perPage(),
            ],
        ]);
    }

    public function show(Request $request, Medium $medium): JsonResponse
    {
        abort_unless($medium->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return response()->json(['data' => $this->formatMedia($medium->load(['category', 'tags', 'user']))]);
    }

    public function destroy(Request $request, Medium $medium): JsonResponse
    {
        abort_unless($medium->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $this->mediaService->delete($medium, $request->user()->id);

        return response()->json(['message' => 'File dipindahkan ke recycle bin.']);
    }

    protected function formatMedia(Medium $m): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'type' => $m->type,
            'resource_type' => $m->resource_type,
            'mime_type' => $m->mime_type,
            'extension' => $m->extension,
            'size_bytes' => $m->size_bytes,
            'formatted_size' => $m->formatted_size,
            'folder_id' => $m->folder_id,
            'category' => $m->category?->name,
            'tags' => $m->tags->pluck('name'),
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
