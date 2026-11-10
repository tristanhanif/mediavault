@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['label' => 'Total Media', 'value' => number_format($stats->total), 'color' => 'bg-indigo-500', 'icon' => 'M4 7v10c0 1.1.9 2 2 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H6a2 2 0 00-2 2z'],
        ['label' => 'Gambar', 'value' => number_format($stats->images), 'color' => 'bg-emerald-500', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['label' => 'Video', 'value' => number_format($stats->videos), 'color' => 'bg-rose-500', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
        ['label' => 'Dokumen', 'value' => number_format($stats->documents), 'color' => 'bg-amber-500', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ] as $card)
        <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $card['color'] }} text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
            </span>
            <div>
                <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ $card['value'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-slate-900">Storage Usage</h2>
        <span class="text-sm text-slate-500">{{ number_format($stats->storage_used / 1048576, 2) }} MB / {{ number_format($storageQuota / 1073741824) }} GB</span>
    </div>
    <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $storagePercent }}%"></div>
    </div>
    <p class="mt-2 text-xs text-slate-400">{{ $storagePercent }}% dari kuota indikator</p>
</div>

<div class="mt-6">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="font-semibold text-slate-900">Recent Uploads</h2>
        <a href="{{ route('media.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">Lihat semua</a>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-3">Nama File</th>
                    <th class="px-6 py-3">Tipe</th>
                    <th class="px-6 py-3">Ukuran</th>
                    <th class="px-6 py-3">Diunggah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($recentUploads as $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 font-medium">
                            <a href="{{ route('media.show', $item) }}" class="text-indigo-600 hover:underline">{{ $item->name }}</a>
                        </td>
                        <td class="px-6 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $item->resource_type === 'image' ? 'bg-emerald-100 text-emerald-700' : ($item->resource_type === 'video' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ ucfirst($item->resource_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-3">{{ $item->formatted_size }}</td>
                        <td class="px-6 py-3 text-slate-500">{{ $item->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400">Belum ada media. Mulai unggah dari menu "File Saya".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
