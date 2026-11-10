@extends('layouts.app')

@section('title', 'Detail Pengguna')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h2 class="truncate font-semibold text-slate-900">{{ $user->name }}</h2>
                <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
            </div>
        </div>

        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Role</dt><dd class="font-medium">{{ ucfirst($user->role) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Terdaftar</dt><dd class="font-medium">{{ $user->created_at->format('d M Y') }}</dd></div>
            <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="text-slate-500">Total Media</dt><dd class="font-medium">{{ number_format($stats->total) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Gambar / Video</dt><dd class="font-medium">{{ $stats->images }} / {{ $stats->videos }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Dokumen</dt><dd class="font-medium">{{ $stats->documents }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Storage</dt><dd class="font-medium">{{ number_format($stats->storage_used / 1048576, 2) }} MB</dd></div>
        </dl>

        <div class="mt-6 space-y-2">
            @if ($user->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-2">
                    @csrf
                    <select name="role" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="user" @selected($user->role === 'user')>User</option>
                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                    </select>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Set Role</button>
                </form>
                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Ubah status akun ini?')"
                            class="w-full rounded-lg {{ $user->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }} px-4 py-2 text-sm font-semibold">
                        {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus PERMANEN akun ini beserta seluruh media di Cloudinary? Tindakan ini tidak bisa dibatalkan!')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Hapus Permanen Akun
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="min-w-0 lg:col-span-3">
        <div class="rounded-2xl bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.users.show', $user) }}" class="flex flex-wrap items-center gap-3 p-6 pb-3">
                <h2 class="font-semibold text-slate-900">Seluruh Media Pengguna</h2>
                <div class="ml-auto flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama file..."
                           class="w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua Tipe</option>
                        <option value="image" @selected(request('type') === 'image')>Gambar</option>
                        <option value="video" @selected(request('type') === 'video')>Video</option>
                        <option value="raw" @selected(request('type') === 'raw')>Dokumen</option>
                    </select>
                    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Nama File</th>
                            <th class="px-6 py-3">Tipe</th>
                            <th class="px-6 py-3">Lokasi</th>
                            <th class="px-6 py-3">Ukuran</th>
                            <th class="px-6 py-3">Diunggah</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($media as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="max-w-64 truncate px-6 py-3 font-medium" title="{{ $item->name }}">{{ $item->name }}</td>
                                <td class="px-6 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $item->resource_type === 'image' ? 'bg-emerald-100 text-emerald-700' : ($item->resource_type === 'video' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($item->resource_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">{{ $item->folder?->name ?? 'Root' }}</td>
                                <td class="px-6 py-3">{{ $item->formatted_size }}</td>
                                <td class="px-6 py-3 text-slate-500">{{ $item->created_at->diffForHumans() }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('media.show', $item) }}" class="text-xs font-medium text-indigo-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Pengguna ini belum mengunggah media.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-6">{{ $media->links() }}</div>
        </div>
    </div>
</div>
@endsection
