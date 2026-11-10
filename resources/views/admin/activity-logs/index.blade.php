@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="rounded-2xl bg-white shadow-sm">
    <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="flex flex-wrap gap-3 p-6">
        <select name="action" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua Aksi</option>
            @foreach (['upload', 'delete', 'restore', 'force_delete', 'rename', 'move', 'download', 'folder_create', 'folder_delete', 'share_create', 'share_revoke', 'login', 'register', 'role_change', 'account_toggle'] as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ Str::headline($action) }}</option>
            @endforeach
        </select>
        <select name="user_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua Pengguna</option>
            @foreach ($users as $id => $name)
                <option value="{{ $id }}" @selected(request('user_id') == $id)>{{ $name }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
    </form>

    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Waktu</th>
                <th class="px-6 py-3">Pengguna</th>
                <th class="px-6 py-3">Aksi</th>
                <th class="px-6 py-3">Deskripsi</th>
                <th class="px-6 py-3">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td class="px-6 py-3 font-medium">{{ $log->user?->name ?? '-' }}</td>
                    <td class="px-6 py-3">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium">{{ Str::headline($log->action) }}</span>
                    </td>
                    <td class="px-6 py-3">{{ $log->description }}</td>
                    <td class="px-6 py-3 text-slate-400">{{ $log->ip_address ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400">Belum ada aktivitas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-6">{{ $logs->links() }}</div>
</div>
@endsection
