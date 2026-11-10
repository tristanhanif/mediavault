@extends('layouts.app')

@section('title', 'File Saya')

@section('content')
<div x-data="{ showUpload: false, selected: [] }">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('media.index') }}" class="flex flex-1 flex-wrap items-center gap-2">
            @if ($folder)
                <input type="hidden" name="folder" value="{{ $folder->id }}">
            @endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama file..."
                   class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Tipe</option>
                <option value="image" @selected(request('type') === 'image')>Gambar</option>
                <option value="video" @selected(request('type') === 'video')>Video</option>
                <option value="raw" @selected(request('type') === 'raw')>Dokumen</option>
            </select>
            <select name="category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="tag" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Tag</option>
                @foreach ($tags as $tag)
                    <option value="{{ $tag->id }}" @selected(request('tag') == $tag->id)>{{ $tag->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="sort" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>Tanggal Upload</option>
                <option value="name" @selected(request('sort') === 'name')>Nama</option>
                <option value="size_bytes" @selected(request('sort') === 'size_bytes')>Ukuran</option>
            </select>
            <select name="direction" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="desc" @selected(request('direction', 'desc') === 'desc')>Menurun</option>
                <option value="asc" @selected(request('direction') === 'asc')>Menaik</option>
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
        </form>

        <button type="button" @click="showUpload = !showUpload"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Upload Media
        </button>
    </div>

    {{-- Form upload multi-file --}}
    <div x-show="showUpload" x-collapse class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
        <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
            <div>
                <label class="block text-sm font-medium text-slate-700">Pilih file (bisa banyak, maks. 20 MB per file)</label>
                <input type="file" name="files[]" multiple required
                       accept=".pdf,.doc,.docx,.txt,image/*,video/*"
                       class="mt-2 block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-white hover:file:bg-indigo-700">
            </div>
            <div class="max-w-xs">
                <label class="block text-sm font-medium text-slate-700">Kategori (opsional)</label>
                <select name="category_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">- Tanpa Kategori -</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Unggah</button>
        </form>
    </div>

    {{-- Breadcrumb --}}
    <nav class="mt-6 flex items-center gap-1 text-sm">
        <a href="{{ route('media.index') }}" class="font-medium text-indigo-600 hover:underline">Root</a>
        @if ($folder)
            @foreach ($folder->ancestors() as $ancestor)
                <span class="text-slate-400">/</span>
                <a href="{{ route('media.index', ['folder' => $ancestor->id]) }}" class="text-indigo-600 hover:underline">{{ $ancestor->name }}</a>
            @endforeach
            <span class="text-slate-400">/</span>
            <span class="font-semibold">{{ $folder->name }}</span>
        @endif
    </nav>

    {{-- Bulk action bar --}}
    <div class="mt-4 flex items-center justify-between" x-show="selected.length > 0" x-cloak>
        <span class="text-sm text-slate-600" x-text="selected.length + ' file dipilih'"></span>
        <form method="POST" action="{{ route('media.bulk-destroy') }}" @submit="document.querySelectorAll('input[name=\'media_ids[]\']').forEach(i => i.disabled = !i.checked)">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="media_ids[]" :value="id">
            </template>
            <button type="submit" onclick="return confirm('Pindahkan file terpilih ke recycle bin?')"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                Hapus Terpilih
            </button>
        </form>
    </div>

    {{-- Folder grid --}}
    @if ($folders->isNotEmpty())
        <h3 class="mt-6 text-sm font-semibold uppercase tracking-wide text-slate-500">Folder</h3>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($folders as $sub)
                <div class="group relative rounded-xl bg-white p-4 shadow-sm transition hover:shadow-md">
                    <a href="{{ route('media.index', ['folder' => $sub->id]) }}" class="block">
                        <svg class="h-10 w-10 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        <p class="mt-2 truncate text-sm font-medium">{{ $sub->name }}</p>
                        <p class="text-xs text-slate-400">Level {{ $sub->depth }}/3</p>
                    </a>
                    <div class="mt-2 flex gap-1 opacity-0 transition group-hover:opacity-100">
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded p-1 text-xs text-slate-500 hover:bg-slate-100">Opsi</summary>
                            <div class="absolute left-0 top-6 z-20 w-48 space-y-1 rounded-lg border border-slate-200 bg-white p-2 shadow-lg">
                                <details class="block">
                                    <summary class="cursor-pointer rounded px-2 py-1 text-xs hover:bg-slate-50">Ganti Nama</summary>
                                    <form method="POST" action="{{ route('folders.update', $sub) }}" class="p-1">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" value="{{ $sub->name }}" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                        <button type="submit" class="mt-1 w-full rounded bg-indigo-600 px-2 py-1 text-xs text-white">Simpan</button>
                                    </form>
                                </details>
                                <details class="block">
                                    <summary class="cursor-pointer rounded px-2 py-1 text-xs hover:bg-slate-50">Pindahkan</summary>
                                    <form method="POST" action="{{ route('folders.move', $sub) }}" class="p-1">
                                        @csrf
                                        <select name="target_id" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                            <option value="">Root</option>
                                            @foreach (\App\Models\Folder::where('user_id', auth()->id())->where('id', '!=', $sub->id)->orderBy('name')->get() as $target)
                                                <option value="{{ $target->id }}">{{ str_repeat('— ', $target->depth - 1) }}{{ $target->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="mt-1 w-full rounded bg-indigo-600 px-2 py-1 text-xs text-white">Pindah</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('folders.destroy', $sub) }}" onsubmit="return confirm('Hapus folder beserta seluruh isinya ke recycle bin?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full rounded px-2 py-1 text-left text-xs text-red-600 hover:bg-red-50">Hapus</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Media grid --}}
    <h3 class="mt-6 text-sm font-semibold uppercase tracking-wide text-slate-500">Media</h3>
    <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @forelse ($media as $item)
            <div class="group rounded-xl bg-white shadow-sm transition hover:shadow-md">
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
                        <p class="text-xs text-slate-400">{{ $item->formatted_size }}</p>
                    </div>
                </a>
                <div class="flex items-center gap-2 border-t border-slate-100 px-3 py-2">
                    <input type="checkbox" value="{{ $item->id }}" x-model="selected" class="rounded border-slate-300 text-indigo-600">
                    <a href="{{ route('media.download', $item) }}" class="ml-auto text-xs font-medium text-indigo-600 hover:underline">Download</a>
                    <form method="POST" action="{{ route('media.destroy', $item) }}" onsubmit="return confirm('Pindahkan ke recycle bin?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl bg-white py-12 text-center text-slate-400 shadow-sm">
                Tidak ada media{{ request('q') || request('type') || request('category') || request('tag') ? ' yang cocok dengan filter' : '' }} di lokasi ini.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $media->links() }}</div>
</div>
@endsection
