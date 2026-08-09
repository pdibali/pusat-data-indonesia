@extends('layouts.main')

@section('title', 'Daftar Transaksi Berlangganan')

@section('content')
<div class="page-layout">

    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Daftar Transaksi Berlangganan</h1>
            <p class="text-sm text-gray-500 mt-0.5">Semua transaksi berlangganan pengguna</p>
        </div>
        <a href="{{ route('admin.transaksi.dashboard') }}"
           class="px-4 py-2 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 transition flex items-center gap-2">
            <i class="fas fa-chart-bar text-xs"></i> Dashboard
        </a>
    </div>

    <div class="card-panel">

        {{-- Filter --}}
        <div class="p-4 border-b border-gray-100">
            <form id="form-transaksi" method="GET" action="{{ route('admin.transaksi.index') }}">
                <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3">

                    {{-- Search --}}
                    <div class="w-full sm:flex-1 sm:min-w-[180px] sm:max-w-xs">
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">Cari</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Order ID, nama user, layanan..."
                                oninput="autoSubmitDebounce(this)"
                                class="w-full pl-9 pr-8 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @if(request('search'))
                                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-400 transition">
                                    <i class="fas fa-times text-xs"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Status & Layanan --}}
                    <div class="grid grid-cols-2 sm:flex gap-2">
                        <div class="w-full sm:w-auto">
                            <label class="block text-[11px] font-medium text-gray-400 mb-1">Status</label>
                            <select name="status" onchange="this.form.submit()"
                                    class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Semua Status</option>
                                <option value="success"   {{ request('status') === 'success'   ? 'selected' : '' }}>Berhasil</option>
                                <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Menunggu</option>
                                <option value="menunggu_verifikasi" {{ request('status') === 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Gagal</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>

                        <div class="w-full sm:w-auto">
                            <label class="block text-[11px] font-medium text-gray-400 mb-1">Layanan</label>
                            <select name="layanan_id" onchange="this.form.submit()"
                                    class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">Semua Layanan</option>
                                @foreach($layanans as $lay)
                                    <option value="{{ $lay->layanan_id }}" {{ request('layanan_id') == $lay->layanan_id ? 'selected' : '' }}>
                                        {{ $lay->nama_layanan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Tanggal dari--sampai --}}
                    <div class="w-full sm:w-auto">
                        <label class="block text-[11px] font-medium text-gray-400 mb-1">Rentang Tanggal</label>
                        <div class="grid grid-cols-2 sm:flex sm:items-center gap-1.5">
                            <input type="date" name="dari" value="{{ request('dari') }}"
                                onchange="this.form.submit()"
                                title="Dari tanggal"
                                class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <span class="hidden sm:inline text-gray-400 text-xs">—</span>
                            <input type="date" name="sampai" value="{{ request('sampai') }}"
                                onchange="this.form.submit()"
                                title="Sampai tanggal"
                                class="w-full sm:w-auto px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        </div>
                    </div>

                    {{-- Active chips + Reset --}}
                    @if(request()->hasAny(['search','status','layanan_id','dari','sampai']))
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if(request('status'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-xs font-medium text-blue-700">
                                    {{ ucfirst(request('status')) }}
                                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="hover:text-red-500 transition"><i class="fas fa-times text-[10px]"></i></a>
                                </span>
                            @endif
                            @if(request('layanan_id'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-xs font-medium text-purple-700">
                                    {{ $layanans->firstWhere('layanan_id', request('layanan_id'))?->nama_layanan }}
                                    <a href="{{ request()->fullUrlWithQuery(['layanan_id' => null]) }}" class="hover:text-red-500 transition"><i class="fas fa-times text-[10px]"></i></a>
                                </span>
                            @endif
                            @if(request('dari') || request('sampai'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-xs font-medium text-amber-700">
                                    {{ request('dari') ?? '…' }} — {{ request('sampai') ?? '…' }}
                                    <a href="{{ request()->fullUrlWithQuery(['dari' => null, 'sampai' => null]) }}" class="hover:text-red-500 transition"><i class="fas fa-times text-[10px]"></i></a>
                                </span>
                            @endif
                            <a href="{{ route('admin.transaksi.index') }}"
                            class="text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1">
                                <i class="fas fa-times-circle"></i> Reset semua
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            @if($transaksis->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-receipt text-gray-400 text-xl"></i>
                </div>
                <p class="text-gray-500 text-sm font-medium">Tidak ada transaksi ditemukan</p>
            </div>
            @else

            {{-- Tabel lengkap — tablet & desktop (sm ke atas) --}}
            <table class="w-full text-sm hidden sm:table">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-5 py-3 text-left font-medium">#</th>
                        <th class="px-5 py-3 text-left font-medium">Order ID</th>
                        <th class="px-5 py-3 text-left font-medium">Pengguna</th>
                        <th class="px-5 py-3 text-left font-medium">Layanan</th>
                        <th class="px-5 py-3 text-left font-medium">Harga</th>
                        <th class="px-5 py-3 text-left font-medium">Metode</th>
                        <th class="px-5 py-3 text-left font-medium">Status</th>
                        <th class="px-5 py-3 text-left font-medium">Tanggal</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transaksis as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3.5 text-gray-400 text-xs">
                            {{ ($transaksis->currentPage() - 1) * $transaksis->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-gray-500">
                            {{ $item->order_id }}
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-800">{{ $item->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $item->user?->email ?? '' }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-gray-800">{{ $item->nama_layanan }}</p>
                            <p class="text-xs text-gray-400">{{ $item->durasi_label }}</p>
                        </td>
                        <td class="px-5 py-3.5 font-semibold text-gray-700">
                            {{ $item->harga_format }}
                        </td>
                        <td class="px-5 py-3.5 text-xs text-gray-500">
                            {{ $item->payment_type ? strtoupper(str_replace('_', ' ', $item->payment_type)) : '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            {!! $item->status_badge !!}
                        </td>
                        <td class="px-5 py-3.5 text-xs text-gray-500">
                            {{ $item->created_at->format('d M Y') }}<br>
                            <span class="text-gray-400">{{ $item->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('admin.transaksi.show', $item) }}"
                            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition inline-flex" title="Detail">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Card list — mobile (<sm) --}}
            <div class="sm:hidden divide-y divide-gray-100">
                @foreach($transaksis as $item)
                <a href="{{ route('admin.transaksi.show', $item) }}"
                class="flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 active:bg-gray-100 transition">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-[11px] text-gray-400 truncate">{{ $item->order_id }}</p>
                        <p class="font-medium text-gray-800 text-sm truncate mt-0.5">{{ $item->user?->name ?? '—' }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $item->user?->email ?? '' }}</p>
                        <div class="flex items-center justify-between gap-2 mt-1.5">
                            <p class="text-xs text-gray-600 truncate">{{ $item->nama_layanan }}</p>
                            <span class="text-sm font-semibold text-gray-700 flex-shrink-0">{{ $item->harga_format }}</span>
                        </div>
                        <div class="mt-1.5">{!! $item->status_badge !!}</div>
                    </div>
                    <i class="fas fa-chevron-right text-gray-300 text-xs flex-shrink-0"></i>
                </a>
                @endforeach
            </div>

            @endif
        </div>

        {{-- Pagination --}}
        @if($transaksis->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-sm text-gray-500">
            <span class="text-xs sm:text-sm text-center sm:text-left">
                Menampilkan {{ $transaksis->firstItem() }}–{{ $transaksis->lastItem() }} dari {{ $transaksis->total() }} data
            </span>

            @php
                $current = $transaksis->currentPage();
                $last    = $transaksis->lastPage();
                $delta   = 1;
                $range   = [];
                $pages   = [];
                $l       = null;

                for ($i = 1; $i <= $last; $i++) {
                    if ($i === 1 || $i === $last || ($i >= $current - $delta && $i <= $current + $delta)) {
                        $range[] = $i;
                    }
                }

                foreach ($range as $i) {
                    if ($l) {
                        if ($i - $l === 2) {
                            $pages[] = $l + 1;
                        } elseif ($i - $l > 2) {
                            $pages[] = '...';
                        }
                    }
                    $pages[] = $i;
                    $l = $i;
                }
            @endphp

            <nav class="flex items-center justify-center sm:justify-end gap-1.5">
                @if($transaksis->onFirstPage())
                    <span class="w-9 h-9 rounded-full border border-gray-200 text-gray-300 flex items-center justify-center cursor-not-allowed">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </span>
                @else
                    <a href="{{ $transaksis->previousPageUrl() }}"
                    class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                @endif

                @foreach($pages as $page)
                    @if($page === '...')
                        <span class="w-9 h-9 flex items-center justify-center text-gray-400 text-sm">…</span>
                    @elseif($page == $current)
                        <span class="w-9 h-9 rounded-full bg-gray-900 text-white text-sm font-semibold flex items-center justify-center">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $transaksis->url($page) }}"
                        class="w-9 h-9 rounded-full border border-gray-200 text-gray-600 text-sm flex items-center justify-center hover:bg-gray-50 transition">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                @if($transaksis->hasMorePages())
                    <a href="{{ $transaksis->nextPageUrl() }}"
                    class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                @else
                    <span class="w-9 h-9 rounded-full border border-gray-200 text-gray-300 flex items-center justify-center cursor-not-allowed">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </span>
                @endif
            </nav>
        </div>
        @endif
    </div>
</div>
@endsection