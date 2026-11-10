@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="rounded-2xl bg-white shadow-sm">
    <div class="flex flex-wrap items-center gap-3 p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..."
                   class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Cari</button>
        </form>
        <span class="ml-auto text-sm text-slate-500">{{ $users->total() }} pengguna</span>
    </div>

    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Pengguna</th>
                <th class="px-6 py-3">Role</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Jumlah Media</th>
                <th class="px-6 py-3">Terdaftar</th>
                <th class="px-6 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-indigo-600 hover:underline">{{ $user->name }}</a>
                        <p class="text-xs text-slate-400">{{ $user->email }}</p>
                    </td>
                    <td class="px-6 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-3">{{ number_format($user->media_count) }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-3">
                        @if ($user->id !== auth()->id())
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-1">
                                    @csrf
                                    <select name="role" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                        <option value="user" @selected($user->role === 'user')>User</option>
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                    </select>
                                    <button type="submit" class="rounded bg-indigo-600 px-2 py-1 text-xs text-white hover:bg-indigo-700">Set Role</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Ubah status akun ini?')"
                                            class="rounded {{ $user->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }} px-2 py-1 text-xs">
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus PERMANEN akun beserta seluruh media di Cloudinary? Tindakan ini tidak bisa dibatalkan!')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700">Hapus</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="p-6">{{ $users->links() }}</div>
</div>
@endsection
