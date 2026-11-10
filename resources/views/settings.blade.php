@extends('layouts.app')

@section('title', 'Kategori & Tag')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Kategori --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-slate-900">Kategori</h2>

        <form method="POST" action="{{ route('categories.store') }}" class="mt-4 flex gap-2">
            @csrf
            <input type="text" name="name" required placeholder="Nama kategori baru"
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tambah</button>
        </form>

        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($categories as $category)
                <li class="flex items-center gap-2 py-2">
                    <span class="flex-1 truncate text-sm">{{ $category->name }}</span>
                    <form method="POST" action="{{ route('categories.update', $category) }}" class="flex gap-1">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $category->name }}" class="w-32 rounded border border-slate-300 px-2 py-1 text-xs">
                        <button type="submit" class="rounded bg-slate-200 px-2 py-1 text-xs hover:bg-slate-300">Simpan</button>
                    </form>
                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori? Media terkait menjadi tanpa kategori.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded bg-red-100 px-2 py-1 text-xs text-red-700 hover:bg-red-200">Hapus</button>
                    </form>
                </li>
            @empty
                <li class="py-4 text-center text-sm text-slate-400">Belum ada kategori.</li>
            @endforelse
        </ul>
    </div>

    {{-- Tag --}}
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-slate-900">Tag</h2>

        <form method="POST" action="{{ route('tags.store') }}" class="mt-4 flex gap-2">
            @csrf
            <input type="text" name="name" required placeholder="Nama tag baru"
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tambah</button>
        </form>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($tags as $tag)
                <span class="flex items-center gap-2 rounded-full bg-slate-100 py-1 pl-3 pr-1 text-sm">
                    {{ $tag->name }}
                    <form method="POST" action="{{ route('tags.destroy', $tag) }}" onsubmit="return confirm('Hapus tag?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:bg-red-100 hover:text-red-600">×</button>
                    </form>
                </span>
            @empty
                <p class="w-full py-4 text-center text-sm text-slate-400">Belum ada tag.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
