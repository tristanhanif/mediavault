@extends('layouts.app')

@section('title', 'Recycle Bin')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">Folder Terhapus</h2>
            <p class="text-xs text-slate-400">Restore akan mengembalikan folder, subfolder, dan seluruh media di dalamnya.</p>
        </div>
    </div>

    <ul class="mt-4 divide-y divide-slate-100">
        @forelse ($folders as $folder)
            <li class="flex items-center gap-4 py-3">
                <svg class="h-8 w-8 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ $folder->name }}</p>
                    <p class="text-xs text-slate-400">Dihapus {{ $folder->deleted_at->diffForHumans() }}</p>
                </div>
                <form method="POST" action="{{ route('folders.restore', $folder->id) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Restore</button>
                </form>
                <form method="POST" action="{{ route('folders.force-delete', $folder->id) }}" onsubmit="return confirm('Hapus PERMANEN folder beserta seluruh isinya? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Hapus Permanen</button>
                </form>
            </li>
        @empty
            <li class="py-6 text-center text-sm text-slate-400">Tidak ada folder terhapus.</li>
        @endforelse
    </ul>
</div>

<div class="mt-6 rounded-2xl bg-white shadow-sm">
    <div class="p-6 pb-2">
        <h2 class="font-semibold text-slate-900">Media Terhapus</h2>
        <p class="text-xs text-slate-400">Hapus permanen akan menghapus file dari Cloudinary secara permanen.</p>
    </div>

    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Nama File</th>
                <th class="px-6 py-3">Lokasi</th>
                <th class="px-6 py-3">Dihapus</th>
                <th class="px-6 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($media as $item)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 font-medium">{{ $item->name }}</td>
                    <td class="px-6 py-3">{{ $item->folder?->name ?? 'Root' }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $item->deleted_at->diffForHumans() }}</td>
                    <td class="px-6 py-3">
                        <div class="flex justify-end gap-2">
                            <form method="POST" action="{{ route('media.restore', $item->id) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Restore</button>
                            </form>
                            <form method="POST" action="{{ route('media.force-delete', $item->id) }}" onsubmit="return confirm('Hapus PERMANEN file ini? Tindakan ini tidak bisa dibatalkan.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">Hapus Permanen</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">Recycle bin kosong.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-6">{{ $media->links() }}</div>
</div>
@endsection
