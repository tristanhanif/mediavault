@extends('layouts.app')

@section('title', 'Media Dibagikan')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="rounded-2xl bg-white shadow-sm">
        <div class="border-b border-slate-100 p-6">
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">Link Berbagi Aktif</span>
            <h2 class="mt-3 text-lg font-semibold break-all">{{ $media->name }}</h2>
            <p class="text-sm text-slate-500">Di bagikan oleh {{ $media->user->name }} • {{ $media->formatted_size }}</p>
        </div>

        <div class="flex min-h-80 items-center justify-center bg-slate-900/95 p-4">
            @if ($media->isImage())
                <img src="{{ route('media.stream', $media) }}" alt="{{ $media->name }}" class="max-h-[60vh] object-contain">
            @elseif ($media->isVideo())
                <video src="{{ route('media.stream', $media) }}" controls class="max-h-[60vh] max-w-full"></video>
            @elseif (strtolower($media->extension) === 'pdf')
                <iframe src="{{ route('media.stream', $media) }}" class="h-[60vh] w-full rounded bg-white"></iframe>
            @else
                <p class="text-slate-300">Pratinjau tidak tersedia untuk format .{{ $media->extension }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3 p-6">
            <a href="{{ route('media.download', $media) }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Download</a>
            <span class="text-xs text-slate-400">Akses berlaku sampai pemilik mencabut link.</span>
        </div>
    </div>
</div>
@endsection
