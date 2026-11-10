@extends('layouts.app')

@section('title', $media->name)

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Preview --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl bg-white shadow-sm">
            <div class="flex min-h-96 items-center justify-center rounded-t-2xl bg-slate-900/95 p-4">
                @if ($media->isImage())
                    <img src="{{ route('media.stream', $media) }}" alt="{{ $media->name }}" class="max-h-[70vh] max-w-full object-contain">
                @elseif ($media->isVideo())
                    <video src="{{ route('media.stream', $media) }}" controls class="max-h-[70vh] max-w-full"></video>
                @elseif (strtolower($media->extension) === 'pdf')
                    <iframe src="{{ route('media.stream', $media) }}" class="h-[70vh] w-full rounded-lg bg-white" title="{{ $media->name }}"></iframe>
                @else
                    <div class="text-center text-slate-300">
                        <svg class="mx-auto h-16 w-16" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="mt-3 text-sm">Pratinjau tidak tersedia untuk format .{{ $media->extension }}</p>
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-3 p-4">
                <a href="{{ route('media.download', $media) }}"
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Download</a>
                <a href="{{ route('media.stream', $media) }}" target="_blank"
                   class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">Buka di tab baru</a>
                @if ($media->user_id === auth()->id())
                    <form method="POST" action="{{ route('media.destroy', $media) }}" onsubmit="return confirm('Pindahkan ke recycle bin?')" class="ml-auto">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Hapus</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Detail panel --}}
    <div class="space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">Detail Media</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Nama</dt><dd class="break-all text-right font-medium">{{ $media->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Tipe</dt><dd class="font-medium">{{ ucfirst($media->resource_type) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Format</dt><dd class="font-medium">{{ $media->mime_type }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Ukuran</dt><dd class="font-medium">{{ $media->formatted_size }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Lokasi</dt><dd class="text-right font-medium">{{ $media->folder ? $media->folder->name : 'Root' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Pemilik</dt><dd class="font-medium">{{ $media->user->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Diunggah</dt><dd class="font-medium">{{ $media->created_at->format('d M Y H:i') }}</dd></div>
            </dl>
            <p class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">File bersifat <strong>immutable</strong>: konten &amp; nama tidak dapat diubah setelah diunggah.</p>
        </div>

        @if ($media->user_id === auth()->id())
            {{-- Kategori & tag --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Kategori &amp; Tag</h2>
                <form method="POST" action="{{ route('media.update', $media) }}" class="mt-4 space-y-3">
                    @csrf @method('PUT')
                    <select name="category_id" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">- Tanpa Kategori -</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($media->category_id === $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <select name="tags[]" multiple size="5" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}" @selected($media->tags->contains($tag->id))>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan Metadata</button>
                </form>
            </div>

            {{-- Kelola share --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Berbagi Media</h2>
                <form method="POST" action="{{ route('shares.store') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="media_id" value="{{ $media->id }}">
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        {{ $activeShares->isEmpty() ? 'Generate Link Berbagi' : 'Regenerasi Link' }}
                    </button>
                </form>
                <div class="mt-4 space-y-3">
                    @forelse ($activeShares as $share)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <code class="block overflow-x-auto text-xs text-slate-600">{{ route('shared.media', ['token' => $share->token]) }}</code>
                            <form method="POST" action="{{ route('shares.revoke', $share) }}" class="mt-2" onsubmit="return confirm('Cabut akses berbagi ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Cabut Akses</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada link aktif. Link bersifat privat &amp; wajib login penerima.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
