@extends('layouts.app')

@section('title', 'Dibagikan ke Saya')

@section('content')
<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
    @forelse ($media as $item)
        <div class="rounded-xl bg-white shadow-sm transition hover:shadow-md">
            <a href="{{ route('media.show', $item) }}" class="block">
                <div class="flex h-28 items-center justify-center overflow-hidden rounded-t-xl bg-slate-100">
                    @if ($item->isImage())
                        <img src="{{ route('media.stream', $item) }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy">
                    @elseif ($item->isVideo())
                        <svg class="h-10 w-10 text-rose-500" fill="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    @else
                        <svg class="h-10 w-10 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    @endif
                </div>
                <div class="p-3">
                    <p class="truncate text-sm font-medium" title="{{ $item->name }}">{{ $item->name }}</p>
                    <p class="text-xs text-slate-400">oleh {{ $item->user->name }}</p>
                </div>
            </a>
            <div class="border-t border-slate-100 px-3 py-2">
                <a href="{{ route('media.download', $item) }}" class="text-xs font-medium text-indigo-600 hover:underline">Download</a>
            </div>
        </div>
    @empty
        <div class="col-span-full rounded-xl bg-white py-12 text-center text-slate-400 shadow-sm">
            Belum ada media yang dibagikan kepada Anda.
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $media->links() }}</div>
@endsection
