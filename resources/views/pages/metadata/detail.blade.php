@extends('layouts.main')

@section('content')

@php
    $from = request('from');
    $bulanList = ['','Januari','Februari','Maret','April','Mei','Juni',
                  'Juli','Agustus','September','Oktober','November','Desember'];

    $statusStyle = match((int)$metadata->status) {
        2 => ['label' => 'Active',   'style' => 'background:#dcfce7; color:#15803d;', 'icon' => 'fa-circle-check'],
        3 => ['label' => 'Inactive', 'style' => 'background:#f3f4f6; color:#6b7280;', 'icon' => 'fa-circle-xmark'],
        default => ['label' => 'Pending', 'style' => 'background:#fef3c7; color:#b45309;', 'icon' => 'fa-clock'],
    };
@endphp

<div class="py-6">

    {{-- Tombol Kembali --}}
    <a href="{{ $from === 'approval' ? route('metadata.approval') : route('metadata.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HERO HEADER CARD                            --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="mx-0 bg-white rounded-xl shadow overflow-hidden">

        {{-- Accent bar atas --}}
        <div style="height:4px; background: linear-gradient(90deg, #0284c7, #38bdf8, #7dd3fc);"></div>

        <div class="p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">

                {{-- Judul --}}
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">
                            Detail Metadata
                        </span>
                        <span style="{{ $statusStyle['style'] }}"
                              class="px-2.5 py-0.5 rounded-full text-xs font-semibold flex items-center gap-1">
                            <i class="fas {{ $statusStyle['icon'] }} text-xs"></i>
                            {{ $statusStyle['label'] }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                        {{ $metadata->nama }}
                    </h1>
                    @if($metadata->alias)
                        <p class="text-sm text-gray-400 mt-0.5 italic">{{ $metadata->alias }}</p>
                    @endif

                    {{-- Tags --}}
                    @if($metadata->tag)
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @foreach(explode(',', $metadata->tag) as $tag)
                                <span class="bg-sky-50 text-sky-600 border border-sky-200 text-xs px-2.5 py-0.5 rounded-full font-medium">
                                    # {{ trim($tag) }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Meta info kanan --}}
                <div class="shrink-0 text-right text-xs text-gray-400 space-y-1">
                    <p id="current-date"></p>
                    <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
                    <p class="mt-2">
                        ID: <span class="font-mono text-gray-500 font-semibold">#{{ $metadata->metadata_id }}</span>
                    </p>
                    @if($metadata->date_inputed)
                        <p>Input: {{ \Carbon\Carbon::parse($metadata->date_inputed)->translatedFormat('d M Y') }}</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-5 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                    <i class="fas fa-tag text-sky-400"></i>
                    <span class="text-gray-500">Klasifikasi:</span>
                    <span class="font-semibold text-gray-700">
                        {{ $metadata->klasifikasi?->nama_klasifikasi ?? '-' }}
                    </span>
                </div>
                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                    <i class="fas fa-database text-indigo-400"></i>
                    <span class="text-gray-500">Tipe:</span>
                    <span class="font-semibold text-gray-700">{{ $metadata->tipe_data }}</span>
                </div>
                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                    <i class="fas fa-ruler text-emerald-400"></i>
                    <span class="text-gray-500">Satuan:</span>
                    <span class="font-semibold text-gray-700">{{ $metadata->satuan_data }}</span>
                </div>
                @if(!empty($metadata->sub_nama_metadata))
                <div class="flex items-start gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs">
                    <i class="fas fa-list text-slate-400 mt-0.5"></i>
                    <div>
                        <div class="text-gray-500">Sub Nama Metadata:</div>
                        <div class="mt-1 space-y-1 text-gray-700">
                            @foreach($metadata->sub_nama_metadata as $satuanId => $label)
                                <div>
                                    <span class="font-semibold">{{ $satuanNames[$satuanId] ?? 'Satuan #'.$satuanId }}:</span>
                                    {{ $label }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                    <i class="fas fa-calendar-alt text-green-400"></i>
                    <span class="text-gray-500">Frekuensi:</span>
                    <span class="font-semibold text-gray-700">{{ $metadata->frekuensi_penerbitan }}</span>
                </div>
                @if($metadata->flag_desimal)
                    <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs">
                        <i class="fas fa-percent text-rose-400"></i>
                        <span class="font-semibold text-gray-700">Menggunakan Desimal</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Tombol aksi admin (jika pending) --}}
        @if($metadata->status == 1)
            <div class="px-6 pb-5 flex gap-2">
                <form action="{{ route('metadata.approve', $metadata->metadata_id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        style="background:#22c55e; color:#fff;"
                        onmouseover="this.style.background='#16a34a'"
                        onmouseout="this.style.background='#22c55e'"
                        class="px-4 py-2 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                        <i class="fas fa-check"></i> Setujui & Aktifkan
                    </button>
                </form>
                <form action="{{ route('metadata.reject', $metadata->metadata_id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        style="background:#f87171; color:#fff;"
                        onmouseover="this.style.background='#ef4444'"
                        onmouseout="this.style.background='#f87171'"
                        class="px-4 py-2 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                        <i class="fas fa-times"></i> Tolak
                    </button>
                </form>
            </div>
        @elseif($metadata->status == 2)
            <div class="px-6 pb-5">
                <form action="{{ route('metadata.reject', $metadata->metadata_id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        style="border:1px solid #fca5a5; color:#ef4444; background:transparent;"
                        onmouseover="this.style.background='#fef2f2'"
                        onmouseout="this.style.background='transparent'"
                        class="px-4 py-2 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <i class="fas fa-ban"></i> Nonaktifkan
                    </button>
                </form>
            </div>
        @elseif($metadata->status == 3)
            <div class="px-6 pb-5">
                <form action="{{ route('metadata.reactivate', $metadata->metadata_id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        style="border:1px solid #86efac; color:#16a34a; background:transparent;"
                        onmouseover="this.style.background='#f0fdf4'"
                        onmouseout="this.style.background='transparent'"
                        class="px-4 py-2 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors">
                        <i class="fas fa-redo"></i> Aktifkan Kembali
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- SECTION CARDS                               --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

        {{-- ── 1. INFORMASI KONSEPTUAL ── --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#eff6ff; color:#3b82f6;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-lightbulb"></i>
                </span>
                Informasi Konseptual
            </h2>
            <div class="space-y-4 text-sm">
                @foreach([
                    ['Konsep',       $metadata->konsep],
                    ['Definisi',     $metadata->definisi],
                    ['Asumsi',       $metadata->asumsi],
                    ['Metodologi',   $metadata->metodologi],
                    ['Penjelasan Metodologi', $metadata->penjelasan_metodologi],
                ] as [$label, $value])
                    @if($value && $value !== 'N/A')
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $label }}</p>
                            <p class="text-gray-700 leading-relaxed">{{ $value }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- ── 2. INFORMASI TEKNIS ── --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#f0fdf4; color:#22c55e;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-cogs"></i>
                </span>
                Informasi Teknis
            </h2>
            <div class="space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Flag Desimal</p>
                        <p class="text-gray-700 font-medium">{{ $metadata->flag_desimal ? 'Ya' : 'Tidak' }}</p>
                    </div>
                </div>
                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipe Group</p>
                            @if($metadata->tipe_group == 1)
                                <span class="bg-purple-50 text-purple-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                    Bagian dari Group
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2.5 py-1 rounded-full">
                                    Berdiri Sendiri
                                </span>
                            @endif
                        </div>

                        @if($metadata->tipe_group == 1 && $metadata->groupParent)
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Metadata Induk</p>
                                <a href="{{ route('metadata.detail', ['metadata' => $metadata->groupParent->metadata_id, 'from' => $from]) }}"
                                class="text-sky-600 hover:text-sky-800 font-semibold transition-colors flex items-center gap-1">
                                    <i class="fas fa-link text-xs"></i>
                                    {{ $metadata->groupParent->nama }}
                                </a>
                            </div>
                        @endif
                    </div>

                    @if($metadata->groupChildren && $metadata->groupChildren->count() > 0)
                        <div class="pt-3 border-t">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
                                Metadata dalam Group Ini ({{ $metadata->groupChildren->count() }})
                            </p>
                            <div class="space-y-1.5">
                                @foreach($metadata->groupChildren as $child)
                                    <a href="{{ route('metadata.detail', ['metadata' => $child->metadata_id, 'from' => $from]) }}"
                                    class="flex items-center justify-between px-3 py-2 bg-gray-50 hover:bg-sky-50
                                            border rounded-lg text-xs transition-colors group">
                                        <span class="font-medium text-gray-700 group-hover:text-sky-700">
                                            {{ $child->nama }}
                                        </span>
                                        <span style="{{ match((int)$child->status) {
                                            2 => 'background:#dcfce7; color:#15803d;',
                                            3 => 'background:#f3f4f6; color:#6b7280;',
                                            default => 'background:#fef3c7; color:#b45309;'
                                        } }}" class="px-2 py-0.5 rounded-full text-xs font-semibold">
                                            {{ match((int)$child->status) { 2=>'Active', 3=>'Inactive', default=>'Pending' } }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── 3. INFORMASI PUBLIKASI ── --}}
        <div class="bg-white rounded-xl shadow p-5 col-span-2">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#fffbeb; color:#f59e0b;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-newspaper"></i>
                </span>
                Informasi Publikasi
            </h2>
            <div class="space-y-4 text-sm">

                <div class="grid grid-cols-2 gap-4">
                    {{-- Tahun Mulai Data: dihitung otomatis dari data terkecil --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tahun Mulai Data</p>
                        <p class="text-gray-700 font-medium">{{ $metadata->tahun_mulai ?? '-' }}</p>
                    </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tahun Metadata</p>
                            <p class="text-gray-700 font-medium">{{ $metadata->tahun_metadata ?? '-' }}</p>
                        </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Frekuensi Penerbitan</p>
                        <span class="bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                            {{ $metadata->frekuensi_penerbitan ?? '-' }}
                        </span>
                    </div>

                    {{-- Tahun Data Tersedia: rentang min-max, dihitung otomatis --}}
                    @if($metadata->tahun_data_tersedia)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tahun Data Tersedia</p>
                            <p class="text-gray-700 font-medium">{{ $metadata->tahun_data_tersedia }}</p>
                        </div>
                    @endif

                    @if($metadata->bulan_pertama_rilis)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Bulan Pertama Rilis</p>
                            <p class="text-gray-700 font-medium">
                                {{ $bulanList[$metadata->bulan_pertama_rilis] ?? '-' }}
                            </p>
                        </div>
                    @endif

                    @if($metadata->tanggal_rilis)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tanggal Rilis</p>
                            <p class="text-gray-700 font-medium">{{ $metadata->tanggal_rilis }}</p>
                        </div>
                    @endif

                    @if($metadata->publikasi_utama)
                        <div class="col-span-2">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Publikasi Utama</p>
                            <p class="text-gray-700 font-medium">{{ $metadata->publikasi_utama }}</p>
                        </div>
                    @endif

                    @if($metadata->rujukan)
                        <div class="col-span-2">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Rujukan</p>
                            <p class="text-gray-700">{{ $metadata->rujukan }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── 5. PENANGGUNG JAWAB (full width) ── --}}
        <div class="bg-white rounded-xl shadow p-5 lg:col-span-2">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#fff1f2; color:#f43f5e;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-address-card"></i>
                </span>
                Sumber Metadata Pertama
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 text-sm">
                <div class="col-span-4">
                    <p class="text-gray-800 font-semibold">{{ $metadata->produsen->nama_produsen ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- PANEL KEPUTUSAN VERIFIKASI                  --}}
    {{-- ═══════════════════════════════════════════ --}}
    @if($from === 'approval')
        <div class="mt-5 bg-white rounded-xl shadow overflow-hidden">
            <div style="height:4px; background: linear-gradient(90deg,
                @if($metadata->status == 1) #f59e0b, #fde68a
                @elseif($metadata->status == 2) #22c55e, #86efac
                @else #9ca3af, #d1d5db @endif);"></div>

            <div class="p-6">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2 mb-1">
                    <i class="fas fa-clipboard-check text-sky-500"></i>
                    Keputusan Verifikasi
                </h2>
                <p class="text-sm text-gray-400 mb-5">
                    Tinjau seluruh informasi metadata di atas sebelum mengambil keputusan.
                </p>

                <div class="flex items-center gap-3 mb-5 p-4 rounded-lg border"
                     style="@if($metadata->status==1) background:#fffbeb; border-color:#fde68a;
                            @elseif($metadata->status==2) background:#f0fdf4; border-color:#bbf7d0;
                            @else background:#f9fafb; border-color:#e5e7eb; @endif">
                    <i class="fas @if($metadata->status==1) fa-clock text-amber-500
                                   @elseif($metadata->status==2) fa-check-circle text-green-500
                                   @else fa-ban text-gray-400 @endif text-xl"></i>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">Status Saat Ini</p>
                        <p class="font-bold @if($metadata->status==1) text-amber-700
                                            @elseif($metadata->status==2) text-green-700
                                            @else text-gray-600 @endif">
                            {{ match((int)$metadata->status) { 2=>'Active', 3=>'Inactive', default=>'Pending' } }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if($metadata->status == 1)
                        <form action="{{ route('metadata.approve', $metadata->metadata_id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="background:#22c55e; color:#fff;"
                                onmouseover="this.style.background='#16a34a'"
                                onmouseout="this.style.background='#22c55e'"
                                class="px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors shadow-md">
                                <i class="fas fa-check-circle"></i>
                                Setujui & Aktifkan Metadata
                            </button>
                        </form>
                        <form action="{{ route('metadata.reject', $metadata->metadata_id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="background:#f87171; color:#fff;"
                                onmouseover="this.style.background='#ef4444'"
                                onmouseout="this.style.background='#f87171'"
                                class="px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors shadow-md">
                                <i class="fas fa-times-circle"></i>
                                Tolak Metadata
                            </button>
                        </form>
                    @elseif($metadata->status == 2)
                        <form action="{{ route('metadata.reject', $metadata->metadata_id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="border:1.5px solid #fca5a5; color:#ef4444; background:transparent;"
                                onmouseover="this.style.background='#fef2f2'"
                                onmouseout="this.style.background='transparent'"
                                class="px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors">
                                <i class="fas fa-ban"></i>
                                Nonaktifkan Metadata
                            </button>
                        </form>
                    @elseif($metadata->status == 3)
                        <form action="{{ route('metadata.reactivate', $metadata->metadata_id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="background:#22c55e; color:#fff;"
                                onmouseover="this.style.background='#16a34a'"
                                onmouseout="this.style.background='#22c55e'"
                                class="px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors shadow-md">
                                <i class="fas fa-redo"></i>
                                Aktifkan Kembali
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('metadata.approval') }}"
                       class="px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors"
                       style="border:1.5px solid #d1d5db; color:#6b7280; background:transparent;"
                       onmouseover="this.style.background='#f9fafb'"
                       onmouseout="this.style.background='transparent'">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    @endif

</div>

<script>
    function updateDateTime() {
        const now = new Date();
        document.getElementById('current-date').textContent =
            now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        document.getElementById('current-time').textContent =
            now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' }) + ' WITA';
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>

@endsection