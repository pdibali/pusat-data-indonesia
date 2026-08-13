@extends('layouts.main')

@section('content')
<div class="py-6 px-4 space-y-5">

    {{-- ── BREADCRUMB ──────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 text-xs text-gray-400">
        <a href="{{ route('anomaly.control.index') }}"
           class="hover:text-sky-600 transition-colors font-semibold text-sky-600">
            <i class="fas fa-angle-left mr-1"></i>Control Anomali
        </a>
        <span>/</span>
        <span class="text-gray-600">Detail #{{ $anomaly->anomalies_id }}</span>
    </div>

    {{-- ── HEADER ──────────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                             text-sm font-semibold"
                      style="{{ $anomaly->severity_style }}">
                    @if($anomaly->severity === 'critical') <i class="fas fa-fire"></i>
                    @elseif($anomaly->severity === 'high') <i class="fas fa-arrow-up"></i>
                    @elseif($anomaly->severity === 'medium') <i class="fas fa-exclamation-triangle"></i>
                    @else <i class="fas fa-info-circle"></i>
                    @endif
                    {{ $anomaly->severity_label }}
                </span>
                <span class="inline-block px-3 py-1.5 rounded-full text-xs font-medium"
                      style="{{ $anomaly->status_style }}">
                    {{ $anomaly->status_label }}
                </span>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @if($anomaly->status === \App\Models\Anomaly::STATUS_WARNING
                    && $data->workflow_status === \App\Models\Data::WORKFLOW_WARNING)
                <form method="POST"
                      action="{{ route('anomaly.control.submit_review', $data->id) }}"
                      class="w-full sm:w-auto">
                    @csrf
                    <button type="submit"
                            class="w-full sm:w-auto justify-center text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg
                                   font-semibold transition-colors flex items-center">
                        <i class="fas fa-magnifying-glass mr-1"></i> Under Review
                    </button>
                </form>
                @elseif($data->workflow_status === \App\Models\Data::WORKFLOW_UNDER_REVIEW)
                <span class="inline-flex items-center gap-2 text-xs px-3 py-2 rounded-full bg-indigo-50
                             text-indigo-700 border border-indigo-200">
                    <i class="fas fa-spinner fa-pulse"></i> Sedang di-review
                </span>
                @endif
            </div>
    </div>

    {{-- ── 2-COLUMN GRID ───────────────────────────────────────── --}}
    <div class="grid md:grid-cols-3 gap-5 min-w-0">

        {{-- ── KOLOM KIRI (2/3) ───────────────────────────────── --}}
        <div class="md:col-span-2 space-y-5 min-w-0">

            {{-- Pesan anomali --}}
            @if($anomaly->anomaly_type !== \App\Models\Anomaly::TYPE_SOURCE_CONFLICT)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    Pesan Anomali
                </p>
                <p class="text-sm text-gray-800 leading-relaxed">
                    {{ $anomaly->message }}
                </p>
                @unless($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNIT_CONFLICT)
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <div class="text-center p-3 rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-400 mb-1">Nilai Sebelumnya</p>
                        <p class="text-lg font-bold text-gray-700 font-mono">
                            {{ $anomaly->previous_value !== null ? number_format($anomaly->previous_value, 2) : '—' }}
                        </p>
                    </div>
                    <div class="text-center p-3 rounded-lg"
                        style="{{ $anomaly->severity_style }}">
                        <p class="text-xs mb-1 opacity-75">Perubahan</p>
                        <p class="text-lg font-bold font-mono">
                            {{ $anomaly->formatted_percentage_change }}
                        </p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-400 mb-1">Nilai Saat Ini</p>
                        <p class="text-lg font-bold text-gray-700 font-mono">
                            {{ $anomaly->current_value !== null ? number_format($anomaly->current_value, 2) : '—' }}
                        </p>
                    </div>
                </div>
                @endunless
            </div>
            @endif

            {{-- Detail Metadata — sumber kebenaran sebelum dua versi yang bentrok --}}
            @if(($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_SOURCE_CONFLICT && $conflictData)
                || $anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNIT_CONFLICT)
            <div class="bg-white rounded-xl border border-sky-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Detail Metadata</p>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 font-semibold">
                        Metadata Inti
                    </span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-6 sm:gap-x-10 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400 font-medium">Nama Metadata</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $data->metadata?->nama ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 font-medium">Satuan Metadata</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $data->metadata?->satuan_data ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 font-medium">Produsen Metadata</dt>
                        <dd class="text-gray-800 mt-0.5">{{ $data->metadata?->produsen?->nama_produsen ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
            @endif

            {{-- Detail data --}}
           @if($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_SOURCE_CONFLICT && $conflictDataList->isNotEmpty())
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                    {{-- Card: data anomali ini --}}
                    <div class="bg-white rounded-xl border border-purple-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Detail Data</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-semibold">
                                Data Ini
                            </span>
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            @php
                            $detailsA = [
                                ['label'=>'Metadata', 'value'=> $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_asal_id ?? $data->satuan_id ?? 0)) : '-'],
                                ['label'=>'Satuan', 'value'=> $data->satuanAsal?->nama_satuan ?? ($data->satuan?->nama_satuan ?? ($data->metadata?->satuan_data ?? '-'))],
                                ['label'=>'Lokasi', 'value'=> $data->location?->nama_wilayah ?? '-'],
                                ['label'=>'Periode', 'value'=> $data->time
                                    ? ($data->time->year
                                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                                        . ($data->time->quarter ? '/Q'.$data->time->quarter    : '')
                                        . ($data->time->semester? '/S'.$data->time->semester   : ''))
                                    : '-'],
                                ['label'=>'Sumber/Produsen','value'=> $data->produsen?->nama_produsen ?? '-'],
                                ['label'=>'Rujukan', 'value'=> $data->rujukan?->nama_rujukan ?? '-'],
                                ['label'=>'Diinput oleh', 'value'=> $data->user?->name ?? '-'],
                                ['label'=>'Tanggal Input', 'value'=> $data->date_inputed?->format('d/m/Y H:i') ?? '-'],
                            ];
                            @endphp
                            @foreach($detailsA as $d)
                            <div>
                                <dt class="text-xs text-gray-400 font-medium">{{ $d['label'] }}</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $d['value'] }}</dd>
                            </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- Card: satu per sumber pembanding --}}
                    @foreach($conflictDataList as $cd)
                    <div class="bg-white rounded-xl border border-amber-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Detail Data</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-semibold">
                                Sumber Pembanding {{ $loop->iteration }}
                            </span>
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            @php
                            $detailsB = [
                                ['label'=>'Metadata', 'value'=> $cd->metadata ? $cd->metadata->getSubNamaForSatuan((int)($cd->satuan_asal_id ?? $cd->satuan_id ?? 0)) : '-'],
                                ['label'=>'Satuan', 'value'=> $cd->satuanAsal?->nama_satuan ?? ($cd->satuan?->nama_satuan ?? ($cd->metadata?->satuan_data ?? '-'))],
                                ['label'=>'Lokasi', 'value'=> $cd->location?->nama_wilayah ?? '-'],
                                ['label'=>'Periode', 'value'=> $cd->time
                                    ? ($cd->time->year
                                        . ($cd->time->month   ? '/Bln-'.$cd->time->month   : '')
                                        . ($cd->time->quarter ? '/Q'.$cd->time->quarter    : '')
                                        . ($cd->time->semester? '/S'.$cd->time->semester   : ''))
                                    : '-'],
                                ['label'=>'Sumber/Produsen','value'=> $cd->produsen?->nama_produsen ?? '-'],
                                ['label'=>'Rujukan', 'value'=> $cd->rujukan?->nama_rujukan ?? '-'],
                                ['label'=>'Diinput oleh', 'value'=> $cd->user?->name ?? '-'],
                                ['label'=>'Tanggal Input', 'value'=> $cd->date_inputed?->format('d/m/Y H:i') ?? '-'],
                            ];
                            @endphp
                            @foreach($detailsB as $d)
                            <div>
                                <dt class="text-xs text-gray-400 font-medium">{{ $d['label'] }}</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $d['value'] }}</dd>
                            </div>
                            @endforeach
                        </dl>
                    </div>
                    @endforeach
                </div>
            @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNIT_CONFLICT)
                <div class="grid md:grid-cols-2 gap-5">
                    {{-- Card 1: Menurut Satuan Metadata --}}
                    <div class="bg-white rounded-xl border border-indigo-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Menurut Metadata</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 font-semibold">
                                Satuan Sistem
                            </span>
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            @php
                            $detailsUnitA = [
                                ['label'=>'Metadata', 'value'=> $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_id ?? 0)) : '-'],
                                ['label'=>'Satuan Data', 'value'=> $data->satuan?->nama_satuan ?? ($data->metadata?->satuan_data ?? '-')],
                                ['label'=>'Lokasi', 'value'=> $data->location?->nama_wilayah ?? '-'],
                                ['label'=>'Periode', 'value'=> $data->time
                                    ? ($data->time->year
                                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                                        . ($data->time->quarter ? '/Q'.$data->time->quarter    : '')
                                        . ($data->time->semester? '/S'.$data->time->semester   : ''))
                                    : '-'],
                                ['label'=>'Sumber/Produsen', 'value'=> $data->metadata?->produsen?->nama_produsen ?? '-'],
                                ['label'=>'Rujukan', 'value'=> $data->rujukan?->nama_rujukan ?? '-'],
                                ['label'=>'Diinput oleh', 'value'=> $data->user?->name ?? '-'],
                                ['label'=>'Tanggal Input', 'value'=> $data->date_inputed?->format('d/m/Y H:i') ?? '-'],
                            ];
                            @endphp
                            @foreach($detailsUnitA as $d)
                            <div>
                                <dt class="text-xs text-gray-400 font-medium">{{ $d['label'] }}</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $d['value'] }}</dd>
                            </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- Card 2: Menurut Satuan Rujukan --}}
                    <div class="bg-white rounded-xl border border-pink-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Menurut Sumber Rujukan</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-pink-50 text-pink-700 border border-pink-200 font-semibold">
                                Satuan Asli Sumber
                            </span>
                        </div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            @php
                            $detailsUnitB = [
                                ['label'=>'Metadata', 'value'=> $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_asal_id ?? 0)) : '-'],
                                ['label'=>'Satuan Data', 'value'=> $data->satuanAsal?->nama_satuan ?? '-'],
                                ['label'=>'Lokasi', 'value'=> $data->location?->nama_wilayah ?? '-'],
                                ['label'=>'Periode', 'value'=> $data->time
                                    ? ($data->time->year
                                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                                        . ($data->time->quarter ? '/Q'.$data->time->quarter    : '')
                                        . ($data->time->semester? '/S'.$data->time->semester   : ''))
                                    : '-'],
                                ['label'=>'Sumber/Produsen', 'value'=> $data->rujukan?->produsen?->nama_produsen ?? '-'],
                                ['label'=>'Rujukan', 'value'=> $data->rujukan?->nama_rujukan ?? '-'],
                                ['label'=>'Diinput oleh', 'value'=> $data->user?->name ?? '-'],
                                ['label'=>'Tanggal Input', 'value'=> $data->date_inputed?->format('d/m/Y H:i') ?? '-'],
                            ];
                            @endphp
                            @foreach($detailsUnitB as $d)
                            <div>
                                <dt class="text-xs text-gray-400 font-medium">{{ $d['label'] }}</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $d['value'] }}</dd>
                            </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            @else
                {{-- Card lama, dipakai untuk tipe anomali selain source_conflict --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Detail Data</p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        @php
                        $details = [
                            ['label'=>'Metadata', 'value'=> $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_id ?? 0)) : '-'],
                            ['label'=>'Satuan Data', 'value'=> $data->satuan?->nama_satuan ?? ($data->metadata?->satuan_data ?? '-')],
                            ['label'=>'Lokasi',    'value'=> $data->location?->nama_wilayah ?? '-'],
                            ['label'=>'Periode',   'value'=> $data->time
                                ? ($data->time->year
                                    . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                                    . ($data->time->quarter ? '/Q'.$data->time->quarter    : '')
                                    . ($data->time->semester? '/S'.$data->time->semester   : ''))
                                : '-'],
                            ['label'=>'Sumber/Produsen', 'value'=> $data->produsen?->nama_produsen ?? '-'],
                            ['label'=>'Rujukan',   'value'=> $data->rujukan?->nama_rujukan ?? '-'],
                            ['label'=>'Diinput oleh', 'value'=> $data->user?->name ?? '-'],
                            ['label'=>'Tanggal Input', 'value'=> $data->date_inputed?->format('d/m/Y H:i') ?? '-'],
                        ];
                        @endphp
                        @foreach($details as $d)
                        <div>
                            <dt class="text-xs text-gray-400 font-medium">{{ $d['label'] }}</dt>
                            <dd class="text-gray-800 mt-0.5">{{ $d['value'] }}</dd>
                        </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            {{-- Konteks series — histori periode sekitar, untuk anomali outlier/perubahan ekstrem --}}
            @if(in_array($anomaly->anomaly_type, [
                    \App\Models\Anomaly::TYPE_EXTREME_INCREASE,
                    \App\Models\Anomaly::TYPE_EXTREME_DECREASE,
                    \App\Models\Anomaly::TYPE_UNREASONABLE,
                ], true))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Riwayat Data Sekitar Periode Ini
                    </p>
                    <span class="text-[10px] text-gray-400">
                        {{ $seriesContext->count() }} periode ditampilkan
                    </span>
                </div>

                @if($seriesContext->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-4">Tidak ada data historis untuk series ini.</p>
                @else
                <div class="overflow-x-auto -mx-5 px-5 sm:mx-0 sm:px-0">
                    <table class="w-full min-w-120 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-gray-500 font-medium">Periode</th>
                                <th class="px-2 py-2 text-right text-gray-500 font-medium">Nilai</th>
                                <th class="px-2 py-2 text-left text-gray-500 font-medium">Satuan</th>
                                <th class="px-2 py-2 text-center text-gray-500 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($seriesContext as $point)
                            <tr class="{{ $point['is_current'] ? 'bg-red-50' : '' }}">
                                <td class="px-2 py-2 font-medium {{ $point['is_current'] ? 'text-red-700 font-semibold' : 'text-gray-700' }}">
                                    {{ $point['periode'] }}
                                </td>
                                <td class="px-2 py-2 text-right font-mono {{ $point['is_current'] ? 'text-red-700 font-semibold' : 'text-gray-800' }}">
                                    {{ number_format($point['value'], 2) }}
                                </td>
                                <td class="px-2 py-2 {{ $point['is_current'] ? 'text-red-600' : 'text-gray-500' }}">
                                    {{ $point['satuan'] }}
                                </td>
                                <td class="px-2 py-2 text-center">
                                    @if($point['is_current'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                            style="background:#fee2e2; color:#b91c1c;">Anomali</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                            style="background:#dcfce7; color:#15803d;">Normal</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endif

            {{-- Perbandingan sumber data — sembunyikan untuk unit_conflict, karena
                tabelnya membandingkan sumber lain (rujukan berbeda), bukan dua
                versi satuan dari data yang sama seperti yang ditampilkan di atas --}}
            @if($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_SOURCE_CONFLICT)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Perbandingan Antar Sumber Data
                    </p>
                    <button onclick="loadSourceComparison()"
                            class="text-xs text-sky-600 hover:text-sky-800 transition-colors">
                        <i class="fas fa-sync-alt mr-1"></i>Refresh
                    </button>
                </div>
                <div id="sourceComparisonWrap">
                    @if($sourceComparison->isNotEmpty())
                        @include('pages.anomaly.control._source_comparison',
                                ['sourceComparison' => $sourceComparison])
                    @else
                        <p class="text-xs text-gray-400 text-center py-4">
                            Tidak ada data pembanding dari sumber lain.
                        </p>
                    @endif
                </div>
            </div>
            @endif

            {{-- Audit trail --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Histori Perubahan (Audit Trail)
                </p>
                @if($auditHistory->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-4">Belum ada histori.</p>
                @else
                <div class="space-y-0">
                    @foreach($auditHistory as $audit)
                    <div class="flex gap-3 pb-4 relative
                                {{ !$loop->last ? 'border-l border-gray-100 ml-2.5' : '' }}">
                        {{-- Icon dot --}}
                        <div class="absolute left-0 -translate-x-1/2 mt-0.5">
                            <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px]"
                                 style="{{ $audit->action_style }}">
                                <i class="{{ $audit->action_icon }}"></i>
                            </div>
                        </div>
                        {{-- Content --}}
                        <div class="ml-5 flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-xs font-semibold text-gray-700">
                                    {{ $audit->action_label }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ $audit->created_at?->format('d/m/Y H:i') }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    — {{ $audit->user?->name ?? 'Sistem' }}
                                </span>
                            </div>
                            @if($audit->reason)
                            <p class="text-xs text-gray-500 mt-0.5 italic">"{{ $audit->reason }}"</p>
                            @endif
                            @if($audit->old_value || $audit->new_value)
                             <div class="mt-1.5 flex flex-col sm:flex-row gap-2 sm:gap-3">
                                @if($audit->old_value)
                                <div class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded">
                                    <span class="font-medium">Sebelum:</span>
                                    @foreach($audit->old_value as $key => $value)
                                        <div>
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            :

                                            <strong>
                                                @if(is_array($value))
                                                    {{ implode(', ', $value) }}
                                                @else
                                                    {{ ucfirst($value) }}
                                                @endif
                                            </strong>
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                                @if($audit->new_value)
                                <div class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded">
                                    <span class="font-medium">Sesudah:</span>
                                    @foreach($audit->new_value as $key => $value)
                                        <div>
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            :

                                            <strong>
                                                @if(is_array($value))
                                                    {{ implode(', ', $value) }}
                                                @else
                                                    {{ ucfirst($value) }}
                                                @endif
                                            </strong>
                                        </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>{{-- end kolom kiri --}}

        {{-- ── KOLOM KANAN (1/3) ──────────────────────────────── --}}
        <div class="space-y-5 min-w-0">

            {{-- Info anomali --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3 text-xs">
                <p class="font-semibold text-gray-500 uppercase tracking-wide text-[10px]">
                    Info Anomali
                </p>
                <div class="flex justify-between">
                    <span class="text-gray-400">ID</span>
                    <span class="font-mono font-semibold text-gray-700">
                        #{{ $anomaly->anomalies_id }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Tipe</span>
                    <span class="text-gray-700">{{ $anomaly->anomaly_type_label }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Terdeteksi</span>
                    <span class="text-gray-700">
                        {{ $anomaly->detected_at?->format('d/m/Y H:i') }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Total Review</span>
                    <span class="text-gray-700">{{ $anomaly->reviews->count() }}</span>
                </div>
            </div>

            {{-- ── TIMELINE HISTORI KEPUTUSAN ────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Histori Keputusan Stakeholder
                </p>
                @if($decisionHistory->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-4">Belum ada keputusan.</p>
                @else
                <div class="space-y-3">
                    @foreach($decisionHistory as $review)
                    <div class="relative pl-6">
                        {{-- Timeline dot --}}
                        <div class="absolute left-0 top-1 w-4 h-4 rounded-full
                                    flex items-center justify-center text-[8px]"
                             style="{{ $review->decision_style }}">
                            <i class="{{ $review->decision_icon }}"></i>
                        </div>
                        {{-- Line --}}
                        @if(!$loop->last)
                        <div class="absolute left-2 top-5 w-px h-full bg-gray-100"></div>
                        @endif
                        {{-- Content --}}
                        <div class="bg-gray-50 rounded-lg p-3 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                      style="{{ $review->decision_style }}">
                                    {{ $review->decision_label }}
                                </span>
                                <span class="text-[10px] text-gray-400">
                                    {{ $review->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <p class="text-xs font-medium text-gray-700">
                                {{ $review->reviewer?->name ?? '-' }}
                            </p>
                            @if($review->justification)
                            <p class="text-xs text-gray-500 italic border-l-2 border-gray-200 pl-2">
                                "{{ $review->justification }}"
                            </p>
                            @endif
                            @if($review->notes)
                            <p class="text-xs text-gray-400">
                                Catatan: {{ $review->notes }}
                            </p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- ── UNDUH LAPORAN PDF ───────────────────────── --}}
            <a href="{{ route('anomaly.control.export_report', $anomaly->anomalies_id) }}"
               class="w-full flex items-center justify-center gap-2 text-xs bg-red-700
                      hover:bg-red-800 text-white py-2.5 rounded-lg font-semibold
                      transition-colors">
                <i class="fas fa-file-pdf"></i> Unduh Laporan PDF
            </a>

            {{-- ── FORM REVIEW ─────────────────────────────── --}}
            @if($anomaly->isPendingReview())
            <div id="reviewSection" class="bg-white rounded-xl border border-amber-200 p-4">
                <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-3">
                    <i class="fas fa-gavel mr-1"></i>Beri Keputusan
                </p>
                <form method="POST"
                      action="{{ route('anomaly.control.review', $anomaly->anomalies_id) }}"
                      id="reviewFormDetail"
                      class="space-y-3">
                    @csrf

                    {{-- Decision radio --}}
                    <div class="space-y-1.5">
                        @foreach($decisionOptions as $val => $label)
                        <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border
                                      border-gray-200 cursor-pointer hover:border-sky-300
                                      transition-colors text-xs font-medium text-gray-700
                                      has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50
                                      has-[:checked]:text-sky-700">
                            <input type="radio" name="decision" value="{{ $val }}"
                                   class="text-sky-600 focus:ring-sky-400"
                                   onchange="onDetailDecisionChange('{{ $val }}')">
                            @if($val === 'approved') <i class="fas fa-check-circle text-green-500 w-4"></i>
                            @elseif($val === 'approved_with_note') <i class="fas fa-check-double text-teal-500 w-4"></i>
                            @elseif($val === 'rejected') <i class="fas fa-times-circle text-red-500 w-4"></i>
                            @else <i class="fas fa-redo text-purple-500 w-4"></i>
                            @endif
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>

                    {{-- Justification --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Justification
                            <span id="detailJustReq"
                                  class="hidden text-red-500 ml-1 font-normal">* Wajib</span>
                        </label>
                        <textarea name="justification" id="detailJustInput" rows="3"
                                  placeholder="Tuliskan alasan keputusan…"
                                  class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2
                                         focus:outline-none focus:ring-2 focus:ring-sky-400
                                         resize-none placeholder-gray-400"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Catatan <span class="text-gray-400 font-normal">(opsional)</span>
                        </label>
                        <input type="text" name="notes" placeholder="Catatan singkat…"
                               class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2
                                      focus:outline-none focus:ring-2 focus:ring-sky-400">
                    </div>

                    @error('decision')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @error('justification')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            class="w-full text-xs bg-sky-600 hover:bg-sky-700 text-white
                                   py-2.5 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-gavel mr-1.5"></i>Simpan Keputusan
                    </button>
                </form>
            </div>
            @else
            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-center">
                <i class="fas fa-check-circle text-2xl text-green-400 mb-2 block"></i>
                <p class="text-xs font-semibold text-gray-600">Anomali sudah diproses</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $anomaly->status_label }}</p>
            </div>
            @endif

        </div>{{-- end kolom kanan --}}
    </div>{{-- end grid --}}
</div>

<script>
const COMPARE_URL = '{{ route("anomaly.control.compare_sources") }}';

// Perbandingan sumber AJAX
async function loadSourceComparison() {
    const wrap = document.getElementById('sourceComparisonWrap');
    wrap.innerHTML = '<p class="text-xs text-gray-400 text-center py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat…</p>';

    try {
        const url = new URL(COMPARE_URL);
        const CURRENT_DATA_ID = {{ $data->id }};
        url.searchParams.set('metadata_id', '{{ $data->metadata_id }}');
        url.searchParams.set('location_id', '{{ $data->location_id }}');
        url.searchParams.set('time_id',     '{{ $data->time_id }}');

        const res  = await fetch(url);
        const json = await res.json();
        json.data.sort((a, b) => (b.data_id === CURRENT_DATA_ID) - (a.data_id === CURRENT_DATA_ID));

        if (!json.success || !json.data.length) {
            wrap.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Tidak ada data pembanding.</p>';
            return;
        }

        let html = '';
        const unitsConsistent = json.data[0]?.units_consistent ?? true;
        if (!unitsConsistent) {
            html += `
            <div class="mb-2 px-3 py-2 rounded-lg text-xs font-medium bg-pink-50 text-pink-700 border border-pink-200 flex items-center gap-2">
                <i class="fas fa-scale-unbalanced"></i>
                Satuan rujukan berbeda antar sumber untuk periode ini, periksa kemungkinan kesalahan normalisasi selain konflik nilainya.
            </div>`;
        }
        html += `
            <div class="overflow-x-auto -mx-5 px-5 sm:mx-0 sm:px-0">
            <table class="w-full min-w-[900px] text-xs">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-2 text-left text-gray-500 font-medium">Rujukan</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">Angka di Satuan Lama (Rujukan)</th>
                    <th class="px-2 py-2 text-left text-gray-500 font-medium">Satuan Lama (Rujukan)</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">Angka Setelah Disesuaikan</th>
                    <th class="px-2 py-2 text-left text-gray-500 font-medium">Satuan Sesuai Metadata</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">Selisih</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">% Diff</th>
                    <th class="px-2 py-2 text-center text-gray-500 font-medium">Status Nilai</th>
                    <th class="px-2 py-2 text-center text-gray-500 font-medium">Status Satuan</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">`;

        json.data.forEach(row => {
            const conflict = row.conflict;
            const unitConflict = row.unit_conflict;
            html += `
            <tr class="${conflict ? 'bg-amber-50/40' : ''}">
            <td class="px-2 py-2 font-medium text-gray-700">${esc(row.rujukan)}</td>
            <td class="px-2 py-2 text-right font-mono text-gray-800">${row.old_unit_value !== null && row.old_unit_value !== undefined ? fmt(row.old_unit_value) : '—'}</td>
            <td class="px-2 py-2 text-gray-500">${esc(row.old_unit_name ?? '—')}</td>
            <td class="px-2 py-2 text-right font-mono text-gray-800">${fmt(row.adjusted_value)}</td>
            <td class="px-2 py-2 text-gray-500">${esc(row.satuan_metadata ?? '—')}</td>
            <td class="px-2 py-2 text-right font-mono ${row.selisih >= 0 ? 'text-red-600' : 'text-blue-600'}">
                ${row.selisih >= 0 ? '+' : ''}${fmt(row.selisih)}
            </td>
            <td class="px-2 py-2 text-right font-mono ${conflict ? 'text-amber-600 font-semibold' : 'text-gray-500'}">
                ${row.pct_diff}%
            </td>
            <td class="px-2 py-2 text-center">
                ${conflict
                ? '<span style="background:#fef9c3;color:#a16207;" class="px-2 py-0.5 rounded-full text-[10px] font-semibold">Konflik</span>'
                : '<span style="background:#dcfce7;color:#15803d;" class="px-2 py-0.5 rounded-full text-[10px] font-semibold">OK</span>'
                }
            </td>
            <td class="px-2 py-2 text-center">
                ${unitConflict
                ? '<span style="background:#fce7f3;color:#be185d;" class="px-2 py-0.5 rounded-full text-[10px] font-semibold">Konflik</span>'
                : '<span style="background:#dcfce7;color:#15803d;" class="px-2 py-0.5 rounded-full text-[10px] font-semibold">OK</span>'
                }
            </td>
            </tr>`;
        });
        html += `</tbody></table>`;
        wrap.innerHTML = html;
    } catch(e) {
        wrap.innerHTML = '<p class="text-xs text-red-400 text-center py-4">Gagal memuat data.</p>';
    }
}

function esc(str) {
    return String(str ?? '-')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmt(val) {
    const n = parseFloat(val);
    return isNaN(n) ? '-' : n.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function fmt4(val) {
    const n = parseFloat(val);
    return isNaN(n) ? '-' : n.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:4});
}

// Decision radio handler
const REQUIRES_JUST = ['approved_with_note', 'rejected', 'revised'];

function onDetailDecisionChange(val) {
    const req   = REQUIRES_JUST.includes(val);
    const badge = document.getElementById('detailJustReq');
    const input = document.getElementById('detailJustInput');
    badge?.classList.toggle('hidden', !req);
    if (input) {
        input.required = req;
        if (req) input.focus();
    }
}
</script>
@endsection