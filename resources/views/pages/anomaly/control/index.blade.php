@extends('layouts.main')

@section('content')
<div class="py-6 px-4 space-y-5">

    {{-- ── HEADER ──────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Control Data Anomali</h1>
            <p class="text-xs text-gray-400 mt-0.5">Review dan kelola anomali yang terdeteksi sistem</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openScanModal()"
                    class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg btn-primary">
                <i class="fas fa-chart-bar"></i> Scan Data
            </button>
            <a href="{{ route('anomaly.control.rules') }}"
               class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-gray-200
                      text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="fas fa-sliders-h"></i> Atur Threshold
            </a>
            <a href="{{ route('data.approval') }}"
               class="flex items-center gap-1.5 text-xs px-3 py-2 rounded-lg border border-gray-200
                      text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="fas fa-database"></i> Ke Data
            </a>
        </div>
    </div>

    {{-- ── ALERT SUCCESS / WARNING ─────────────────────────────── --}}
    @if(session('success'))
        <div class="flex items-start gap-3 px-4 py-3 rounded-lg text-sm bg-green-50
                    border border-green-200 text-green-800">
            <i class="fas fa-check-circle mt-0.5 shrink-0 text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="flex items-start gap-3 px-4 py-3 rounded-lg text-sm bg-amber-50
                    border border-amber-200 text-amber-800">
            <i class="fas fa-exclamation-triangle mt-0.5 shrink-0 text-amber-500"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    {{-- ── STATS CARDS ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        @php
        $statCards = [
            ['label'=>'Warning',        'value'=>$stats['total_warning'],      'icon'=>'fas fa-exclamation-triangle', 'style'=>'background:#fef9c3; color:#a16207;', 'border'=>'border-amber-200'],
            ['label'=>'Resolved',       'value'=>$stats['total_resolved'],     'icon'=>'fas fa-check-circle',         'style'=>'background:#dcfce7; color:#15803d;', 'border'=>'border-green-200'],
        ];
        @endphp

        @foreach($statCards as $card)
        <div class="rounded-xl border {{ $card['border'] }} p-4 flex items-center gap-3"
             style="{{ $card['style'] }}">
            <i class="{{ $card['icon'] }} text-lg opacity-70"></i>
            <div>
                <p class="text-xl font-bold leading-none">{{ number_format($card['value']) }}</p>
                <p class="text-xs mt-0.5 opacity-75">{{ $card['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>


    {{-- ── FILTER BAR ───────────────────────────────────────────── --}}
    <form id="anomaly-filter-form" method="GET" action="{{ route('anomaly.control.index') }}"
        class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">

            {{-- Search --}}
            <div class="col-span-2 md:col-span-1">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Cari</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2
                            text-gray-400 text-xs pointer-events-none"></i>
                    <input type="text" name="search" id="anomaly-search"
                        value="{{ request('search') }}"
                        placeholder="Metadata, lokasi…"
                        class="w-full pl-7 pr-7 py-2 text-xs border border-gray-200 rounded-lg
                                focus:outline-none focus:ring-2 focus:ring-sky-400">
                    @if(request('search'))
                        <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-400 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Severity --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Severity</label>
                <select name="severity" onchange="this.form.submit()"
                        class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-2
                            focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <option value="">Semua</option>
                    @foreach($severityOpts as $val => $label)
                        <option value="{{ $val }}" {{ request('severity')===$val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                        class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-2
                            focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <option value="">Semua status</option>
                    @foreach($statusOpts as $val => $label)
                        <option value="{{ $val }}" {{ request('status')===$val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tipe --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Tipe</label>
                <select name="anomaly_type" onchange="this.form.submit()"
                        class="w-full text-xs border border-gray-200 rounded-lg px-2.5 py-2
                            focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <option value="">Semua</option>
                    @foreach($typeOpts as $val => $label)
                        <option value="{{ $val }}" {{ request('anomaly_type')===$val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Active chips --}}
        @if(request()->hasAny(['search','severity','status','anomaly_type']))
            <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                <span class="text-xs text-gray-400">Filter aktif:</span>
                @if(request('severity'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-xs font-medium text-amber-700">
                        {{ $severityOpts[request('severity')] ?? request('severity') }}
                        <a href="{{ request()->fullUrlWithQuery(['severity' => null, 'page' => null]) }}" class="hover:text-red-500 transition-colors"><i class="fas fa-times text-[10px]"></i></a>
                    </span>
                @endif
                @if(request('status'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-sky-50 border border-sky-200 text-xs font-medium text-sky-700">
                        {{ $statusOpts[request('status')] ?? request('status') }}
                        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="hover:text-red-500 transition-colors"><i class="fas fa-times text-[10px]"></i></a>
                    </span>
                @endif
                @if(request('anomaly_type'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-xs font-medium text-purple-700">
                        {{ $typeOpts[request('anomaly_type')] ?? request('anomaly_type') }}
                        <a href="{{ request()->fullUrlWithQuery(['anomaly_type' => null, 'page' => null]) }}" class="hover:text-red-500 transition-colors"><i class="fas fa-times text-[10px]"></i></a>
                    </span>
                @endif
                @if(request('search'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 border border-gray-200 text-xs font-medium text-gray-600">
                        "{{ request('search') }}"
                        <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}" class="hover:text-red-500 transition-colors"><i class="fas fa-times text-[10px]"></i></a>
                    </span>
                @endif
                <a href="{{ route('anomaly.control.index') }}"
                class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1 ml-1">
                    <i class="fas fa-times-circle"></i> Reset semua
                </a>
            </div>
        @endif
    </form>

    {{-- ── BULK ACTION BAR ──────────────────────────────────────── --}}
    <form id="bulkForm" method="POST" action="{{ route('anomaly.control.bulk_review') }}">
        @csrf
        <div id="bulkBar"
             class="hidden items-center gap-3 px-4 py-2.5 bg-sky-50 border border-sky-200
                    rounded-xl text-sm text-sky-800">
            <span id="bulkCount" class="font-semibold"></span>
            <span>anomali dipilih</span>
            <div class="flex-1"></div>
            <select name="decision"
                    class="text-xs border border-sky-300 rounded-lg px-2.5 py-1.5
                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <option value="approved">Setujui semua</option>
                <option value="approved_with_note">Setujui dengan catatan</option>
                <option value="rejected">Tolak semua</option>
            </select>
            <input type="text" name="justification" placeholder="Justification (jika perlu)…"
                   class="text-xs border border-sky-300 rounded-lg px-2.5 py-1.5 w-56
                          focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
            <button type="submit"
                    class="text-xs bg-sky-600 hover:bg-sky-700 text-white
                           px-4 py-1.5 rounded-lg font-semibold transition-colors">
                Proses
            </button>
        </div>

        {{-- ── TABEL ANOMALI ────────────────────────────────────── --}}
        <div class="grid grid-cols-3 bg-white rounded-xl border border-gray-200 overflow-hidden mt-3">
            {{-- Wrapper scrollable horizontal --}}
            <div class="overflow-x-auto col-span-3">
                <table class="w-full text-xs" style="min-width: 1100px;">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold">
                            <th class="px-3 py-3 text-center sticky left-0 bg-gray-50">#</th>
                            <th class="px-3 py-3 text-left sticky left-0 bg-gray-50">Saverity</th>
                            <th class="px-3 py-3 text-left min-w-40">Metadata / Lokasi</th>
                            <th class="px-3 py-3 text-left min-w-32.5">Tipe Anomali</th>
                            {{-- Kolom kontekstual: berisi info berbeda per tipe anomali --}}
                            <th class="px-3 py-3 text-center min-w-35">Nilai Saat Ini</th>
                            <th class="px-3 py-3 text-center min-w-35">Nilai Referensi</th>
                            <th class="px-3 py-3 text-center min-w-45">Selisih / Z-score</th>
                            {{-- Kolom khusus source_conflict: produsen-produsen yang konflik --}}
                            <th class="px-3 py-3 text-left min-w-60">Detail Perbandingan</th>
                            <th class="px-3 py-3 text-left min-w-25">Status</th>
                            <th class="px-3 py-3 text-left min-w-21.25">Terdeteksi</th>
                            <th class="px-3 py-3 text-center min-w-17.5 sticky right-0 bg-gray-50 z-10">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($anomalies as $anomaly)
                        @php
                            $data    = $anomaly->data;
                            $ctxType = $anomaly->_ctx_type ?? 'other';
                            $stats   = $anomaly->_ctx_stats ?? [];
                            $sources = $anomaly->_ctx_sources ?? [];
        
                            // Warna baris per severity
                            $rowBg = match($anomaly->severity) {
                                default    => '',
                            };
        
                            // Format angka helper
                            $fmtNum = fn($v) => $v !== null ? number_format((float)$v, 2) : '—';
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors {{ $rowBg }}">
        
                            {{-- # --}}
                            <td class="px-3 py-3 text-center text-gray-400 sticky left-0 {{ $rowBg ?: 'bg-white' }} z-10">
                                {{ $anomalies->firstItem() + $loop->index }}
                            </td>
        
                            {{-- Severity --}}
                            <td class="px-3 py-3 sticky left-0 {{ $rowBg ?: 'bg-white' }} z-10">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                    style="{{ $anomaly->severity_style }}">
                                    @if($anomaly->severity === 'critical')    <i class="fas fa-fire text-[10px]"></i>
                                    @elseif($anomaly->severity === 'high')    <i class="fas fa-arrow-up text-[10px]"></i>
                                    @elseif($anomaly->severity === 'medium')  <i class="fas fa-minus text-[10px]"></i>
                                    @else                                     <i class="fas fa-info text-[10px]"></i>
                                    @endif
                                    {{ $anomaly->severity_label }}
                                </span>
                            </td>
        
                            {{-- Metadata / Lokasi --}}
                            <td class="px-3 py-3">
                                <p class="font-semibold text-gray-800 truncate max-w-38.75">
                                    {{ $data?->metadata?->nama ?? '-' }}
                                </p>
                                <p class="text-gray-400 truncate max-w-38.75 mt-0.5">
                                    {{ $data?->location?->nama_wilayah ?? '-' }}
                                </p>
                                @if($data?->time)
                                <p class="text-gray-300 mt-0.5">
                                    {{ $data->time->year }}{{ $data->time->month ? '/Bln-'.$data->time->month : '' }}{{ $data->time->quarter ? '/Q'.$data->time->quarter : '' }}
                                </p>
                                @endif
                            </td>
        
                            {{-- Tipe Anomali --}}
                            <td class="px-3 py-3">
                                @php
                                    [$tipeIcon, $tipeBg, $tipeTxt] = match($anomaly->anomaly_type) {
                                        'extreme_increase' => ['fas fa-arrow-trend-up',  'bg-red-50 text-red-700 border-red-200',      'Kenaikan Ekstrem'],
                                        'extreme_decrease' => ['fas fa-arrow-trend-down','bg-blue-50 text-blue-700 border-blue-200',   'Penurunan Ekstrem'],
                                        'source_conflict'  => ['fas fa-code-branch',     'bg-purple-50 text-purple-700 border-purple-200','Konflik Sumber'],
                                        'unreasonable_value'=> ['fas fa-chart-line',     'bg-amber-50 text-amber-700 border-amber-200','Nilai Tdk Wajar'],
                                        'unit_conflict'    => ['fas fa-scale-unbalanced', 'bg-pink-50 text-pink-700 border-pink-200', 'Konflik Satuan'],
                                        default            => ['fas fa-question',        'bg-gray-100 text-gray-500 border-gray-200',  $anomaly->anomaly_type_label],
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border text-[11px] font-medium {{ $tipeBg }}">
                                    <i class="{{ $tipeIcon }} text-[10px]"></i>
                                    {{ $tipeTxt }}
                                </span>
                                {{-- Produsen saat ini (untuk context) --}}
                                @if($data?->produsen_display)
                                <p class="text-gray-400 mt-1 text-[11px]">
                                    <i class="fas fa-building text-[9px] mr-0.5"></i>{{ $data->produsen_display }}
                                </p>
                                @endif
                            </td>
        
                            {{-- Nilai Saat Ini --}}
                            <td class="px-3 py-3 text-center">
                                @if($ctxType !== 'unit_conflict' && $anomaly->_ctx_curr_value !== null)
                                    <span class="font-mono font-semibold text-gray-800 text-sm">
                                        {{ number_format((float)$anomaly->_ctx_curr_value, 2) }}
                                    </span>
                                    @if($data?->metadata?->satuan_data)
                                    <p class="text-gray-400 text-[11px] mt-0.5">{{ $data->metadata->satuan_data }}</p>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
        
                            {{-- Nilai Referensi --}}
                            <td class="px-3 py-3 text-center">
                                @if($anomaly->_ctx_ref_value !== null)
                                    <span class="font-mono text-gray-600 text-sm">
                                        {{ number_format((float)$anomaly->_ctx_ref_value, 2) }}
                                    </span>
                                    <p class="text-gray-400 text-[11px] mt-0.5">
                                        {{ $anomaly->_ctx_ref_label ?? '—' }}
                                    </p>
                                    {{-- Untuk unreasonable: tampilkan batas atas/bawah --}}
                                    @if($ctxType === 'unreasonable' && !empty($stats))
                                    <p class="text-[10px] text-gray-400 mt-1 font-mono">
                                        [{{ number_format((float)($stats['lower'] ?? 0), 2) }}
                                        –
                                        {{ number_format((float)($stats['upper'] ?? 0), 2) }}]
                                    </p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">batas ±3σ</p>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
        
                            {{-- Selisih / Z-score --}}
                            <td class="px-3 py-3 text-center">
                                @if($anomaly->_ctx_change_pct !== null)
                                    @php
                                        $pct = (float) $anomaly->_ctx_change_pct;
                                        $isUnreasonable = $ctxType === 'unreasonable';
                                        $isNeg = $pct < 0;
                                        $pctColor = $isUnreasonable
                                            ? 'text-amber-600'
                                            : ($isNeg ? 'text-blue-600' : 'text-red-600');
                                    @endphp
                                    <span class="font-mono font-bold text-sm {{ $pctColor }}">
                                        @if($isUnreasonable)
                                        @php
                                            $z        = abs((float)($anomaly->_ctx_change_pct ?? 0));
                                            $mean     = (float)($stats['mean'] ?? 0);
                                            $stddev   = (float)($stats['stddev'] ?? 0);
                                            $curr     = (float)($anomaly->_ctx_curr_value ?? 0);
                                            $lower2s  = $mean - 2 * $stddev;
                                            $upper2s  = $mean + 2 * $stddev;
                                            $lower3s  = (float)($stats['lower'] ?? $mean - 3 * $stddev);
                                            $upper3s  = (float)($stats['upper'] ?? $mean + 3 * $stddev);
                                            $n        = $stats['n'] ?? 0;

                                            // Warna berdasarkan z-score
                                            $zColor = $z >= 10 ? '#b91c1c' : ($z >= 6 ? '#c2410c' : ($z >= 3 ? '#a16207' : '#1d4ed8'));
                                            // Lebar bar capped di 100%
                                            $zPct = min(round((min($z, 20) / 20) * 100), 100);

                                            // Apakah nilai di atas atau di bawah mean
                                            $isAbove = $curr > $mean;
                                        @endphp
                                            {{-- Bar z-score --}}
                                            <div>
                                                <div class="flex justify-between text-gray-500 mb-0.5">
                                                    <span>Z-score</span>
                                                    <span class="font-mono font-bold" style="color:{{ $zColor }}">
                                                        {{ number_format($z, 2) }}
                                                    </span>
                                                </div>
                                                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                    <div class="h-1.5 rounded-full"
                                                        style="width:{{ $zPct }}%; background:{{ $zColor }};"></div>
                                                </div>
                                                <div class="flex justify-between text-gray-300 mt-0.5 text-[10px]">
                                                    <span>0</span><span>normal ≤3</span><span>ekstrem 20</span>
                                                </div>
                                            </div>
                                        @else
                                            {{ $pct >= 0 ? '+' : '' }}{{ number_format($pct, 1) }}%
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
        
                            {{-- Detail Perbandingan (kontekstual) --}}
                            <td class="px-3 py-3">
                                @if($ctxType === 'source_conflict' && !empty($sources))
                                    @php $unitsConsistent = $sources[0]['units_consistent'] ?? true; @endphp
                                    @unless($unitsConsistent)
                                    <div class="mb-1.5 px-2 py-1 rounded text-[10px] font-semibold bg-pink-50 text-pink-700 border border-pink-200">
                                        <i class="fas fa-scale-unbalanced mr-1"></i>Satuan rujukan berbeda antar sumber
                                    </div>
                                    @endunless
                                    <div class="space-y-1">
                                        @foreach($sources as $src)
                                        <div class="flex items-center justify-between gap-2 px-2 py-1 rounded
                                                    {{ $src['is_current'] ? 'bg-purple-50 border border-purple-200' : 'bg-gray-50' }}">
                                            <span class="truncate max-w-25 {{ $src['is_current'] ? 'font-semibold text-purple-800' : 'text-gray-600' }}"
                                                title="{{ $src['rujukan'] }}">
                                                @if($src['is_current'])<i class="fas fa-circle text-[6px] text-purple-500 mr-0.5"></i>@endif
                                                {{ $src['rujukan'] }}
                                                <span class="text-gray-400">({{ $src['satuan'] }})</span>
                                            </span>
                                            <span class="font-mono text-[11px] shrink-0
                                                        {{ $src['pct_diff'] >= 5 ? 'text-amber-600 font-semibold' : 'text-gray-600' }}">
                                                {{ number_format((float)$src['value'], 2) }}
                                                @if($src['pct_diff'] > 0)
                                                <span class="text-gray-400">({{ number_format((float)$src['pct_diff'], 1) }}%)</span>
                                                @endif
                                            </span>
                                        </div>
                                        @endforeach
                                    </div>
        
                                @elseif($ctxType === 'unreasonable' && !empty($stats) && ($stats['n'] ?? 1) > 0)
                                    @php
                                        $z        = abs((float)($anomaly->_ctx_change_pct ?? 0));
                                        $mean     = (float)($stats['mean'] ?? 0);
                                        $stddev   = (float)($stats['stddev'] ?? 0);
                                        $curr     = (float)($anomaly->_ctx_curr_value ?? 0);
                                        $lower2s  = $mean - 2 * $stddev;
                                        $upper2s  = $mean + 2 * $stddev;
                                        $lower3s  = (float)($stats['lower'] ?? $mean - 3 * $stddev);
                                        $upper3s  = (float)($stats['upper'] ?? $mean + 3 * $stddev);
                                        $n        = $stats['n'] ?? 0;

                                        // Warna berdasarkan z-score
                                        $zColor = $z >= 10 ? '#b91c1c' : ($z >= 6 ? '#c2410c' : ($z >= 3 ? '#a16207' : '#1d4ed8'));
                                        // Lebar bar capped di 100%
                                        $zPct = min(round((min($z, 20) / 20) * 100), 100);

                                        // Apakah nilai di atas atau di bawah mean
                                        $isAbove = $curr > $mean;
                                    @endphp

                                    <div class="space-y-2 text-[11px]">

                                        {{-- Rentang normal --}}
                                        <div class="rounded-md px-2 py-1.5 space-y-1"
                                            style="background:#f9fafb; border:1px solid #e5e7eb;">
{{-- 
                                            <div class="flex justify-between">
                                                <span class="text-gray-400">Batas ±2σ</span>
                                                <span class="font-mono text-gray-600">
                                                    {{ number_format($lower2s, 2) }} – {{ number_format($upper2s, 2) }}
                                                </span>
                                            </div> --}}
                                            <div class="flex justify-between">
                                                <span class="text-gray-400">Batas ±3σ</span>
                                                <span class="font-mono text-gray-500">
                                                    {{ number_format($lower3s, 2) }} – {{ number_format($upper3s, 2) }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between border-t border-gray-100 pt-1 mt-1">
                                                <span class="font-semibold" style="color:{{ $zColor }}">
                                                    Nilai ini
                                                    {{ $isAbove ? 'terlalu tinggi ↑' : 'terlalu rendah ↓' }}
                                                </span>
                                                <span class="font-mono font-semibold" style="color:{{ $zColor }}">
                                                    {{ number_format($curr, 2) }}
                                                </span>
                                            </div>
                                        </div>

                                        <p class="text-gray-400 text-[10px]">
                                            μ={{ number_format($mean, 2) }} •
                                            σ={{ number_format($stddev, 2) }}
                                        </p>
                                    </div>

                                @elseif($ctxType === 'unreasonable' && (empty($stats) || ($stats['n'] ?? 1) === 0))
                                    {{-- Tidak ada histori cukup — tampilkan pesan informatif --}}
                                    <div class="text-[11px] space-y-1">
                                        @if(strpos($anomaly->message ?? '', 'ditandai pengguna') !== false)
                                            {{-- Data ditandai manual oleh user saat import --}}
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-blue-700"
                                                style="background:#dbeafe; border:1px solid #93c5fd;">
                                                <i class="fas fa-user-check text-[10px]"></i>
                                                Ditandai pengguna
                                            </span>
                                            <p class="text-gray-400">
                                                Data ditandai sebagai anomali oleh pengguna<br>saat proses import.
                                            </p>
                                        @else
                                            {{-- Auto-detected tapi data historis tidak cukup --}}
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-amber-700"
                                                style="background:#fef9c3; border:1px solid #fde68a;">
                                                <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                                Histori data tidak cukup
                                            </span>
                                            <p class="text-gray-400">
                                                Perlu minimal 3 data historis untuk<br>menghitung rentang normal.
                                            </p>
                                        @endif
                                        <p class="text-gray-500 font-mono">
                                            Nilai saat ini:
                                            <strong>{{ number_format((float)($anomaly->_ctx_curr_value ?? 0), 2) }}</strong>
                                        </p>
                                    </div>
        
                                @elseif($ctxType === 'extreme_change')
                                    {{-- Panah visual perubahan --}}
                                    @php
                                        $prev = $anomaly->_ctx_ref_value;
                                        $curr = $anomaly->_ctx_curr_value;
                                        $isUp = $anomaly->anomaly_type === 'extreme_increase';
                                    @endphp
                                    <div class="flex items-center gap-1.5 text-[11px]">
                                        <span class="font-mono text-gray-500">{{ $fmtNum($prev) }}</span>
                                        <i class="fas fa-arrow-right {{ $isUp ? 'text-red-400' : 'text-blue-400' }} text-[10px]"></i>
                                        <span class="font-mono font-semibold {{ $isUp ? 'text-red-600' : 'text-blue-600' }}">
                                            {{ $fmtNum($curr) }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">vs periode sebelumnya</p>
                                @elseif($ctxType === 'unit_conflict')
                                    <div class="space-y-1 text-[11px]">
                                        <div class="flex items-center justify-between gap-2 px-2 py-1 rounded bg-indigo-50 border border-indigo-100">
                                            <span class="text-indigo-500">Satuan Metadata</span>
                                            <span class="font-semibold text-indigo-700">{{ $stats['satuan_metadata'] ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 px-2 py-1 rounded bg-pink-50 border border-pink-100">
                                            <span class="text-pink-500">Satuan Rujukan</span>
                                            <span class="font-semibold text-pink-700">{{ $stats['satuan_rujukan'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-300 text-[11px]">Lihat detail</span>
                                @endif
                            </td>
        
                            {{-- Status --}}
                            <td class="px-3 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                    style="{{ $anomaly->status_style }}">
                                    {{ $anomaly->status_label }}
                                </span>
                            </td>
        
                            {{-- Terdeteksi --}}
                            <td class="px-3 py-3 text-gray-500 whitespace-nowrap text-[11px]">
                                {{ $anomaly->detected_at?->format('d/m/Y') }}<br>
                                <span class="text-gray-400">{{ $anomaly->detected_at?->format('H:i') }}</span>
                            </td>
        
                            {{-- Aksi --}}
                            <td class="px-3 py-3 text-center sticky right-0 {{ $rowBg ?: 'bg-white' }} z-10">
                                <a href="{{ route('anomaly.control.show', $anomaly->anomalies_id) }}"
                                class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg
                                        border border-sky-200 text-sky-600 hover:bg-sky-50
                                        transition-colors whitespace-nowrap">
                                    <i class="fas fa-eye text-[10px]"></i>Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-gray-400">
                                <i class="fas fa-check-circle text-3xl text-green-300 mb-2 block"></i>
                                Tidak ada anomali
                                {{ request()->hasAny(['severity','status','metadata_id','search','anomaly_type'])
                                    ? 'yang sesuai filter' : 'yang perlu direview' }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        
            {{-- Pagination --}}
            @if($anomalies->hasPages())
            <div class="col-span-3 px-4 py-3 border-t border-gray-100">
                {{ $anomalies->links() }}
            </div>
            @endif
        </div>

    </form>{{-- end bulkForm --}}

</div>{{-- end py-6 --}}

{{-- ══════════════════════════════════════════════════════════
     MODAL REVIEW
══════════════════════════════════════════════════════════ --}}
<div id="reviewModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,0.45);">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <p class="font-bold text-gray-800 text-sm">Review Anomali</p>
                <p id="modalSubtitle" class="text-xs text-gray-400 mt-0.5"></p>
            </div>
            <button onclick="closeReviewModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- Modal body --}}
        <form id="reviewForm" method="POST" class="px-5 py-4 space-y-4">
            @csrf
            @method('POST')

            {{-- Decision --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">Keputusan</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(\App\Models\AnomalyReview::decisionOptions() as $val => $label)
                    <label class="decision-option flex items-center gap-2 px-3 py-2.5 rounded-lg
                                  border border-gray-200 cursor-pointer hover:border-sky-300
                                  transition-colors text-xs font-medium text-gray-700
                                  has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50
                                  has-[:checked]:text-sky-700">
                        <input type="radio" name="decision" value="{{ $val }}"
                               class="text-sky-600 focus:ring-sky-400"
                               onchange="onDecisionChange('{{ $val }}')">
                        @if($val === 'approved') <i class="fas fa-check-circle text-green-500"></i>
                        @elseif($val === 'approved_with_note') <i class="fas fa-check-double text-teal-500"></i>
                        @elseif($val === 'rejected') <i class="fas fa-times-circle text-red-500"></i>
                        @else <i class="fas fa-redo text-purple-500"></i>
                        @endif
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Justification (conditional) --}}
            <div id="justificationWrap">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                    Justification
                    <span id="justificationRequired"
                          class="hidden text-red-500 ml-1">* Wajib</span>
                </label>
                <textarea name="justification" id="justificationInput" rows="3"
                          placeholder="Tuliskan alasan keputusan…"
                          class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2
                                 focus:outline-none focus:ring-2 focus:ring-sky-400
                                 resize-none placeholder-gray-400"></textarea>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                    Catatan Tambahan
                    <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <input type="text" name="notes"
                       placeholder="Catatan singkat…"
                       class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2
                              focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            {{-- Submit --}}
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="closeReviewModal()"
                        class="flex-1 text-xs border border-gray-200 text-gray-500
                               hover:bg-gray-50 py-2.5 rounded-lg transition-colors font-semibold">
                    Batal
                </button>
                <button type="submit" id="submitReviewBtn"
                        class="flex-1 text-xs bg-sky-600 hover:bg-sky-700 text-white
                               py-2.5 rounded-lg transition-colors font-semibold
                               disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                    <i class="fas fa-gavel mr-1"></i>Proses
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL SCAN ALL --}}
<div id="scanModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,0.45);">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <p class="font-bold text-gray-800 text-sm">
                    <i class="fas fa-search text-emerald-600 mr-1.5"></i>Scan Anomali
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Scan seluruh data historis di database</p>
            </div>
            <button onclick="closeScanModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Scan akan memeriksa <strong>semua data</strong> dan mendeteksi anomali baru.
                Proses ini mungkin memakan waktu tergantung jumlah data.
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                    Filter Metadata <span class="text-gray-400 font-normal">(opsional)</span>
                </label>

                <select id="scanMetadataId"
                        placeholder="Pilih metadata..."
                        class="w-full text-xs">
                    <option value="">Semua metadata</option>

                    @foreach($metadataList as $m)
                        <option value="{{ $m->metadata_id }}">
                            {{ $m->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                    Jenis Scan
                </label>
                <select id="scanType"
                        class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <option value="all">Semua jenis anomali</option>
                    <option value="extreme">Kenaikan / Penurunan Ekstrem</option>
                    <option value="unreasonable">Nilai Tidak Wajar</option>
                    <option value="source_conflict">Konflik Sumber Data</option>
                </select>
            </div>

            {{-- Progress --}}
            <div id="scanProgress" class="hidden">
                <div class="flex items-center gap-2 text-xs text-emerald-700 bg-emerald-50
                            border border-emerald-200 rounded-lg px-3 py-2.5">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span id="scanProgressText">Sedang scanning…</span>
                </div>
            </div>

            {{-- Result --}}
            <div id="scanResult" class="hidden text-xs"></div>

            <div class="flex gap-2">
                <button type="button" onclick="closeScanModal()"
                        class="flex-1 text-xs border border-gray-200 text-gray-500
                               hover:bg-gray-50 py-2.5 rounded-lg font-semibold">
                    Tutup
                </button>
                <button type="button" onclick="doScan()" id="scanBtn"
                        class="flex-1 text-xs bg-emerald-600 hover:bg-emerald-700 text-white
                               py-2.5 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-play mr-1"></i>Mulai Scan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<script>
    new TomSelect('#scanMetadataId', {
        create: false,
        allowEmptyOption: true,
        placeholder: 'Pilih metadata...',
        maxOptions: 500,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
</script>

<script>
// ── TREND CHART ─────────────────────────────────────────────
let trendChart = null;
const TREND_URL = '{{ route("anomaly.control.trend_stats") }}';

async function loadTrend(days) {
    document.querySelectorAll('.trend-btn').forEach(b => {
        const active = parseInt(b.dataset.days) === days;
        b.className = b.className.replace(/(bg-sky-600 text-white border-sky-600|border-gray-200 text-gray-500 hover:bg-gray-50)/g, '');
        b.classList.add(...(active
            ? ['bg-sky-600','text-white','border-sky-600']
            : ['border-gray-200','text-gray-500','hover:bg-gray-50']
        ));
    });

    try {
        const res  = await fetch(`${TREND_URL}?days=${days}`);
        const json = await res.json();
        renderTrend(json.trend);
    } catch(e) { console.error('Trend load error:', e); }
}

function renderTrend(data) {
    const labels   = data.map(d => d.date);
    const critical = data.map(d => d.critical);
    const high     = data.map(d => d.high);
    const medium   = data.map(d => d.medium);
    const low      = data.map(d => d.low);
    const canvas   = document.getElementById('trendCanvas');

    if (trendChart) trendChart.destroy();

    trendChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label:'Critical', data:critical, backgroundColor:'#fee2e2', borderColor:'#b91c1c', borderWidth:1 },
                { label:'High',     data:high,     backgroundColor:'#ffedd5', borderColor:'#c2410c', borderWidth:1 },
                { label:'Medium',   data:medium,   backgroundColor:'#fef9c3', borderColor:'#a16207', borderWidth:1 },
                { label:'Low',      data:low,       backgroundColor:'#dbeafe', borderColor:'#1d4ed8', borderWidth:1 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } },
            scales: {
                x: { stacked: true, ticks: { font: { size: 10 } } },
                y: { stacked: true, ticks: { font: { size: 10 }, stepSize: 1 } },
            },
        }
    });
}

// Init chart on load
loadTrend(7);

// ── BULK CHECKBOX ───────────────────────────────────────────
function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    const bar     = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length;
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }
}

// ── REVIEW MODAL ────────────────────────────────────────────
const REVIEW_BASE = '{{ url("anomaly/control") }}';
const REQUIRES_JUSTIFICATION = ['approved_with_note', 'rejected', 'revised'];

function openReviewModal(anomalyId, metadataName) {
    const form = document.getElementById('reviewForm');
    form.action = `${REVIEW_BASE}/${anomalyId}/review`;
    form.reset();
    document.getElementById('modalSubtitle').textContent = metadataName;
    document.getElementById('justificationRequired').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('hidden');
    document.getElementById('reviewModal').classList.add('flex');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('flex');
}

const SCAN_URL = '{{ route("anomaly.control.scan_all") }}';

function openScanModal() {
    document.getElementById('scanModal').classList.remove('hidden');
    document.getElementById('scanModal').classList.add('flex');
    document.getElementById('scanResult').classList.add('hidden');
    document.getElementById('scanProgress').classList.add('hidden');
}

function closeScanModal() {
    document.getElementById('scanModal').classList.add('hidden');
    document.getElementById('scanModal').classList.remove('flex');
}

async function doScan() {
    const btn      = document.getElementById('scanBtn');
    const progress = document.getElementById('scanProgress');
    const result   = document.getElementById('scanResult');
    const metaId   = document.getElementById('scanMetadataId').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Scanning…';
    progress.classList.remove('hidden');
    result.classList.add('hidden');

    const scanType = document.getElementById('scanType').value;
    const form = new FormData();
    form.append('_token', '{{ csrf_token() }}');
    if (metaId) form.append('metadata_id', metaId);
    form.append('scan_type', scanType);

    try {
        const res  = await fetch(SCAN_URL, { method: 'POST', body: form });
        const json = await res.json();

        progress.classList.add('hidden');
        result.classList.remove('hidden');

        if (json.success) {
            const stats = json.stats || {};
            const scanned = stats.scanned ?? 0;
            const anomalies = stats.anomaliesFound ?? stats.anomalies_found ?? 0;
            const skipped = stats.skipped ?? 0;

            result.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-green-700">
                    <p class="font-semibold mb-1"><i class="fas fa-check-circle mr-1"></i>Scan Selesai</p>
                    <p>Scan selesai. ${scanned} data diperiksa, ${anomalies} anomali ditemukan.${skipped ? ' ' + skipped + ' dilewati.' : ''}</p>
                </div>`;
            // Reload setelah 2 detik
            setTimeout(() => window.location.reload(), 2000);
        } else {
            result.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700">
                    <p class="font-semibold mb-1"><i class="fas fa-times-circle mr-1"></i>Gagal</p>
                    <p>${json.message}</p>
                </div>`;
        }
    } catch(e) {
        progress.classList.add('hidden');
        result.classList.remove('hidden');
        result.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700">
            Terjadi kesalahan jaringan.</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-play mr-1"></i>Mulai Scan';
}

document.getElementById('scanModal').addEventListener('click', function(e) {
    if (e.target === this) closeScanModal();
});

function onDecisionChange(val) {
    const required = REQUIRES_JUSTIFICATION.includes(val);
    const reqBadge = document.getElementById('justificationRequired');
    const input    = document.getElementById('justificationInput');
    reqBadge.classList.toggle('hidden', !required);
    input.required = required;
    if (required) input.focus();
}

// Close on backdrop click
document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});

// Handle AJAX review submit
document.getElementById('reviewForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitReviewBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memproses…';

    try {
        const res  = await fetch(this.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(this),
        });
        const json = await res.json();

        if (json.success) {
            closeReviewModal();
            // Reload halaman untuk refresh data
            window.location.reload();
        } else {
            const errors = json.errors ? Object.values(json.errors).flat().join('\n') : json.message;
            alert(errors);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-gavel mr-1"></i>Proses';
        }
    } catch(err) {
        alert('Terjadi kesalahan jaringan.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-gavel mr-1"></i>Proses';
    }
});

// Auto-submit search dengan debounce
(function () {
    const input = document.getElementById('anomaly-search');
    if (!input) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => input.form.submit(), 500);
    });
})();
</script>
@endsection