@extends('layouts.main')

@section('content')
@php
    $statusConfig = [
        'diajukan' => ['label' => 'Diajukan', 'bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-200', 'icon' => 'fa-paper-plane'],
        'diterima' => ['label' => 'Diterima', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'fa-check-circle'],
        'ditolak'  => ['label' => 'Ditolak', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'fa-times-circle'],
    ];
    $pendingCount = \App\Models\DataRequest::where('status', 'diajukan')->count();
@endphp

<div class="bg-white rounded-xl shadow p-4 lg:p-6">

    <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-800">Tinjau Usulan Penyediaan Data</h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-0.5">
                Usulan data baru dari pengguna yang perlu ditindaklanjuti
                @if($pendingCount > 0)
                    <span class="ml-1 text-amber-600 font-semibold">({{ $pendingCount }} menunggu tinjauan)</span>
                @endif
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.data_requests.index') }}"
        class="mb-4 border-t border-gray-100 pt-4 flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama data pengajuan"
                oninput="autoSubmitDebounce(this)"
                class="w-full py-2 pl-9 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400">
        </div>
        <select name="status" onchange="this.form.submit()" class="p-2 rounded-md border-2 border-gray-300 text-sm sm:w-48">
            <option value="">Semua Status</option>
            @foreach($statusConfig as $key => $cfg)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
            @endforeach
        </select>
    </form>

    {{-- ===== MOBILE: card list (below md) ===== --}}
    <div class="md:hidden space-y-3">
        @forelse($dataRequests as $item)
            @php $cfg = $statusConfig[$item->status] ?? $statusConfig['diajukan']; @endphp
            <div class="relative border border-gray-100 rounded-lg p-3 hover:bg-gray-50/60 transition-colors">

                <a href="{{ route('admin.data_requests.show', $item) }}"
                   class="absolute top-3 right-3 text-sky-600 hover:text-sky-800 text-xs font-medium">
                    <i class="fas fa-eye"></i> Tinjau
                </a>

                <div class="pr-16">
                    <p class="text-sm font-semibold text-gray-800 leading-snug break-words">{{ $item->nama_data }}</p>
                    <span class="mt-1.5 inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-medium border {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }}">
                        <i class="fas {{ $cfg['icon'] }} text-[9px]"></i>
                        {{ $cfg['label'] }}
                    </span>
                </div>

                <dl class="mt-3 pt-2 border-t border-gray-50 grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-gray-500">
                    <div>
                        <dt class="text-[10px] text-gray-400 uppercase tracking-wide">Diajukan Oleh</dt>
                        <dd class="text-gray-600 truncate">{{ $item->user->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] text-gray-400 uppercase tracking-wide">Wilayah</dt>
                        <dd class="text-gray-600 truncate">{{ $item->location->nama_wilayah ?? '-' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-[10px] text-gray-400 uppercase tracking-wide">Tanggal</dt>
                        <dd class="text-gray-600">{{ $item->created_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-gray-400">
                <i class="fas fa-inbox text-2xl mb-2 block text-gray-300"></i>
                Belum ada usulan data yang masuk.
            </div>
        @endforelse
    </div>

    {{-- ===== DESKTOP: table (md and up) ===== --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-2 font-semibold">Nama Data</th>
                    <th class="px-4 py-2 font-semibold">Diajukan Oleh</th>
                    <th class="px-4 py-2 font-semibold">Wilayah</th>
                    <th class="px-4 py-2 font-semibold">Status</th>
                    <th class="px-4 py-2 font-semibold">Tanggal</th>
                    <th class="px-4 py-2 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($dataRequests as $item)
                    @php $cfg = $statusConfig[$item->status] ?? $statusConfig['diajukan']; @endphp
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-700">{{ $item->nama_data }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $item->user->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $item->location->nama_wilayah ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }}">
                                <i class="fas {{ $cfg['icon'] }} text-[10px]"></i>
                                {{ $cfg['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400">{{ $item->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.data_requests.show', $item) }}"
                               class="text-sky-600 hover:text-sky-800 text-xs font-medium">
                                <i class="fas fa-eye"></i> Tinjau
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                            <i class="fas fa-inbox text-2xl mb-2 block text-gray-300"></i>
                            Belum ada usulan data yang masuk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($dataRequests->hasPages())
        <div class="mt-4 border-t border-gray-100 pt-4">
            {{ $dataRequests->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection