<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Data Series</title>
    <meta name="description" content="Jelajahi seluruh data yang tersedia di Pusat Data Indonesia Bali. Filter berdasarkan klasifikasi, frekuensi, dan kata kunci."/>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-gray-900 antialiased">

    @include('pages.landing.components.navbar')

    {{-- ── Page Header ──────────────────────────────────────────────────────── --}}
    <div class="bg-[#001734] pt-24 pb-10 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" aria-hidden="true">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="100%" fill="url(#ds-grid)"/>
            </svg>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-white/40 mb-6" aria-label="Breadcrumb">
                <a href="{{ route('landing') }}" class="hover:text-[#F7C100] transition-colors">Beranda</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-white/70">Data Series</span>
            </nav>
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black font-poppins text-white leading-tight mb-2">
                        Data Series
                    </h1>
                    <p class="text-white/50 text-base max-w-xl">
                        Jelajahi seluruh data yang tersedia. Klik data untuk melihat detail dan grafik.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Content ──────────────────────────────────────────────────────── --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- ── Filter Bar ────────────────────────────────────────────────── --}}
        <form id="filter-form" method="GET" action="{{ route('landing.data.series') }}">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-col sm:flex-row gap-3">

                    {{-- Search --}}
                    <div class="relative flex-1 min-w-0">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                        </svg>
                        <input
                            id="search-input"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Cari data..."
                            autocomplete="off"
                            class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-gray-200 text-sm
                                text-gray-800 placeholder-gray-400 outline-none
                                focus:border-[#001734] focus:ring-2 focus:ring-[#001734]/10 transition-colors"
                        >
                        @if(request('q'))
                            <button type="button" id="clear-search"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Klasifikasi (TomSelect) --}}
                    <div class="w-full sm:w-56 shrink-0">
                        <select id="select-klasifikasi" name="klasifikasi"
                                class="w-full text-sm border border-gray-200 rounded-xl">
                            <option value="">Semua Klasifikasi</option>
                            @foreach($klasifikasiList as $kl)
                                <option value="{{ $kl }}" {{ request('klasifikasi') === $kl ? 'selected' : '' }}>
                                    {{ $kl }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Active filter chips --}}
                @if(request('q') || request('klasifikasi'))
                    <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100 overflow-x-auto">
                        <span class="text-xs text-gray-400 flex-shrink-0">Filter aktif:</span>
                        @if(request('q'))
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                                        bg-[#001734]/5 border border-[#001734]/10 text-xs font-semibold text-[#001734]">
                                "{{ request('q') }}"
                                <a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}"
                                class="hover:text-red-500 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            </span>
                        @endif
                        @if(request('klasifikasi'))
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                                        bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-700">
                                {{ request('klasifikasi') }}
                                <a href="{{ request()->fullUrlWithQuery(['klasifikasi' => null, 'page' => null]) }}"
                                class="hover:text-red-500 transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            </span>
                        @endif
                        <a href="{{ route('landing.data.series') }}"
                        class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1 ml-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset semua
                        </a>
                    </div>
                @endif
            </div>
        </form>

        {{-- ── Rekomendasi Data Gratis (CTA produk untuk pelanggan baru) ──────
             Tampil hanya untuk user yang dibatasi (belum berlangganan),
             maksimal 6 data, masing-masing dari rujukan/sumber berbeda. ── --}}
        @if($isLimited && $rekomendasiGratis->isNotEmpty())
            <div class="mb-10">
                <div class="flex items-end justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-[#001734] flex items-center gap-2">
                            Rekomendasi Data Tidak Berbayar
                        </h2>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($rekomendasiGratis as $meta)
                        @php
                            $range     = $yearRanges[$meta->metadata_id] ?? null;
                            $startYear = $range ? (int) explode('-', $range)[0] : (now()->year - 5);
                            $lastYear  = $range ? (int) explode('-', $range)[1] : (now()->year - 1);
                            $years     = range($startYear, $lastYear);

                            $dataPoints = $meta->data()
                                ->where('data.status', 1)
                                ->join('time', 'data.time_id', '=', 'time.time_id')
                                ->whereBetween('time.year', [$startYear, $lastYear])
                                ->groupBy('time.year')
                                ->orderBy('time.year')
                                ->selectRaw('time.year, SUM(data.number_value) as nilai')
                                ->pluck('nilai', 'year');

                            $vals = collect($years)
                                ->map(fn($y) => isset($dataPoints[$y]) ? (float) $dataPoints[$y] : null)
                                ->filter()->values();

                            $minV = 0;
                            $maxV = $vals->max() ?: 1;
                            $n    = $vals->count();

                            $pts = $vals->map(function ($v, $idx) use ($n, $minV, $maxV) {
                                $x = ($idx / max($n - 1, 1)) * 300;
                                $y = 84 - (($v - $minV) / max($maxV - $minV, 1)) * 72;
                                return [round($x, 1), round($y, 1)];
                            });

                            $polyLine = $pts->map(fn($p) => "{$p[0]},{$p[1]}")->implode(' ');

                            $firstVal = $vals->first() ?: 1;
                            $lastVal  = $vals->last()  ?: 1;
                            $diff     = $lastVal - $firstVal;
                            $pct      = round(abs($diff / max(abs($firstVal), 1)) * 100, 1);
                            $trend    = $diff >= 0 ? 'up' : 'down';
                            $lastPt   = $pts->last();

                            $namaKlasifikasi = $meta->klasifikasi?->nama_klasifikasi ?? '—';
                            $wilayah = $meta->data->where('location_id', 0)->first()?->location?->nama_wilayah;
                        @endphp

                        <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                            <div class="relative px-5 pt-5 pb-2 h-36 overflow-hidden" style="background:#0B2A52;">
                                <span class="absolute top-3 left-4 px-2 py-0.5 rounded-full bg-[#F7C100] text-[#001734] text-[10px] font-extrabold tracking-wide z-10">
                                    Tidak Berbayar
                                </span>
                                <span class="absolute top-3 right-4 text-xs font-semibold" style="color:rgba(247,0,0,0.7);">
                                    {{ $meta->satuan_data }}
                                </span>
                                <svg viewBox="0 0 300 90" class="w-full h-20 mt-1" preserveAspectRatio="none" aria-hidden="true">
                                    @foreach([25, 50, 75] as $gy)
                                        <line x1="0" y1="{{ $gy }}" x2="300" y2="{{ $gy }}"
                                            stroke="white" stroke-opacity="0.06" stroke-width="1"/>
                                    @endforeach
                                    @if($n >= 2)
                                        <polyline points="{{ $polyLine }}" fill="none" stroke="#E63946"
                                                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        @if($lastPt)
                                            <circle cx="{{ $lastPt[0] }}" cy="{{ $lastPt[1] }}" r="3.5" fill="#E63946"/>
                                        @endif
                                    @else
                                        <text x="150" y="48" text-anchor="middle"
                                            font-size="11" fill="rgba(255,255,255,0.30)">Belum ada data</text>
                                    @endif
                                </svg>
                                <div class="flex justify-between px-0.5 mt-1">
                                    @foreach($years as $y)
                                        <span class="text-white/35" style="font-size:10px;">{{ $y }}</span>
                                    @endforeach
                                </div>
                                @if($n >= 2)
                                    <div class="absolute bottom-3 right-4 flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold
                                                {{ $trend === 'up' ? 'bg-blue-500/20 text-blue-300' : 'bg-red-500/20 text-red-300' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            @if($trend === 'up')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17l5-5m0 0l-5-5m5 5H6"/>
                                            @endif
                                        </svg>
                                        {{ $trend === 'up' ? '+' : '-' }}{{ $pct }}%
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <h3 class="text-sm font-bold text-stikom leading-snug line-clamp-2 group-hover:text-stikom-accent transition-colors duration-200">
                                        {{ $meta->nama }}
                                    </h3>
                                    <span class="shrink-0 px-2 py-0.5 rounded-full bg-stikom-red/20 text-stikom-red text-xs font-semibold">
                                        {{ $meta->frekuensi_penerbitan }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-0.5 rounded-full bg-stikom-red/20 text-stikom-red text-xs font-semibold max-w-[140px] truncate">
                                        {{ $namaKlasifikasi }}
                                    </span>
                                    <span class="text-gray-400 text-xs whitespace-nowrap">sejak {{ $startYear }}</span>
                                </div>
                                @if($wilayah)
                                    <div class="flex items-center gap-1 mb-3">
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="text-xs text-gray-400 truncate">{{ $wilayah }}</span>
                                    </div>
                                @endif
                                @php
                                    $firstData    = $meta->data->where('location_id', 0)->first();
                                    $namaProdusen = $firstData?->rujukan?->produsen?->nama_produsen
                                                    ?? $meta->produsen?->nama_produsen
                                                    ?? $meta->produsen?->nama
                                                    ?? null;
                                @endphp
                                @if($namaProdusen)
                                    <div class="flex items-center gap-1 mb-2">
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span class="text-xs text-gray-400 truncate">{{ $namaProdusen }}</span>
                                    </div>
                                @endif
                                <div class="border-t border-gray-50 pt-4">
                                    <a href="{{ route('landing.data.show', $meta->metadata_id) }}"
                                    class="flex items-center gap-1 text-xs font-bold text-stikom hover:text-stikom-accent transition-colors">
                                        Lihat Detail
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Result info ──────────────────────────────────────────────── --}}
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-lg font-bold text-[#001734]">Semua Data</h2>
        </div>
        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-gray-500">
                Menampilkan
                <span class="font-bold text-[#001734]">{{ $metadataList->firstItem() }}–{{ $metadataList->lastItem() }}</span>
                dari
                <span class="font-bold text-[#001734]">{{ number_format($metadataList->total()) }}</span>
                data
                @if(request('q'))<span> untuk "<span class="font-semibold text-[#001734]">{{ request('q') }}</span>"</span>@endif
            </p>
        </div>

        {{-- ── Cards ──────────────────────────────────────────────────────── --}}
        @if($metadataList->isEmpty())
            <div class="text-center py-24">
                <h3 class="text-xl font-bold text-gray-300 mb-2">Belum ada data yang tersedia</h3>
            </div>
        @else

            {{-- ── Grid + List wrapper ──────────────────────────────────────── --}}
            <div x-data="{ view: localStorage.getItem('ds_view') || 'grid' }"
                 x-init="window.addEventListener('storage', () => { view = localStorage.getItem('ds_view') || 'grid'; })">

                {{-- ── GRID VIEW ── --}}
                <div x-show="view === 'grid'" class="relative">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($metadataList as $i => $meta)
                            @php
                                $isLocked = $isLimited && !in_array($meta->metadata_id, $freeIds);

                                $range     = $yearRanges[$meta->metadata_id] ?? null;
                                $startYear = $range ? (int) explode('-', $range)[0] : (now()->year - 5);
                                $lastYear  = $range ? (int) explode('-', $range)[1] : (now()->year - 1);
                                $years     = range($startYear, $lastYear);

                                if (!$isLocked) {
                                    $dataPoints = $meta->data()
                                        ->where('data.status', 1)
                                        ->join('time', 'data.time_id', '=', 'time.time_id')
                                        ->whereBetween('time.year', [$startYear, $lastYear])
                                        ->groupBy('time.year')
                                        ->orderBy('time.year')
                                        ->selectRaw('time.year, SUM(data.number_value) as nilai')
                                        ->pluck('nilai', 'year');

                                    $vals = collect($years)
                                        ->map(fn($y) => isset($dataPoints[$y]) ? (float) $dataPoints[$y] : null)
                                        ->filter()->values();

                                    $minV = 0;
                                    $maxV = $vals->max() ?: 1;
                                    $y = 84 - (($v - $minV) / max($maxV - $minV, 1)) * 72;

                                    $pts = $vals->map(function ($v, $idx) use ($n, $minV, $maxV) {
                                        $x = ($idx / max($n - 1, 1)) * 300;
                                        $y = 84 - (($v - $minV) / max($maxV - $minV, 1)) * 72;
                                        return [round($x, 1), round($y, 1)];
                                    });

                                    $polyLine = $pts->map(fn($p) => "{$p[0]},{$p[1]}")->implode(' ');
                                    $areaPath = 'M0,90 L' . $pts->map(fn($p) => "{$p[0]},{$p[1]}")->implode(' L') . ' L300,90 Z';

                                    $firstVal = $vals->first() ?: 1;
                                    $lastVal  = $vals->last()  ?: 1;
                                    $diff     = $lastVal - $firstVal;
                                    $pct      = round(abs($diff / max(abs($firstVal), 1)) * 100, 1);
                                    $trend    = $diff >= 0 ? 'up' : 'down';
                                    $lastPt   = $pts->last();
                                } else {
                                    $n = 0;
                                }

                                $namaKlasifikasi = $meta->klasifikasi?->nama_klasifikasi ?? $meta->klasifikasi ?? '—';
                            @endphp

                            @if(!$isLocked)
                                {{-- FREE card --}}
                                <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                                    <div class="relative px-5 pt-5 pb-2 h-36 overflow-hidden" style="background:#0B2A52;">
                                        <span class="absolute top-3 right-4 text-xs font-semibold" style="color:rgba(247,0,0,0.7);">
                                            {{ $meta->satuan_data }}
                                        </span>
                                        <svg viewBox="0 0 300 90" class="w-full h-20 mt-1" preserveAspectRatio="none" aria-hidden="true">
                                            @foreach([25, 50, 75] as $gy)
                                                <line x1="0" y1="{{ $gy }}" x2="300" y2="{{ $gy }}"
                                                    stroke="white" stroke-opacity="0.06" stroke-width="1"/>
                                            @endforeach
                                            @if($n >= 2)
                                                <polyline points="{{ $polyLine }}" fill="none" stroke="#E63946"
                                                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                                @if($lastPt)
                                                    <circle cx="{{ $lastPt[0] }}" cy="{{ $lastPt[1] }}" r="3.5" fill="#E63946"/>
                                                @endif
                                            @else
                                                <text x="150" y="48" text-anchor="middle"
                                                    font-size="11" fill="rgba(255,255,255,0.30)">Belum ada data</text>
                                            @endif
                                        </svg>
                                        <div class="flex justify-between px-0.5 mt-1">
                                            @foreach($years as $y)
                                                <span class="text-white/35" style="font-size:10px;">{{ $y }}</span>
                                            @endforeach
                                        </div>
                                        @if($n >= 2)
                                            <div class="absolute bottom-3 right-4 flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold
                                                        {{ $trend === 'up' ? 'bg-blue-500/20 text-blue-300' : 'bg-red-500/20 text-red-300' }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    @if($trend === 'up')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17l5-5m0 0l-5-5m5 5H6"/>
                                                    @endif
                                                </svg>
                                                {{ $trend === 'up' ? '+' : '-' }}{{ $pct }}%
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <h3 class="text-sm font-bold text-stikom leading-snug line-clamp-2 group-hover:text-stikom-accent transition-colors duration-200">
                                                {{ $meta->nama }}
                                            </h3>
                                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-stikom-red/20 text-stikom-red text-xs font-semibold">
                                                {{ $meta->frekuensi_penerbitan }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="px-2 py-0.5 rounded-full bg-stikom-red/20 text-stikom-red text-xs font-semibold max-w-[140px] truncate">
                                                {{ $namaKlasifikasi }}
                                            </span>
                                            <span class="text-gray-400 text-xs whitespace-nowrap">sejak {{ isset($yearRanges[$meta->metadata_id]) ? explode('-', $yearRanges[$meta->metadata_id])[0] : ($meta->tahun_mulai_data ?? '—') }}</span>
                                        </div>
                                        @php $wilayah = $meta->data->where('location_id', 0)->first()?->location?->nama_wilayah; @endphp
                                        @if($wilayah)
                                            <div class="flex items-center gap-1 mb-3">
                                                <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                <span class="text-xs text-gray-400 truncate">{{ $wilayah }}</span>
                                            </div>
                                        @endif
                                        @php
                                            $firstData    = $meta->data->where('location_id', 0)->first();
                                            $namaProdusen = $firstData?->rujukan?->produsen?->nama_produsen
                                                            ?? $meta->produsen?->nama_produsen
                                                            ?? $meta->produsen?->nama
                                                            ?? null;
                                        @endphp
                                        @if($namaProdusen)
                                            <div class="flex items-center gap-1 mb-3">
                                                <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span class="text-xs text-gray-400 truncate">{{ $namaProdusen }}</span>
                                            </div>
                                        @endif
                                        <div class="border-t border-gray-50 pt-4">
                                            <a href="{{ route('landing.data.show', $meta->metadata_id) }}"
                                            class="flex items-center gap-1 text-xs font-bold text-stikom hover:text-stikom-accent transition-colors">
                                                Lihat Detail
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            @else
                                {{-- LOCKED card (Grid) --}}
                                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                    <div class="relative px-5 pt-5 pb-2 h-36 overflow-hidden" style="background:#0B2A52;">
                                        <div class="absolute inset-0 px-5 pt-5 pb-2" style="filter: blur(4px);">
                                            <svg viewBox="0 0 300 90" class="w-full h-20 mt-1" preserveAspectRatio="none" aria-hidden="true">
                                                @foreach([25, 50, 75] as $gy)
                                                    <line x1="0" y1="{{ $gy }}" x2="300" y2="{{ $gy }}"
                                                        stroke="white" stroke-opacity="0.06" stroke-width="1"/>
                                                @endforeach
                                                <polyline points="0,60 60,45 120,55 180,38 240,50 300,42"
                                                        fill="none" stroke="#E63946" stroke-width="2.2"
                                                        stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="300" cy="42" r="3.5" fill="#E63946"/>
                                            </svg>
                                            <div class="flex justify-between px-0.5 mt-1">
                                                @foreach($years as $y)
                                                    <span class="text-white/35" style="font-size:10px;">{{ $y }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="absolute inset-0 bg-[#0B2A52]/70"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="flex flex-col items-center gap-1.5 opacity-60">
                                                <svg class="w-7 h-7 text-[#F7C100]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                                <span class="text-white/50 text-xs font-semibold tracking-wide">Berlangganan</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <h3 class="text-sm font-bold text-stikom leading-snug line-clamp-2">
                                                {{ $meta->nama }}
                                            </h3>
                                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-stikom-red/20 text-stikom-red text-xs font-semibold">
                                                {{ $meta->frekuensi_penerbitan }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="px-2 py-0.5 rounded-full bg-stikom-red/20 text-stikom-red text-xs font-semibold max-w-[140px] truncate">
                                                {{ $namaKlasifikasi }}
                                            </span>
                                            <span class="text-gray-400 text-xs whitespace-nowrap">sejak {{ isset($yearRanges[$meta->metadata_id]) ? explode('-', $yearRanges[$meta->metadata_id])[0] : ($meta->tahun_mulai_data ?? '—') }}</span>
                                        </div>
                                        @php
                                            $firstData    = $meta->data->where('location_id', 0)->first();
                                            $namaProdusen = $firstData?->rujukan?->produsen?->nama_produsen
                                                            ?? $meta->produsen?->nama_produsen
                                                            ?? $meta->produsen?->nama
                                                            ?? null;
                                        @endphp
                                        @if($namaProdusen)
                                            <div class="flex items-center gap-1 mb-2">
                                                <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span class="text-xs text-gray-400 truncate">{{ $namaProdusen }}</span>
                                            </div>
                                        @endif
                                        <div class="border-t border-gray-50 pt-4">
                                            <a href="{{ route('langganan') }}"
                                            class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#001734] text-stikom-accent hover:text-black text-xs font-bold hover:bg-[#F7C100] transition-colors duration-200 w-fit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                                Langganan untuk Akses
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        @endforeach
                    </div>
                </div>{{-- /grid --}}

                {{-- ── LIST VIEW ── --}}
                <div x-show="view === 'list'" class="relative space-y-3">
                    @foreach($metadataList as $i => $meta)
                        @php
                            $isLocked        = $isLimited && !in_array($meta->metadata_id, $freeIds);
                            $namaKlasifikasi = $meta->klasifikasi?->nama_klasifikasi ?? $meta->klasifikasi ?? '—';
                        @endphp

                        @if(!$isLocked)
                            {{-- FREE list row --}}
                            <div class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-[#F7C100]/30 transition-all duration-200 overflow-hidden">
                                <div class="flex items-center gap-4 p-4 sm:p-5">
                                    <div class="w-12 h-12 rounded-xl bg-[#001734] flex items-center justify-center flex-shrink-0 group-hover:bg-[#F7C100] transition-colors duration-300">
                                        <svg class="w-6 h-6 text-[#F7C100] group-hover:text-[#001734] transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <h3 class="text-sm font-bold text-[#001734] group-hover:text-[#F7C100] transition-colors line-clamp-1">
                                                {{ $meta->nama }}
                                            </h3>
                                            <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                                <span class="px-2 py-0.5 rounded-full bg-[#F7C100]/15 text-[#001734] text-xs font-semibold">
                                                    {{ $namaKlasifikasi }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full bg-[#001734]/5 text-[#001734] text-xs font-semibold">
                                                    {{ $meta->frekuensi_penerbitan }}
                                                </span>
                                                <span class="text-gray-400 text-xs">{{ $meta->satuan_data }}</span>
                                                <span class="text-gray-300 text-xs">·</span>
                                                <span class="text-gray-400 text-xs">sejak {{ isset($yearRanges[$meta->metadata_id]) ? explode('-', $yearRanges[$meta->metadata_id])[0] : ($meta->tahun_mulai_data ?? '—') }}</span>
                                                @if($meta->produsen?->nama_produsen)
                                                    <span class="text-gray-300 text-xs">·</span>
                                                    <span class="text-gray-400 text-xs">{{ $meta->produsen->nama_produsen }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a href="{{ route('landing.data.show', $meta->metadata_id) }}"
                                           class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#001734] text-white text-xs font-bold hover:bg-[#002a52] transition-colors">
                                            Detail
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>

                        @else
                            {{-- LOCKED list row --}}
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                                <div class="flex items-center gap-4 p-4 sm:p-5">
                                    <div class="w-12 h-12 rounded-xl bg-[#001734] flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-[#F7C100]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-3 flex-wrap">
                                            <h3 class="text-sm font-bold text-[#001734] line-clamp-1">
                                                {{ $meta->nama }}
                                            </h3>
                                            <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                                <span class="px-2 py-0.5 rounded-full bg-[#F7C100]/15 text-[#001734] text-xs font-semibold">
                                                    {{ $namaKlasifikasi }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full bg-[#001734]/5 text-[#001734] text-xs font-semibold">
                                                    {{ $meta->frekuensi_penerbitan }}
                                                </span>
                                                <span class="text-gray-400 text-xs">{{ $meta->satuan_data }}</span>
                                                <span class="text-gray-300 text-xs">·</span>
                                                <span class="text-gray-400 text-xs">sejak {{ isset($yearRanges[$meta->metadata_id]) ? explode('-', $yearRanges[$meta->metadata_id])[0] : ($meta->tahun_mulai_data ?? '—') }}</span>
                                                @if($meta->produsen?->nama_produsen)
                                                    <span class="text-gray-300 text-xs">·</span>
                                                    <span class="text-gray-400 text-xs">{{ $meta->produsen->nama_produsen }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a href="{{ route('langganan') }}"
                                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#001734] text-stikom-accent hover:text-black text-xs font-bold hover:bg-[#F7C100] transition-colors duration-200">
                                            <svg class="w-3.5 h-3.5 text-[#F7C100]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Langganan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @endforeach
                </div>{{-- /list --}}

            </div>{{-- /x-data view wrapper --}}

            {{-- ── Pagination ── --}}
            @if($metadataList->hasPages())
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-between gap-4">

                    <p class="text-sm text-gray-500 order-2 sm:order-1">
                        Halaman <span class="font-bold text-[#001734]">{{ $metadataList->currentPage() }}</span>
                        dari <span class="font-bold text-[#001734]">{{ $metadataList->lastPage() }}</span>
                    </p>

                    <nav class="flex items-center gap-1 order-1 sm:order-2" aria-label="Paginasi">

                        @if($metadataList->onFirstPage())
                            <span class="w-9 h-9 flex items-center justify-center rounded-xl text-gray-300 cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </span>
                        @else
                            <a href="{{ $metadataList->previousPageUrl() }}"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:border-[#001734] hover:text-[#001734] transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </a>
                        @endif

                        @php
                            $window  = 2;
                            $current = $metadataList->currentPage();
                            $last    = $metadataList->lastPage();
                            $start   = max(1, $current - $window);
                            $end     = min($last, $current + $window);
                        @endphp

                        @if($start > 1)
                            <a href="{{ $metadataList->url(1) }}"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-[#001734] hover:text-[#001734] transition-colors">1</a>
                            @if($start > 2)
                                <span class="w-9 h-9 flex items-center justify-center text-gray-400 text-sm">…</span>
                            @endif
                        @endif

                        @for($p = $start; $p <= $end; $p++)
                            @if($p === $current)
                                <span class="w-9 h-9 flex items-center justify-center rounded-xl bg-[#001734] text-white text-sm font-bold">{{ $p }}</span>
                            @else
                                <a href="{{ $metadataList->url($p) }}"
                                class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-[#001734] hover:text-[#001734] transition-colors">{{ $p }}</a>
                            @endif
                        @endfor

                        @if($end < $last)
                            @if($end < $last - 1)
                                <span class="w-9 h-9 flex items-center justify-center text-gray-400 text-sm">…</span>
                            @endif
                            <a href="{{ $metadataList->url($last) }}"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-[#001734] hover:text-[#001734] transition-colors">{{ $last }}</a>
                        @endif

                        @if($metadataList->hasMorePages())
                            <a href="{{ $metadataList->nextPageUrl() }}"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:border-[#001734] hover:text-[#001734] transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @else
                            <span class="w-9 h-9 flex items-center justify-center rounded-xl text-gray-300 cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        @endif

                    </nav>

                    <div class="flex items-center gap-2 order-3 text-sm text-gray-500">
                        <span>Tampilkan</span>
                        <select onchange="window.location.href = '{{ route('landing.data.series') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1}).toString()"
                                class="px-3 py-1.5 rounded-xl border border-gray-200 text-sm text-gray-700 outline-none focus:border-[#001734] bg-white cursor-pointer">
                            @foreach([12, 24, 48] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 12) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                        <span>per halaman</span>
                    </div>

                </div>
            @endif

        @endif

    </main>

    @include('pages.landing.components.footer')

    {{-- Back to top --}}
    <button id="back-to-top"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="fixed bottom-6 right-6 z-50 w-12 h-12 rounded-2xl bg-[#001734] text-[#F7C100] shadow-xl flex items-center justify-center opacity-0 translate-y-4 pointer-events-none transition-all duration-300 hover:bg-[#002a52] hover:scale-110 focus:outline-none focus:ring-2 focus:ring-[#F7C100] focus:ring-offset-2"
            aria-label="Kembali ke atas">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form        = document.getElementById('filter-form');
        const searchInput = document.getElementById('search-input');
        const clearBtn    = document.getElementById('clear-search');

        let debounceTimer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => form.submit(), 600);
        });

        clearBtn?.addEventListener('click', () => {
            searchInput.value = '';
            form.submit();
        });

        if (document.getElementById('select-klasifikasi')) {
            new TomSelect('#select-klasifikasi', {
                allowEmptyOption: true,
                placeholder: 'Semua Klasifikasi',
                onChange() { form.submit(); },
            });
        }
    });

    (function () {
        const btn = document.getElementById('back-to-top');
        window.addEventListener('scroll', () => {
            const past = window.scrollY > 300;
            btn.classList.toggle('opacity-0',           !past);
            btn.classList.toggle('translate-y-4',       !past);
            btn.classList.toggle('pointer-events-none', !past);
            btn.classList.toggle('opacity-100',          past);
            btn.classList.toggle('translate-y-0',        past);
        }, { passive: true });
    })();
    </script>

</body>
</html>