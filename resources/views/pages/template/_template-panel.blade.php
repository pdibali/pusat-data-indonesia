@php
    $activeTemplateId = (int) request('template_id', 0);
    $tahunOpts        = range(2010, 2030);
    $semesterOpts     = [1 => 'Semester 1', 2 => 'Semester 2'];
    $kuartalOpts      = [1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'];
    $bulanOpts        = [
        1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
        5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
        9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember',
    ];

    $isCustomer = auth()->check() && optional(Auth::user())->group_id === 3;
    $quotaFull  = $isCustomer && ($templateLimit ?? 0) > 0 && ($templateUsage ?? 0) >= ($templateLimit ?? 0);
@endphp

{{-- ══════════════════════════════════════════════════════════
     STYLES — diletakkan di atas agar tidak FOUC
══════════════════════════════════════════════════════════ --}}
<style>
    /* ── Mobile card accordion ── */
    @media (max-width: 639px) {
        #pivotTable { display: none !important; }
        #mobileCardList { display: flex; flex-direction: column; gap: 0.75rem; }
    }
    @media (min-width: 640px) {
        #mobileCardList { display: none !important; }
    }

    .meta-card { border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; }
    .meta-card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.75rem 1rem; background: #f9fafb; cursor: pointer;
        gap: 0.5rem;
    }
    .meta-card-header:active { background: #f3f4f6; }
    .meta-card-body { display: none; }
    .meta-card-body.open { display: block; }
    .meta-card-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.5rem 1rem; border-top: 1px solid #f3f4f6; font-size: 0.75rem;
    }
    .meta-card-row:nth-child(even) { background: #fafafa; }
    /* ── Card utama tidak boleh memotong scroll ── */
    .tp-panel-card {
        overflow: visible !important;
        min-width: 0;           
    }

    #tableAccessOverlay {
        position: absolute;
        inset: 0;
        z-index: 40;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        border-radius: 0.5rem;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        background: rgba(255, 255, 255, 0.75); /* sedikit lebih opaque agar blur lebih solid */
        pointer-events: all;                    /* blokir semua klik di bawahnya */
        cursor: default;
    }

    #tableAccessOverlay:not(.hidden) {
        display: flex;
    }

    /* ── Tabel: lebar min-content agar tidak di-squeeze ── */
    #pivotTable {
        border-collapse: collapse;
        width: max-content;   /* ← KUNCI: tabel melebar sesuai konten */
        min-width: 100%;
        table-layout: auto;
        font-size: 0.813rem;
    }

    /* ── Sticky kolom kiri ── */
    #pivotTable th.col-sticky,
    #pivotTable td.col-sticky {
        position: sticky;
        z-index: 10;
    }
    /* Kolom Nama Metadata */
    #pivotTable th.col-sticky-nama,
    #pivotTable td.col-sticky-nama {
        left: 0;
        min-width: 140px;
        max-width: none;      /* dihapus */
        width: auto;          /* auto, bukan fixed */
        background: inherit;
        word-break: normal;
        white-space: normal;  /* boleh wrap ke 2 baris kalau perlu */
        line-height: 1.3;
    }

    .loc-text {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: bottom;
    }
    /* Freeze header baris */
    #pivotTable thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #f9fafb;
    }
    /* Sudut kiri-atas: sticky horizontal + vertikal */
    #pivotTable thead th.col-sticky-nama {
        z-index: 30;
        background: #f9fafb;
    }
    /* ── Garis kanan sticky kolom ── */
    #pivotTable th.col-sticky-nama,
    #pivotTable td.col-sticky-nama {
        border-right: 2px solid #e5e7eb;
        box-shadow: 2px 0 4px -2px rgba(0,0,0,0.08);
    }

    /* ── Warna baris zebra ── */
    #pivotTable tbody tr:nth-child(even) td {
        background-color: #f9fafb;
    }
    #pivotTable tbody tr:nth-child(even) td.col-sticky {
        background-color: #f9fafb;
    }
    #pivotTable tbody tr:nth-child(odd) td.col-sticky {
        background-color: #ffffff;
    }
    #pivotTable tbody tr:hover td {
        background-color: #f5f3ff !important;
    }

    /* ── Indentasi wilayah ── */
    .loc-indent-0 { padding-left: 0.5rem; }
    .loc-indent-1 { padding-left: 1.5rem; }
    .loc-indent-2 { padding-left: 2.5rem; }
    .loc-indent-3 { padding-left: 3.5rem; }
    .loc-indent-1::before { content: '└ '; color: #d1d5db; margin-right: 2px; }
    .loc-indent-2::before { content: '  └ '; color: #d1d5db; margin-right: 2px; white-space: pre; }
    .loc-indent-3::before { content: '    └ '; color: #d1d5db; margin-right: 2px; white-space: pre; }

    /* ── Separator antar metadata ── */
    tr.metadata-separator td {
        height: 2px;
        padding: 0;
        background: #e5e7eb;
    }

    /* ── Step badge ── */
    .tp-step-badge {
        width: 1.25rem; height: 1.25rem;
        border-radius: 9999px;
        ; color: #fff;
        font-size: 0.65rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    /* ── Select ── */
    .tp-select {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        background: #fff;
        outline: none;
        transition: box-shadow 0.15s, border-color 0.15s;
        cursor: pointer;
    }
    .tp-select:focus {
        box-shadow: 0 0 0 2px #a78bfa;
        border-color: #8b5cf6;
    }

    /* ── Frekuensi tombol ── */
    .freq-btn:disabled {
        pointer-events: none;
        opacity: 0.40;
        filter: grayscale(0.6);
        cursor: not-allowed;
    }
    .freq-count-badge {
        display: inline-block;
        vertical-align: middle;
    }

    /* ── Tombol Grafik di tabel ── */
    .btn-grafik {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.625rem;
        font-size: 0.7rem;
        font-weight: 500;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        color: #6b7280;
        background: #fff;
        white-space: nowrap;
        transition: border-color 0.15s, background 0.15s, color 0.15s;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-grafik:hover {
        border-color: #a78bfa;
        background: #f5f3ff;
        color: #7c3aed;
    }
</style>

<div class="tp-panel-card mt-2 bg-white rounded-xl shadow p-6">

    {{-- ════ HEADER ════ --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Tampilkan Data</h2>
            <p class="text-sm text-gray-400 mt-1">
                Silakan membuat template terlebih dahulu untuk memilih data yang ingin ditampilkan.
            </p>
        </div>
        <a href="{{ $quotaFull ? '#' : route('template.create') }}"
        id="btnBuatTemplateHeader"
        @if($quotaFull) onclick="return false;" aria-disabled="true" title="Kuota template Anda sudah penuh ({{ $templateUsage }}/{{ $templateLimit }})" @endif
        class="w-full sm:w-auto px-4 py-2.5 text-sm font-semibold rounded-lg
                flex items-center justify-center gap-2 transition-colors shrink-0
                {{ $quotaFull
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed pointer-events-none'
                        : 'btn-primary shadow-md shadow-blue-400/30' }}">
            <i class="fas fa-plus"></i> Buat Template
        </a>
    </div>

    {{-- ════════════════════════════════════════
         STEP 1 — Pilih Template
    ════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="tp-step-badge bg-sky-500">1</span>
            <p class="text-sm font-semibold text-gray-700">Pilih Template</p>
        </div>

        @auth
            @if(optional(Auth::user())->group_id === 3)
                <div class="mb-3 rounded-lg border border-sky-100 bg-sky-50 px-3 py-2 text-sm text-sky-700">
                    Kuota template: <span class="font-semibold">{{ $templateUsage ?? 0 }}</span> dari <span class="font-semibold">{{ $templateLimit ?? 0 }}</span> terpakai.
                </div>
            @endif
        @endauth

        @auth
            @if($availableTemplates->isEmpty())
                <div class="flex flex-col w-full border border-gray-300 rounded-lg text-sm text-gray-500">
                    <div class="border-b border-gray-300 px-3 py-2 text-xs font-medium text-gray-600">
                        Daftar Template
                    </div>
                    <div class="flex flex-col items-center gap-3 py-12 text-gray-400">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-layer-group text-gray-300 text-xl"></i>
                        </div>
                        <p class="font-medium text-gray-500">Belum ada template</p>
                        <p class="text-xs text-gray-400">Buat template pertama Anda untuk memudahkan akses data</p>
                        <a href="{{ $quotaFull ? '#' : route('template.create') }}"
                        @if($quotaFull) onclick="return false;" aria-disabled="true" title="Kuota template Anda sudah penuh" @endif
                        class="mt-1 px-4 py-2 text-xs font-semibold rounded-lg transition-colors
                                {{ $quotaFull
                                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed pointer-events-none'
                                        : 'bg-stikom-blue hover:bg-blue-700 text-white' }}">
                            <i class="fas fa-plus mr-1"></i> Buat Template
                        </a>
                    </div>
                </div>
            @else
                <div class="flex flex-col w-full border border-gray-300 rounded-lg">
                    <div class="border-b border-gray-300 px-3 py-2 text-xs font-medium text-gray-600">
                        Daftar Template
                        <span class="text-gray-400">({{ $availableTemplates->count() }})</span>
                    </div>
                    <div class="flex flex-col gap-2 my-3 mx-3 max-h-52 overflow-y-auto pr-1
                                scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
                        @foreach($availableTemplates as $tmpl)
                            @php
                                $fp         = $tmpl->filter_params ?? [];
                                $jenis      = $fp['jenis_template'] ?? 'metadata';
                                $jenisLabel = [
                                    'metadata'    => 'Metadata',
                                    'klasifikasi' => 'Klasifikasi',
                                    'wilayah'     => 'Wilayah',
                                ][$jenis] ?? $jenis;
                                $isActive = $activeTemplateId === (int) $tmpl->tampilan_id;
                                $isLocked = $tmpl->is_locked ?? false;
                            @endphp
        
                            {{--
                                MOBILE BADGE — 2 baris, tidak pakai grid-cols-13
                                Baris 1: nama template + aksi (edit/hapus) di kanan
                                Baris 2: meta info (jenis · count · tanggal)
                            --}}
                            <div class="w-full border-2 rounded-lg px-4 py-3
                                        text-xs font-semibold transition-all duration-150
                                        {{ $isLocked
                                            ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                                            : ($isActive
                                                ? 'border-sky-500 bg-sky-500 text-white cursor-pointer'
                                                : 'border-sky-300 text-sky-500 hover:bg-sky-500 hover:text-white cursor-pointer') }}"
                                onclick="selectTemplate({{ $tmpl->tampilan_id }}, {{ $isLocked ? 'true' : 'false' }})">
        
                                {{-- Baris 1: Nama + aksi --}}
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold text-xs leading-tight">{{ $tmpl->nama_tampilan }}</p>
        
                                    {{-- Aksi: edit + hapus --}}
                                    <div class="flex items-center gap-2.5 shrink-0 ml-1"
                                        onclick="event.stopPropagation()">
                                        @if(!$isLocked)
                                            <a href="{{ route('template.edit', $tmpl->tampilan_id) }}"
                                            class="{{ $isActive ? 'text-white/80 hover:text-white' : 'text-sky-400 hover:text-sky-600' }} transition-colors"
                                            title="Edit">
                                                <i class="fas fa-edit text-xs"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('template.destroy', $tmpl->tampilan_id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Hapus template \'{{ addslashes($tmpl->nama_tampilan) }}\'?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="{{ $isLocked ? 'text-gray-400 hover:text-red-500' : ($isActive ? 'text-white/80 hover:text-white' : 'text-sky-400 hover:text-red-500') }} transition-colors"
                                                    title="Hapus">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
        
                                {{-- Baris 2: meta info --}}
                                <div class="mt-1.5 flex flex-wrap items-center text-xs gap-x-2 gap-y-0.5 font-normal
                                            {{ $isLocked ? 'text-gray-400' : ($isActive ? 'text-white/75' : 'opacity-70') }}">
                                    <span>{{ $jenisLabel }}</span>
                                    <span class="opacity-50">·</span>
                                    <span>{{ $tmpl->isi_tampilan_count ?? 0 }} data</span>
                                    <span class="opacity-50">·</span>
                                    <span>Diperbarui {{ $tmpl->updated_at?->format('d-m-Y H.i') }}</span>
                                </div>

                                @if($isLocked)
                                    <div class="mt-2 inline-flex items-center gap-1 px-2 py-1 rounded-full
                                                bg-amber-100 text-amber-700 text-[10px] font-bold">
                                        <i class="fas fa-lock text-[9px]"></i>
                                        Templat lama terkunci
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endauth

        @guest
            {{-- Container yang diisi JS --}}
            <div class="flex flex-col w-full border border-gray-300 rounded-lg">
                <div class="border-b border-gray-300 px-3 py-2 text-xs font-medium text-gray-600">
                    Daftar Template
                </div>
                <div id="guestTemplateList"
                    class="flex flex-col gap-2 my-3 mx-3 max-h-52 overflow-y-auto pr-1
                            scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
                    {{-- Diisi oleh loadGuestTemplates() --}}
                </div>
            </div>
        @endguest
    </div>

    {{-- ════════════════════════════════════════
         STEP 2 — Pilih Frekuensi
    ════════════════════════════════════════ --}}
    <div id="stepFrekuensi" class="{{ $activeTemplateId ? '' : 'hidden' }} mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="tp-step-badge bg-sky-500">2</span>
            <p class="text-sm font-semibold text-gray-700">Pilih Frekuensi Rentang Waktu</p>
        </div>

        {{-- Loading indicator frekuensi --}}
        <div id="freqLoadingHint" class="mb-2 text-xs text-gray-400 hidden">
            <i class="fas fa-circle-notch fa-spin mr-1 text-sky-400"></i>
            Memeriksa ketersediaan data per frekuensi...
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
            @foreach([
                '10tahunan'  => ['label' => '10 Tahunan',  'sub' => 'Setiap 10 tahun'],
                'tahunan'    => ['label' => 'Tahunan',     'sub' => 'Per tahun'],
                'semesteran' => ['label' => 'Semesteran',  'sub' => 'S1 / S2'],
                'kuartal'    => ['label' => 'Kuartal',     'sub' => 'Q1 – Q4'],
                'bulanan'    => ['label' => 'Bulanan',     'sub' => 'Jan – Des'],
                'custom'     => ['label' => 'Custom',      'sub' => 'Rentang bebas'],
            ] as $key => $opt)
                <button type="button"
                        id="freq-btn-{{ $key }}"
                        onclick="selectFrekuensi('{{ $key }}')"
                        class="freq-btn border-2 border-gray-200 rounded-xl p-3 text-left
                               hover:border-sky-400 hover:bg-sky-50 transition-all duration-150
                               {{ $key === 'custom' ? 'border-dashed' : '' }}"
                        disabled>
                    <p class="text-xs font-semibold text-gray-700 flex items-center gap-1 flex-wrap">
                        {{ $opt['label'] }}
                        @if($key === 'custom')
                            <i class="fas fa-sliders-h text-gray-400"></i>
                        @endif
                        <span id="freq-badge-{{ $key }}"
                              class="freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full
                                     bg-gray-100 text-gray-400">
                            …
                        </span>
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $opt['sub'] }}</p>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ════════════════════════════════════════
         STEP 3 — Rentang Periode
    ════════════════════════════════════════ --}}
    <div id="stepPeriode" class="hidden mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="tp-step-badge bg-sky-500">3</span>
            <p class="text-sm font-semibold text-gray-700">Tentukan Rentang Periode</p>
        </div>

        {{-- Info helper --}}
        <div id="periodeHelperText" class="mb-3 text-xs text-sky-600 bg-sky-50 border border-sky-200 rounded-lg px-3 py-2 hidden">
            <i class="fas fa-info-circle mr-1"></i>
            <span id="periodeHelperMsg"></span>
        </div>

        {{-- ── 10 Tahunan ─────────────────────── --}}
        <div id="periode10tahunan" class="hidden">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-3">Pilih rentang dekade (setiap 10 tahun)</p>
                <div class="flex flex-wrap gap-2 mb-3" id="preset10tahunan"></div>
                <p class="text-xs text-gray-400 mt-2">Atau pilih manual:</p>
                <div class="flex flex-wrap items-end gap-3 mt-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Dari Dekade</label>
                        <select id="from10" onchange="checkPeriode10()" class="tp-select min-w-32">
                            <option value="">Pilih...</option>
                            @foreach([1950,1960,1970,1980,1990,2000,2010,2020,2030,2040] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-gray-400 mb-2">—</span>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sampai Dekade</label>
                        <select id="to10" onchange="checkPeriode10()" class="tp-select min-w-32">
                            <option value="">Pilih...</option>
                            @foreach([1950,1960,1970,1980,1990,2000,2010,2020,2030,2040] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="tahunanKolomInfo" class="hidden mt-2">
                    <span class="text-xs text-sky-600 font-medium" id="tahunanKolom"></span>
                </div>
            </div>
        </div>

        {{-- ── Tahunan ──────────────────────────── --}}
        <div id="periodeTahunan" class="hidden">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-3">Pilih rentang tahun</p>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach(['5'=>'5 Tahun','10'=>'10 Tahun','15'=>'15 Tahun','20'=>'20 Tahun'] as $n => $label)
                        <button type="button" onclick="applyPresetTahunan({{ $n }})"
                                class="px-3 py-1.5 text-xs border border-sky-200 text-sky-600
                                       bg-sky-50 hover:bg-sky-100 rounded-lg transition-colors font-medium">
                            {{ $label }} Terakhir
                        </button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Dari Tahun</label>
                        <select id="fromTahunan" onchange="checkPeriodeTahunan()" class="tp-select min-w-32">
                            <option value="">Pilih...</option>
                            @foreach($tahunOpts as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-gray-400 mb-2">—</span>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sampai Tahun</label>
                        <select id="toTahunan" onchange="checkPeriodeTahunan()" class="tp-select min-w-32">
                            <option value="">Pilih...</option>
                            @foreach($tahunOpts as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="tahunanKolomInfoTahunan" class="hidden mt-2">
                    <span class="text-xs text-sky-600 font-medium" id="tahunanKolomTahunan"></span>
                </div>
            </div>
        </div>

        {{-- ── Complex: semesteran / kuartal / bulanan ─── --}}
        <div id="periodeComplex" class="hidden">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-4">
                <div>
                    <p class="text-xs text-gray-500 mb-2">Pilih rentang tahun</p>
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach(['3'=>'3 Tahun','5'=>'5 Tahun','10'=>'10 Tahun'] as $n => $label)
                            <button type="button" onclick="applyPresetComplex({{ $n }})"
                                    class="px-3 py-1.5 text-xs border border-sky-200 text-sky-600
                                           bg-sky-50 hover:bg-sky-100 rounded-lg transition-colors font-medium">
                                {{ $label }} Terakhir
                            </button>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun Dari</label>
                            <select id="yearFrom" onchange="checkPeriodeComplex()" class="tp-select min-w-32">
                                <option value="">Pilih...</option>
                                @foreach($tahunOpts as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <span class="text-gray-400 mb-2">—</span>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun Sampai</label>
                            <select id="yearTo" onchange="checkPeriodeComplex()" class="tp-select min-w-32">
                                <option value="">Pilih...</option>
                                @foreach($tahunOpts as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2" id="periodeComplexLabel">Periode</label>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Dari</label>
                            <select id="periodFromComplex" onchange="checkPeriodeComplex()" class="tp-select min-w-36">
                                <option value="">Semua (opsional)</option>
                            </select>
                        </div>
                        <span class="text-gray-400 mb-2">—</span>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                            <select id="periodToComplex" onchange="checkPeriodeComplex()" class="tp-select min-w-36">
                                <option value="">Semua (opsional)</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Kosongkan untuk mengambil semua periode dalam rentang tahun yang dipilih.
                    </p>
                </div>

                <div id="complexKolomInfo" class="hidden">
                    <span class="text-xs text-sky-600 font-medium" id="complexKolom"></span>
                </div>
            </div>
        </div>

        {{-- ── Custom ─────────────────────────────── --}}
        <div id="periodeCustom" class="hidden">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-4">
                <p class="text-xs text-amber-700 font-medium">
                    <i class="fas fa-sliders-h mr-1"></i>
                    Rentang Custom — Pilih tahun dari–sampai secara bebas
                </p>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Dari Tahun</label>
                        <select id="customYearFrom" onchange="checkPeriodeCustom()" class="tp-select min-w-32">
                            <option value="">Pilih...</option>
                            @foreach($tahunOpts as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-gray-400 mb-2">—</span>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sampai Tahun</label>
                        <select id="customYearTo" onchange="checkPeriodeCustom()" class="tp-select min-w-32">
                            <option value="">Pilih...</option>
                            @foreach($tahunOpts as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="block text-xs text-gray-500 mb-1">Satuan</label>
                        <select id="customUnit" onchange="checkPeriodeCustom()" class="tp-select">
                            <option value="tahunan">Per Tahun</option>
                            <option value="semesteran">Per Semester</option>
                            <option value="kuartal">Per Kuartal</option>
                            <option value="bulanan">Per Bulan</option>
                        </select>
                    </div>
                </div>
                <div id="customKolomInfo" class="hidden">
                    <span class="text-xs text-amber-700 font-medium" id="customKolom"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         STEP 4 — Tombol Tampilkan Data
    ════════════════════════════════════════ --}}
    <div id="stepTampilkan" class="hidden mb-6">
        <button type="button" onclick="tampilkanData()"
                class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm
                       font-semibold rounded-lg shadow-md shadow-sky-400/30
                       flex items-center gap-2 transition-colors">
            <i class="fas fa-table"></i> Tampilkan Data
        </button>
    </div>

    {{-- ════════════════════════════════════════
         HASIL TABEL PIVOT
    ════════════════════════════════════════ --}}
    <div id="dataTableSection" class="hidden">
        <hr class="my-5 border-gray-100">

        {{-- Info bar --}}
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <div>
                <p class="text-sm font-semibold text-gray-700" id="tableInfoText">Memuat data...</p>
                <p class="text-xs text-gray-400 mt-0.5" id="tableSubInfo"></p>
            </div>
            <div class="flex gap-2 flex-wrap">
                {{-- ── Tombol Export (Dropdown) ── --}}
                <div class="relative" id="exportBtnGroup" style="display:none!important">
                    <button type="button" onclick="toggleExportDropdown()"
                            class="px-3 py-1.5 border border-gray-300 hover:bg-gray-50 text-gray-600
                                text-xs font-medium rounded-lg flex items-center gap-1.5 transition-colors">
                        <i class="fas fa-download text-xs"></i> Export
                        <i class="fas fa-chevron-down text-[10px] ml-0.5" id="exportChevron"></i>
                    </button>

                    <div id="exportDropdown"
                        class="hidden absolute left-0 right-0 mt-1 w-44 bg-white border border-gray-200
                                rounded-lg shadow-lg z-50 overflow-hidden">
                        <button onclick="exportData('excel'); closeExportDropdown()"
                                class="w-full px-4 py-2.5 text-left text-xs flex items-center gap-2.5
                                    text-emerald-600 hover:bg-emerald-50 transition-colors">
                            <i class="fas fa-file-excel w-3.5 text-center"></i> Export Excel
                        </button>
                        <button onclick="exportData('pdf'); closeExportDropdown()"
                                class="w-full px-4 py-2.5 text-left text-xs flex items-center gap-2.5
                                    text-red-500 hover:bg-red-50 transition-colors border-t border-gray-100">
                            <i class="fas fa-file-pdf w-3.5 text-center"></i> Export PDF
                        </button>
                        <button onclick="exportData('json'); closeExportDropdown()"
                                class="w-full px-4 py-2.5 text-left text-xs flex items-center gap-2.5
                                    text-gray-500 hover:bg-gray-50 transition-colors border-t border-gray-100">
                            <i class="fas fa-code w-3.5 text-center"></i> Export JSON
                        </button>
                    </div>
                </div>
                <button type="button" onclick="resetFilter()"
                        class="px-3 py-1.5 border border-gray-300 hover:bg-gray-50 text-gray-500
                               text-xs font-medium rounded-lg flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-times text-xs"></i> Reset
                </button>
            </div>
        </div>

        {{-- Loading --}}
        <div id="tableLoading"
             class="hidden flex flex-col items-center gap-3 py-14 text-gray-400">
            <i class="fas fa-circle-notch fa-spin text-sky-400 text-3xl"></i>
            <p class="text-sm">Memuat data...</p>
        </div>

        {{-- Empty --}}
        <div id="tableEmpty"
             class="hidden flex flex-col items-center gap-3 py-14
                    border-2 border-dashed border-gray-200 rounded-xl text-gray-400">
            <i class="fas fa-inbox text-4xl text-gray-200"></i>
            <p class="text-sm font-medium text-gray-500" id="tableEmptyMsg">
                Tidak ada data ditemukan
            </p>
            <p class="text-xs">Coba ubah filter atau rentang periode</p>
        </div>

            <div id="tableWrap" class="hidden grid grid-cols-2 gap-5 my-4">
                {{-- Wrapper relative untuk overlay + scroll ──────────────── --}}
                <div class="col-span-2 relative">

                    {{-- Overlay di sini — di luar div scroll, tapi dalam wrapper relative --}}
                    <div id="tableAccessOverlay" class="hidden">
                        <div class="flex flex-col items-center gap-4 text-center px-6">
                            <div class="w-16 h-16 rounded-full bg-sky-100 flex items-center justify-center shadow-inner">
                                <i class="fas fa-lock text-sky-500 text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-800 font-bold text-base">Data Terkunci</p>
                                <p class="text-gray-500 text-sm mt-1">
                                    Berlangganan untuk mengakses data lengkap dari template ini.
                                </p>
                            </div>
                            <a href="{{ route('langganan') }}"
                            class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm
                                    font-semibold rounded-lg shadow-md shadow-sky-400/30
                                    flex items-center gap-2 transition-colors">
                                <i class="fas fa-crown"></i> Lihat Paket Langganan
                            </a>
                        </div>
                    </div>

                    {{-- Div scroll tabel — terpisah dari overlay --}}
                    <div class="border border-gray-300 rounded-lg w-full max-h-100 overflow-auto">
                        <table id="pivotTable">
                            <thead id="pivotHead"></thead>
                            <tbody id="pivotBody"></tbody>
                        </table>

                        {{-- Mobile card list — diisi JS, tersembunyi di desktop --}}
                        <div id="mobileCardList" class="p-3"></div>
                    </div>

                </div>
            </div>
        
        

        {{-- Pagination --}}
        <div id="tablePagination"
             class="hidden mt-4 flex items-center justify-between text-xs text-gray-500 flex-wrap gap-2">
            <span id="paginationInfo"></span>
            <div id="paginationBtns" class="flex gap-1 flex-wrap"></div>
        </div>
    </div>
    
    @include('pages.template.metadata-detail-modal')

</div>

{{-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ --}}
{{-- Pass akses ke JS --}}
<script>
    const TP_HAS_ACCESS = @json($hasAccess ?? true);
</script>
<script>

const IS_GUEST = {{ Auth::check() ? 'false' : 'true' }};

// ─── Konstanta ────────────────────────────────────────────────
const TP_PERIODE_OPTS = {
    semesteran : [[1,'Semester 1'],[2,'Semester 2']],
    kuartal    : [[1,'Q1'],[2,'Q2'],[3,'Q3'],[4,'Q4']],
    bulanan    : [
        [1,'Januari'],[2,'Februari'],[3,'Maret'],[4,'April'],
        [5,'Mei'],[6,'Juni'],[7,'Juli'],[8,'Agustus'],
        [9,'September'],[10,'Oktober'],[11,'November'],[12,'Desember'],
    ],
};

const TP_FREK_LABEL = {
    '10tahunan':'10 Tahunan','5tahunan':'5 Tahunan','tahunan':'Tahunan',
    'semesteran':'Semesteran','kuartal':'Kuartal','bulanan':'Bulanan','custom':'Custom',
};

const TMPL_URLS = {
    base            : '{{ route("data.index") }}',
    tableData       : '{{ route("template.table_data") }}',
    freqCounts      : '{{ route("template.freq_counts") }}',
    tableDataGuest : '{{ route("template.table_data_guest") }}',
    freqCountsGuest: '{{ route("template.freq_counts_guest") }}',
    grafik          : '{{ route("template.grafik") }}',
    csrf            : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    metadataDetail  : '{{ url("metadata") }}',
    exportExcel     : '{{ route("template.export.excel") }}',
    exportPdf       : '{{ route("template.export.pdf") }}',
    exportJson      : '{{ route("template.export.json") }}',
};

// State filter aktif
const TS = {
    tampilan_id : {{ $activeTemplateId ?: 'null' }},
    frekuensi   : null,
    year_from   : null,
    year_to     : null,
    period_from : null,
    period_to   : null,
    custom_unit : null,
    page        : 1,
};

// State guest template (dari localStorage)
let guestTemplates = [];
let activeGuestTemplate = null; // object template yang sedang aktif

// ─────────────────────────────────────────────────────────────
// STEP 1 — Pilih template
// ─────────────────────────────────────────────────────────────
function selectTemplate(id, isLocked = false) {
    if (isLocked) {
        window.location.href = '{{ route('langganan') }}';
        return;
    }

    const url = (TS.tampilan_id === id)
        ? TMPL_URLS.base
        : TMPL_URLS.base + '?template_id=' + id;
    window.location.href = url;
}

// ─────────────────────────────────────────────────────────────
// STEP 2 — Load & render freq-counts badges
// ─────────────────────────────────────────────────────────────
async function loadFreqCounts(tampilanId) {
    if (!tampilanId) return;

    const freqKeys = ['10tahunan', 'tahunan', 'semesteran', 'kuartal', 'bulanan', 'custom'];

    // Tampilkan hint loading
    const hint = document.getElementById('freqLoadingHint');
    if (hint) hint.classList.remove('hidden');

    // Set semua badge ke "…" dan disabled sementara
    freqKeys.forEach(freq => {
        const btn   = document.getElementById('freq-btn-' + freq);
        const badge = document.getElementById('freq-badge-' + freq);
        if (btn)   { btn.disabled = true; }
        if (badge) { badge.textContent = '…'; badge.className = 'freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400'; }
    });

    try {
        const res  = await fetch(`${TMPL_URLS.freqCounts}?tampilan_id=${tampilanId}`);
        const data = await res.json();

        freqKeys.forEach(freq => {
            const btn   = document.getElementById('freq-btn-' + freq);
            const badge = document.getElementById('freq-badge-' + freq);
            if (!btn) return;

            const count = data[freq] ?? 0;

            if (count > 0) {
                // ── Ada data → aktifkan tombol ──
                btn.disabled = false;
                btn.removeAttribute('disabled');
                // Styling tombol aktif (enabled)
                btn.classList.remove('opacity-40', 'cursor-not-allowed');
                btn.classList.add('cursor-pointer');

                if (badge) {
                    badge.className = 'freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-sky-100 text-sky-700';
                    badge.textContent = count;
                    badge.title = `${count} metadata tersedia`;
                }
                btn.title = `${count} metadata tersedia`;
            } else {
                // ── Tidak ada data → disable ──
                btn.disabled = true;
                // Reset border/background jika sebelumnya aktif
                btn.classList.remove('border-sky-500','bg-sky-50','border-amber-400','bg-amber-50');
                btn.classList.add('border-gray-200');

                if (badge) {
                    badge.className = 'freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400';
                    badge.textContent = '0';
                }
                btn.title = 'Tidak ada data untuk frekuensi ini';
            }
        });

    } catch (e) {
        console.warn('Gagal memuat freq counts:', e);
        // Saat error → enable semua agar tidak memblokir user
        freqKeys.forEach(freq => {
            const btn   = document.getElementById('freq-btn-' + freq);
            const badge = document.getElementById('freq-badge-' + freq);
            if (btn)   btn.disabled = false;
            if (badge) { badge.textContent = '?'; }
        });
    } finally {
        if (hint) hint.classList.add('hidden');
    }
}

// ─────────────────────────────────────────────────────────────
// STEP 2 — Pilih frekuensi
// ─────────────────────────────────────────────────────────────
function selectFrekuensi(freq) {
    TS.frekuensi = freq;
    TS.year_from = TS.year_to = TS.period_from = TS.period_to = null;
    TS.page      = 1;

    // Highlight tombol aktif
    document.querySelectorAll('.freq-btn').forEach(btn => {
        btn.classList.remove('border-sky-500','bg-sky-50','border-amber-400','bg-amber-50');
        btn.classList.add('border-gray-200');
    });
    const ab = document.getElementById('freq-btn-' + freq);
    if (ab) {
        ab.classList.remove('border-gray-200');
        if (freq === 'custom') {
            ab.classList.add('border-amber-400','bg-amber-50');
        } else {
            ab.classList.add('border-sky-500','bg-sky-50');
        }
    }

    _resetDataTable();
    document.getElementById('stepTampilkan').classList.add('hidden');
    document.getElementById('stepPeriode').classList.remove('hidden');

    // Helper text
    const helperMap = {
        '10tahunan'  : '10 Tahunan: setiap titik = 1 dekade (10 tahun). Jumlah kolom = jumlah dekade dalam rentang.',
        'tahunan'    : 'Tahunan: 1 kolom per tahun. Misal 2020–2025 = 6 kolom.',
        'semesteran' : 'Semesteran: 1 kolom per semester (S1/S2). Misal 2021–2022 = 4 kolom.',
        'kuartal'    : 'Kuartal: 1 kolom per kuartal (Q1–Q4). Misal 2021–2022 = 8 kolom.',
        'bulanan'    : 'Bulanan: 1 kolom per bulan. Misal 2021–2022 = 24 kolom.',
        'custom'     : 'Custom: pilih tahun bebas dan satuan tampilan kolom.',
    };
    const helperEl  = document.getElementById('periodeHelperText');
    const helperMsg = document.getElementById('periodeHelperMsg');
    if (helperMap[freq]) {
        helperEl.classList.remove('hidden');
        helperMsg.textContent = helperMap[freq];
    } else {
        helperEl.classList.add('hidden');
    }

    // Sembunyikan semua panel periode
    ['periode10tahunan','periodeTahunan','periodeComplex','periodeCustom']
        .forEach(id => document.getElementById(id)?.classList.add('hidden'));

    if (freq === '10tahunan') {
        document.getElementById('periode10tahunan').classList.remove('hidden');
        _buildPreset10();
    } else if (freq === 'tahunan') {
        document.getElementById('periodeTahunan').classList.remove('hidden');
    } else if (freq === 'custom') {
        document.getElementById('periodeCustom').classList.remove('hidden');
    } else {
        // semesteran, kuartal, bulanan
        document.getElementById('periodeComplex').classList.remove('hidden');
        const labelMap = { 'semesteran':'Semester', 'kuartal':'Kuartal', 'bulanan':'Bulan' };
        document.getElementById('periodeComplexLabel').textContent = labelMap[freq] ?? 'Periode';

        const opts = TP_PERIODE_OPTS[freq] ?? [];
        ['periodFromComplex','periodToComplex'].forEach(selId => {
            const el = document.getElementById(selId);
            if (!el) return;
            el.innerHTML = '<option value="">Semua (opsional)</option>';
            opts.forEach(([v, label]) => {
                const o = document.createElement('option');
                o.value = v; o.textContent = label;
                el.appendChild(o);
            });
        });
        ['yearFrom','yearTo','periodFromComplex','periodToComplex']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    }
}

// ─── Preset tombol cepat 10tahunan ────────────────────────────
function _buildPreset10() {
    const container = document.getElementById('preset10tahunan');
    container.innerHTML = '';
    const curYear   = new Date().getFullYear();
    const curDecade = Math.floor(curYear / 10) * 10;
    const presets   = [
        { from: curDecade,      to: curDecade,      label: 'Dekade Ini' },
        { from: curDecade - 10, to: curDecade,       label: '2 Dekade Terakhir' },
        { from: curDecade - 20, to: curDecade,       label: '3 Dekade Terakhir' },
        { from: 1990,           to: curDecade,       label: 'Sejak 1990' },
    ];
    presets.forEach(p => {
        const btn = document.createElement('button');
        btn.type  = 'button';
        btn.textContent = p.label;
        btn.className   = 'px-3 py-1.5 text-xs border border-sky-200 text-sky-600 bg-sky-50 hover:bg-sky-100 rounded-lg transition-colors font-medium';
        btn.onclick = () => {
            document.getElementById('from10').value = p.from;
            document.getElementById('to10').value   = p.to;
            checkPeriode10();
        };
        container.appendChild(btn);
    });
}

// ─── Preset "N tahun terakhir" ────────────────────────────────
function applyPresetTahunan(n) {
    const curYear = new Date().getFullYear();
    document.getElementById('fromTahunan').value = curYear - n + 1;
    document.getElementById('toTahunan').value   = curYear;
    checkPeriodeTahunan();
}

function applyPresetComplex(n) {
    const curYear = new Date().getFullYear();
    document.getElementById('yearFrom').value = curYear - n + 1;
    document.getElementById('yearTo').value   = curYear;
    checkPeriodeComplex();
}

// ─── STEP 3 — Cek validitas & preview kolom ──────────────────

function checkPeriode10() {
    const f = +document.getElementById('from10').value;
    const t = +document.getElementById('to10').value;
    if (f && t) {
        TS.period_from = f; TS.period_to = t;
        TS.year_from   = f; TS.year_to   = t;
        const count = Math.floor((t - f) / 10) + 1;
        showKolomPreview('tahunanKolom', 'tahunanKolomInfo', `→ ${count} kolom dekade`);
        document.getElementById('stepTampilkan').classList.remove('hidden');
    } else {
        document.getElementById('stepTampilkan').classList.add('hidden');
    }
}

function checkPeriodeTahunan() {
    const f = +document.getElementById('fromTahunan').value;
    const t = +document.getElementById('toTahunan').value;
    if (f && t) {
        TS.year_from   = f; TS.year_to   = t;
        TS.period_from = f; TS.period_to = t;
        const count = t - f + 1;
        showKolomPreview('tahunanKolomTahunan', 'tahunanKolomInfoTahunan', `→ ${count} kolom tahun`);
        document.getElementById('stepTampilkan').classList.remove('hidden');
    } else {
        document.getElementById('stepTampilkan').classList.add('hidden');
    }
}

function checkPeriodeComplex() {
    const yFrom = +document.getElementById('yearFrom').value;
    const yTo   = +document.getElementById('yearTo').value;
    const pFrom = document.getElementById('periodFromComplex').value;
    const pTo   = document.getElementById('periodToComplex').value;

    if (yFrom && yTo) {
        TS.year_from   = yFrom;
        TS.year_to     = yTo;
        TS.period_from = pFrom ? +pFrom : null;
        TS.period_to   = pTo   ? +pTo   : null;

        const years   = yTo - yFrom + 1;
        const perYear = { semesteran: 2, kuartal: 4, bulanan: 12 }[TS.frekuensi] ?? 1;
        let count     = years * perYear;
        if (pFrom && pTo) count = years * (pTo - pFrom + 1);
        const unit = { semesteran:'semester', kuartal:'kuartal', bulanan:'bulan' }[TS.frekuensi] ?? 'periode';
        showKolomPreview('complexKolom', 'complexKolomInfo', `→ estimasi ${count} kolom ${unit}`);
        document.getElementById('stepTampilkan').classList.remove('hidden');
    } else {
        document.getElementById('stepTampilkan').classList.add('hidden');
    }
}

function checkPeriodeCustom() {
    const f    = +document.getElementById('customYearFrom').value;
    const t    = +document.getElementById('customYearTo').value;
    const unit = document.getElementById('customUnit').value;

    if (f && t) {
        TS.year_from   = f;
        TS.year_to     = t;
        TS.period_from = null;
        TS.period_to   = null;
        TS.custom_unit = unit;
        TS.frekuensi   = unit;

        const years   = t - f + 1;
        const perYear = { tahunan: 1, semesteran: 2, kuartal: 4, bulanan: 12 }[unit] ?? 1;
        const count   = years * perYear;
        const label   = { tahunan:'tahun', semesteran:'semester', kuartal:'kuartal', bulanan:'bulan' }[unit];
        showKolomPreview('customKolom', 'customKolomInfo', `→ ${count} kolom ${label} (${f}–${t})`);
        document.getElementById('stepTampilkan').classList.remove('hidden');
    } else {
        document.getElementById('stepTampilkan').classList.add('hidden');
    }
}

function showKolomPreview(textId, wrapId, text) {
    const el = document.getElementById(textId);
    const wr = document.getElementById(wrapId);
    if (el) el.textContent = text;
    if (wr) wr.classList.remove('hidden');
}

// ─────────────────────────────────────────────────────────────
// GUEST TEMPLATE — baca & render dari localStorage
// ─────────────────────────────────────────────────────────────

const LS_KEY = 'guest_templates';

function loadGuestTemplates() {
    if (!IS_GUEST) return;
    try {
        const raw = localStorage.getItem(LS_KEY);
        guestTemplates = raw ? JSON.parse(raw) : [];
    } catch (e) {
        guestTemplates = [];
    }
    renderGuestTemplates();
    _updateGuestQuotaUI();
}

function saveGuestTemplateFromResponse(templateData) {
    // Dipanggil dari response store() saat guest
    try {
        let existing = JSON.parse(localStorage.getItem(LS_KEY) ?? '[]');

        if (existing.length >= 2) {
            alert('Anda telah mencapai batas maksimal 2 template untuk pengguna guest. Login untuk menyimpan lebih banyak template.');
            return;
        }

        // Beri ID unik berbasis timestamp
        templateData._local_id = 'local_' + Date.now();
        existing.push(templateData);
        localStorage.setItem(LS_KEY, JSON.stringify(existing));
        guestTemplates = existing;
    } catch (e) {
        console.error('Gagal simpan template ke localStorage:', e);
    }
}

function deleteGuestTemplate(localId) {
    guestTemplates = guestTemplates.filter(t => t._local_id !== localId);
    localStorage.setItem(LS_KEY, JSON.stringify(guestTemplates));

    // Reset state kalau yang dihapus sedang aktif
    if (activeGuestTemplate?._local_id === localId) {
        activeGuestTemplate = null;
        TS.tampilan_id = null;
        document.getElementById('stepFrekuensi')?.classList.add('hidden');
        _resetDataTable();
    }
    renderGuestTemplates();
    _updateGuestQuotaUI();
}

function renderGuestTemplates() {
    const container = document.getElementById('guestTemplateList');
    if (!container) return;

    if (!guestTemplates.length) {
        container.innerHTML = `
            <div class="flex flex-col items-center gap-3 py-12 text-gray-400">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-gray-300 text-xl"></i>
                </div>
                <p class="font-medium text-gray-500">Belum ada template</p>
                <p class="text-xs text-gray-400">Buat template pertama Anda untuk memudahkan akses data</p>
                <a href="{{ route('template.create') }}"
                id="btnBuatTemplateGuestEmpty"
                class="mt-1 px-4 py-2 bg-stikom-blue hover:bg-blue-700 text-white
                        text-xs font-semibold rounded-lg transition-colors">
                    <i class="fas fa-plus mr-1"></i> Buat Template
                </a>
            </div>`;
        _updateGuestQuotaUI();
        return;
    }

    container.innerHTML = guestTemplates.map(tmpl => {
        const isActive  = activeGuestTemplate?._local_id === tmpl._local_id;
        const metaCount = (tmpl.metadata_ids ?? []).length;
        const jenis     = tmpl.jenis_template ?? 'metadata';
        const jenisLabel = { metadata:'Metadata', klasifikasi:'Klasifikasi', wilayah:'Wilayah' }[jenis] ?? jenis;
        const createdAt = tmpl.created_at
            ? new Date(tmpl.created_at).toLocaleString('id-ID', { dateStyle:'short', timeStyle:'short' })
            : '-';

        return `
        <div class="grid grid-cols-13 gap-5 w-full border-2 rounded-lg px-4 py-3
                    text-xs font-semibold items-center cursor-pointer transition-all duration-150
                    ${isActive
                        ? 'border-sky-500 bg-sky-500 text-white'
                        : 'border-sky-300 text-sky-500 hover:bg-sky-500 hover:text-white'}"
             onclick="selectGuestTemplate('${tmpl._local_id}')">

            <div class="col-span-8">
                <p class="font-semibold">${_esc(tmpl.nama_tampilan)}</p>
                <div class="mt-1 flex items-center gap-2 font-normal opacity-80">
                    <span>${_esc(jenisLabel)}</span>
                    <span>•</span>
                    <span>${metaCount} metadata</span>
                </div>
            </div>

            <div class="col-span-4 font-normal opacity-70">
                <span class="block">Dibuat</span>
                <span>${_esc(createdAt)}</span>
            </div>

            <div class="col-span-1 flex gap-2 justify-end"
                 onclick="event.stopPropagation()">
                <button onclick="deleteGuestTemplate('${tmpl._local_id}')"
                        class="${isActive ? 'text-white/80 hover:text-white' : 'text-sky-400 hover:text-red-500'} transition-colors"
                        title="Hapus template ini">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');

    // Banner login
    const banner = document.getElementById('guestLoginBanner');
    if (banner) banner.classList.remove('hidden');
}

function selectGuestTemplate(localId) {
    const tmpl = guestTemplates.find(t => t._local_id === localId);
    if (!tmpl) return;

    activeGuestTemplate = tmpl;
    TS.tampilan_id      = null; // guest tidak punya DB id
    TS.frekuensi        = null;
    TS.page             = 1;
    _resetDataTable();

    renderGuestTemplates(); // re-render untuk highlight aktif

    // Tampilkan step frekuensi
    document.getElementById('stepFrekuensi')?.classList.remove('hidden');

    // Load freq counts via endpoint guest
    loadFreqCountsGuest(tmpl);
}

const GUEST_TEMPLATE_LIMIT = 2;

function _updateGuestQuotaUI() {
    if (!IS_GUEST) return;
    const full = guestTemplates.length >= GUEST_TEMPLATE_LIMIT;

    ['btnBuatTemplateHeader', 'btnBuatTemplateGuestEmpty'].forEach(id => {
        const btn = document.getElementById(id);
        if (!btn) return;

        if (full) {
            btn.classList.remove('btn-primary', 'shadow-md', 'shadow-blue-400/30', 'bg-stikom-blue', 'hover:bg-blue-700', 'text-white');
            btn.classList.add('bg-gray-200', 'text-gray-400', 'cursor-not-allowed', 'pointer-events-none');
            btn.setAttribute('aria-disabled', 'true');
            btn.title = `Kuota template Anda sudah penuh (${guestTemplates.length}/${GUEST_TEMPLATE_LIMIT}). Login untuk membuat lebih banyak template.`;
            btn.addEventListener('click', _preventClick);
        } else {
            btn.classList.add('btn-primary', 'shadow-md', 'shadow-blue-400/30');
            btn.classList.remove('bg-gray-200', 'text-gray-400', 'cursor-not-allowed', 'pointer-events-none');
            btn.removeAttribute('aria-disabled');
            btn.removeAttribute('title');
            btn.removeEventListener('click', _preventClick);
        }
    });
}

function _preventClick(e) {
    e.preventDefault();
}

async function loadFreqCountsGuest(tmpl) {
    const freqKeys = ['10tahunan', 'tahunan', 'semesteran', 'kuartal', 'bulanan', 'custom'];
    const hint     = document.getElementById('freqLoadingHint');
    if (hint) hint.classList.remove('hidden');

    freqKeys.forEach(freq => {
        const btn   = document.getElementById('freq-btn-' + freq);
        const badge = document.getElementById('freq-badge-' + freq);
        if (btn)   btn.disabled = true;
        if (badge) { badge.textContent = '…'; badge.className = 'freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400'; }
    });

    try {
        const body = new URLSearchParams();
        body.append('_token', TMPL_URLS.csrf);
        (tmpl.metadata_ids ?? []).forEach(id => body.append('metadata_ids[]', id));
        if (tmpl.metadata_location_ids) {
            body.append('metadata_location_ids', JSON.stringify(tmpl.metadata_location_ids));
        }

        const res  = await fetch(TMPL_URLS.freqCountsGuest, { method:'POST', body });
        const data = await res.json();

        freqKeys.forEach(freq => {
            const btn   = document.getElementById('freq-btn-' + freq);
            const badge = document.getElementById('freq-badge-' + freq);
            if (!btn) return;

            const count = data[freq] ?? 0;
            if (count > 0) {
                btn.disabled = false;
                btn.classList.remove('opacity-40', 'cursor-not-allowed');
                btn.classList.add('cursor-pointer');
                if (badge) {
                    badge.className  = 'freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-sky-100 text-sky-700';
                    badge.textContent = count;
                }
            } else {
                btn.disabled = true;
                btn.classList.remove('border-sky-500','bg-sky-50','border-amber-400','bg-amber-50');
                btn.classList.add('border-gray-200');
                if (badge) {
                    badge.className  = 'freq-count-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400';
                    badge.textContent = '0';
                }
            }
        });

    } catch (e) {
        console.warn('Gagal load freq counts guest:', e);
        freqKeys.forEach(freq => {
            const btn = document.getElementById('freq-btn-' + freq);
            if (btn) btn.disabled = false;
        });
    } finally {
        if (hint) hint.classList.add('hidden');
    }
}

// ─────────────────────────────────────────────────────────────
// STEP 4 — Fetch & render tabel pivot
// ─────────────────────────────────────────────────────────────
async function tampilkanData(page = 1) {
    TS.page = page;

    const section    = document.getElementById('dataTableSection');
    const loading    = document.getElementById('tableLoading');
    const empty      = document.getElementById('tableEmpty');
    const tableWrap  = document.getElementById('tableWrap');
    const pagination = document.getElementById('tablePagination');

    section.classList.remove('hidden');
    loading.classList.remove('hidden');
    empty.classList.add('hidden');
    tableWrap.classList.add('hidden');
    pagination.classList.add('hidden');
    section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    try {
        let res;

        if (IS_GUEST && activeGuestTemplate) {
            // ── Mode Guest: kirim metadata_ids langsung ──────────
            const tmpl   = activeGuestTemplate;
            const body   = new URLSearchParams();
            body.append('_token', TMPL_URLS.csrf);
            body.append('frekuensi', TS.frekuensi);
            if (TS.year_from)   body.append('year_from',   TS.year_from);
            if (TS.year_to)     body.append('year_to',     TS.year_to);
            if (TS.period_from) body.append('period_from', TS.period_from);
            if (TS.period_to)   body.append('period_to',   TS.period_to);
            body.append('page', page);
            (tmpl.metadata_ids ?? []).forEach(id => body.append('metadata_ids[]', id));
            if (tmpl.metadata_location_ids) {
                body.append('metadata_location_ids', JSON.stringify(tmpl.metadata_location_ids));
            }

            res = await fetch(TMPL_URLS.tableDataGuest, { method:'POST', body });

        } else {
            // ── Mode Auth: pakai tampilan_id seperti sebelumnya ──
            const payload = {
                tampilan_id : TS.tampilan_id,
                frekuensi   : TS.frekuensi,
                year_from   : TS.year_from,
                year_to     : TS.year_to,
                period_from : TS.period_from,
                period_to   : TS.period_to,
                page        : page,
            };
            res = await fetch(TMPL_URLS.tableData, {
                method  : 'POST',
                headers : {
                    'Content-Type' : 'application/json',
                    'X-CSRF-TOKEN' : TMPL_URLS.csrf,
                    'Accept'       : 'application/json',
                },
                body: JSON.stringify(payload),
            });
        }

        const d = await res.json();
        loading.classList.add('hidden');

        if (!d.success || !d.rows?.length) {
            empty.classList.remove('hidden');
            document.getElementById('tableEmptyMsg').textContent =
                d.message ?? 'Tidak ada data ditemukan pada rentang periode ini.';
            return;
        }

        _renderTable(d);
        _renderMobileCards(d);

    } catch (e) {
        loading.classList.add('hidden');
        empty.classList.remove('hidden');
        document.getElementById('tableEmptyMsg').textContent = 'Terjadi kesalahan. Silakan coba lagi.';
        console.error(e);
    }
}

// ─── Deteksi level wilayah ────────────────────────────────────
function _getLokasiLevel(row) {
    if (row.lokasi_level !== undefined && row.lokasi_level !== null) {
        return row.lokasi_level;
    }
    const locId = String(row.location_id ?? '');
    if (locId.endsWith('00000000')) return 0;
    if (locId.endsWith('000000'))   return 1;
    if (locId.endsWith('0000'))     return 2;
    return 3;
}

function showExportButtons() {
        if (!TP_HAS_ACCESS) return;
        const g = document.getElementById('exportBtnGroup');
        if (g) g.style.removeProperty('display');
    }
    function hideExportButtons() {
        const g = document.getElementById('exportBtnGroup');
        if (g) g.style.setProperty('display', 'none', 'important');
    }

    // ─── Export handler ───────────────────────────────────────────
    function exportData(format) {

        if (!TP_HAS_ACCESS) {
            alert('Anda perlu berlangganan untuk mengekspor data.');
            return;
        }
        // Validasi state
        if (!TS.tampilan_id || !TS.frekuensi) {
            alert('Pilih template dan frekuensi terlebih dahulu.');
            return;
        }
    
        // Payload — sama dengan tampilkanData()
        const payload = {
            tampilan_id : TS.tampilan_id,
            frekuensi   : TS.frekuensi,
            year_from   : TS.year_from,
            year_to     : TS.year_to,
            period_from : TS.period_from,
            period_to   : TS.period_to,
        };
    
        const urlMap = {
            excel : TMPL_URLS.exportExcel,
            pdf   : TMPL_URLS.exportPdf,
            json  : TMPL_URLS.exportJson,
        };
    
        const url = urlMap[format];
        if (!url) return;
    
        if (format === 'pdf') {
            // PDF → buka di tab baru (render Blade view)
            _submitFormPost(url, payload);
            return;
        }
    
        if (format === 'json') {
            // JSON → download via fetch
            _fetchAndDownload(url, payload, 'application/json', _makeFilename('json'));
            return;
        }
    
        // Excel → download via form POST (stream download)
        _submitFormPost(url, payload);
    }

    // ─── Buat form POST invisible dan submit ─────────────────────
    function _submitFormPost(url, payload) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.target = '_blank'; // PDF buka di tab baru; Excel otomatis download
    
        // CSRF
        const csrf = document.createElement('input');
        csrf.type  = 'hidden';
        csrf.name  = '_token';
        csrf.value = TMPL_URLS.csrf;
        form.appendChild(csrf);
    
        // Fields
        Object.entries(payload).forEach(([k, v]) => {
            if (v === null || v === undefined) return;
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = k;
            inp.value = v;
            form.appendChild(inp);
        });
    
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    // ─── Fetch → download file ─────────────────────────────────
    async function _fetchAndDownload(url, payload, mimeType, filename) {
        try {
            const res = await fetch(url, {
                method  : 'POST',
                headers : {
                    'Content-Type' : 'application/json',
                    'X-CSRF-TOKEN' : TMPL_URLS.csrf,
                    'Accept'       : mimeType,
                },
                body: JSON.stringify(payload),
            });
    
            if (!res.ok) throw new Error('Server error: ' + res.status);
    
            const blob = await res.blob();
            const link = document.createElement('a');
            link.href     = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        } catch (e) {
            alert('Gagal mengekspor: ' + e.message);
            console.error(e);
        }
    }
    
    // ─── Nama file otomatis ───────────────────────────────────────
    function _makeFilename(ext) {
        const name = document.querySelector('[class*="border-sky-500"] p.font-semibold')
            ?.textContent?.trim()?.replace(/\s+/g, '_') ?? 'export';
        const range = [TS.year_from, TS.year_to].filter(Boolean).join('-') || 'all';
        return `Export_${name}_${TS.frekuensi}_${range}.${ext}`;
    }

// ─────────────────────────────────────────────────────────────
// RENDER TABEL PIVOT
// ─────────────────────────────────────────────────────────────
function _renderTable(d) {
    const head      = document.getElementById('pivotHead');
    const body      = document.getElementById('pivotBody');
    const wrap      = document.getElementById('tableWrap');
    const pag       = document.getElementById('tablePagination');
    const infoText  = document.getElementById('tableInfoText');
    const subInfo   = document.getElementById('tableSubInfo');
    const cols      = d.columns;   // [{label, meta}]
    const rows      = d.rows;

    const periodLabel = {
        '10tahunan':'Dekade','5tahunan':'Periode (5 Tahunan)','tahunan':'Tahun',
        'semesteran':'Semester','kuartal':'Kuartal','bulanan':'Bulan','custom':'Periode Custom',
    }[d.frekuensi] ?? 'Periode';

    // ── Baris header 1: Nama | Periode (colspan) | Satuan | Sumber | Aksi
    // ── Baris header 2: [kosong] | kolom per periode | [rowspan dari baris 1]
    head.innerHTML = `
        <tr class="border-b border-gray-200">
            <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs
                       col-sticky col-sticky-nama whitespace-nowrap"
                rowspan="2">
                Nama Data
            </th>
            <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs
                       whitespace-nowrap border-l border-gray-200"
                colspan="${cols.length}">
                ${_esc(periodLabel)}
            </th>
            <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs
                       whitespace-nowrap border-l border-gray-200"
                rowspan="2" style="min-width:70px">
                Satuan
            </th>
            <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs
                       whitespace-nowrap border-l border-gray-200"
                rowspan="2" style="min-width:150px">
                Sumber
            </th>
            <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs
                       whitespace-nowrap border-l border-gray-200"
                rowspan="2" style="min-width:80px">
                Aksi
            </th>
        </tr>
        <tr class="border-b border-gray-200">
            ${cols.map((c, i) =>
                `<th class="px-3 py-2 text-center font-semibold text-gray-600 text-xs
                             whitespace-nowrap border-r border-gray-100
                             ${i === 0 ? 'border-l border-gray-200' : ''}"
                     style="min-width:90px">
                    ${_esc(c.label)}
                </th>`
            ).join('')}
        </tr>`;

    // ── Group rows per metadata ────────────────────────────────
    const grouped   = {};
    const metaOrder = [];
    rows.forEach(row => {
        const key = String(row.metadata_id);
        if (!grouped[key]) {
            grouped[key] = { nama: row.nama, klasifikasi: row.klasifikasi, rows: [] };
            metaOrder.push(key);
        }
        grouped[key].rows.push(row);
    });

    let html   = '';
    let rowIdx = 0;

    metaOrder.forEach((key, mIdx) => {
            const group = grouped[key];

            group.rows.sort((a, b) => {
                const la = _getLokasiLevel(a);
                const lb = _getLokasiLevel(b);
                if (la !== lb) return la - lb;
                return (a.lokasi ?? '').localeCompare(b.lokasi ?? '');
            });

            // ── Hitung level minimum yang benar-benar ada di grup ini ──
            const minLevel = Math.min(...group.rows.map(r => _getLokasiLevel(r)));

            group.rows.forEach((row, ri) => {
                const level        = _getLokasiLevel(row);       // level asli (buat ikon & tooltip)
                const displayLevel = level - minLevel;             // level RELATIF (buat indentasi visual)
                const isFirstRow   = ri === 0;
                const span         = group.rows.length;
                rowIdx++;

                const levelIcons = ['🏛️','🏙️','🏘️','🏠'];
                const levelNames = ['Provinsi','Kabupaten','Kecamatan','Desa'];
                const locIcon    = levelIcons[level] ?? '';
                const locTitle   = levelNames[level] ?? '';

            // Nilai per kolom
            const cells = cols.map((c, ci) => {
                const val = row.values?.[c.label];
                const fmt = (val !== null && val !== undefined && val !== '')
                    ? parseFloat(val).toLocaleString('id-ID', {
                          minimumFractionDigits: 0, maximumFractionDigits: 2,
                      })
                    : '<span class="text-gray-200 select-none">—</span>';
                return `<td class="px-3 py-2.5 text-right font-mono text-xs
                                   border-r border-gray-100 whitespace-nowrap
                                   ${ci === 0 ? 'border-l border-gray-200' : ''}">
                            ${fmt}
                        </td>`;
            }).join('');

            html += `<tr>`;

            // ── Kolom sticky: Nama metadata + wilayah ──
            html += `
                <td class="px-3 py-2.5 col-sticky col-sticky-nama align-top">
                    <div class="flex flex-col gap-0.5">
                        ${isFirstRow ? `
                            <p class="text-xs font-bold text-gray-800 leading-tight flex items-center gap-1">
                                ${_esc(group.nama)}

                                <button type="button"
                                        onclick="event.stopPropagation(); openMetadataModal(${row.metadata_id})"
                                        class="text-blue-400 hover:text-sky-600 transition-colors
                                            bg-transparent border-0 p-0 cursor-pointer"
                                        title="Lihat detail metadata">
                                    <i class="fas fa-info-circle text-[11px]"></i>
                                </button>
                            </p>
                            ${group.klasifikasi
                                ? `<p class="text-gray-400 text-[10px] mb-1">${_esc(group.klasifikasi)}</p>`
                                : ''}
                        ` : ''}
                        <span class="loc-indent-${displayLevel} flex items-center gap-1 text-xs
                                    ${level === 0 ? 'font-semibold text-gray-700' : ''}
                                    ${level === 1 ? 'text-gray-700' : ''}
                                    ${level === 2 ? 'text-gray-700' : ''}
                                    ${level === 3 ? 'text-gray-700' : ''}"
                            title="${_esc(row.lokasi ?? '-')}">
                            <span class="text-gray-300">${locIcon}</span>
                            <span class="loc-text">${_esc(row.lokasi ?? '-')}</span>
                        </span>
                    </div>
                </td>`;

            html += cells;

            // ── Satuan + Sumber: rowspan hanya di baris pertama (sama untuk semua lokasi) ──
            // ── Satuan: tetap rowspan, karena satuan sama untuk semua lokasi ──
            if (isFirstRow) {
                html += `
                    <td class="px-3 py-2.5 text-xs text-gray-500 text-center
                            border-l border-gray-200 whitespace-nowrap align-middle"
                        rowspan="${span}">
                        ${_esc(row.satuan ?? '-')}
                    </td>`;
            }

            // ── Sumber: PER BARIS, karena tiap lokasi bisa punya rujukan/produsen beda ──
            const sumberCombined = (row.sumber ?? '') + (row.produsen ? ((row.sumber ? ' · ' : '') + row.produsen) : '');
            html += `
                <td class="px-3 py-2.5 text-xs text-gray-400 align-top border-l border-gray-200"
                    style="max-width:180px" title="${_esc(sumberCombined)}">
                    <span class="line-clamp-3">${_esc(sumberCombined || '-')}</span>
                </td>`;

            // ── Aksi/Grafik: muncul di SETIAP baris (per lokasi) ──
            // Jika location_id kosong/null (Semua Wilayah), kirimkan 0 sebagai sentinel
            const grafikUrl = `${TMPL_URLS.grafik}?metadata_id=${row.metadata_id}&location_id=${(row.location_id ?? 0)}`;
            const locLabel  = row.lokasi ? _esc(row.lokasi) : 'Semua Wilayah';
            html += `
                <td class="px-3 py-2.5 text-center align-middle border-l border-gray-200 whitespace-nowrap">
                    <a href="${grafikUrl}"
                       class="btn-grafik"
                       title="Grafik: ${_esc(group.nama)} — ${locLabel}">
                        <i class="fas fa-chart-bar"></i>
                        Grafik
                    </a>
                </td>`;

            html += `</tr>`;
        });

        // Separator antar metadata
        if (mIdx < metaOrder.length - 1) {
            const totalCols = 1 + cols.length + 3; // sticky + data cols + satuan+sumber+aksi
            html += `<tr class="metadata-separator"><td colspan="${totalCols}"></td></tr>`;
        }
    });

    body.innerHTML = html;
    wrap.classList.remove('hidden');
    showExportButtons();
    
    // ── Cek akses langganan ──
    const overlay = document.getElementById('tableAccessOverlay');
    if (overlay) {
        if (!TP_HAS_ACCESS) {
            overlay.classList.remove('hidden');
        } else {
            overlay.classList.add('hidden');
        }
    }

    // ── Info bar ─────────────────────────────────────────────
    const start = (d.current_page - 1) * d.per_page + 1;
    const end   = Math.min(d.current_page * d.per_page, d.total);
    infoText.textContent = `Menampilkan ${start}–${end} dari ${d.total} baris`;
    const tmplName = document.querySelector('[class*="border-sky-500"] p.font-semibold')?.textContent?.trim() ?? '';
    subInfo.textContent = [
        tmplName ? `Template: ${tmplName}` : '',
        `Frekuensi: ${TP_FREK_LABEL[d.frekuensi] ?? d.frekuensi}`,
        `${cols.length} kolom periode`,
    ].filter(Boolean).join(' · ');

    // ── Pagination ────────────────────────────────────────────
    if (d.last_page > 1) {
        pag.classList.remove('hidden');
        document.getElementById('paginationInfo').textContent =
            `Halaman ${d.current_page} dari ${d.last_page}`;

        let pages = [];
        for (let p = 1; p <= d.last_page; p++) {
            if (p === 1 || p === d.last_page || Math.abs(p - d.current_page) <= 2)
                pages.push(p);
            else if (pages[pages.length - 1] !== '...') pages.push('...');
        }
        document.getElementById('paginationBtns').innerHTML = pages.map(p =>
            p === '...'
            ? `<span class="w-7 h-7 flex items-center justify-center text-gray-400 text-xs">…</span>`
            : `<button onclick="tampilkanData(${p})"
                       class="w-7 h-7 rounded-md text-xs font-medium transition-colors
                              ${p === d.current_page
                                ? 'bg-sky-500 text-white'
                                : 'border border-gray-300 text-gray-500 hover:bg-gray-50'}">
                   ${p}</button>`
        ).join('');
    }
}

function _renderMobileCards(d) {
    const container = document.getElementById('mobileCardList');
    if (!container) return;

    const cols = d.columns;
    const rows = d.rows;

    // Group per metadata (sama seperti _renderTable)
    const grouped   = {};
    const metaOrder = [];
    rows.forEach(row => {
        const key = String(row.metadata_id);
        if (!grouped[key]) {
            grouped[key] = { nama: row.nama, klasifikasi: row.klasifikasi, satuan: row.satuan, sumber: row.sumber, rows: [] };
            metaOrder.push(key);
        }
        grouped[key].rows.push(row);
    });

    container.innerHTML = metaOrder.map((key, idx) => {
        const group = grouped[key];

        // Baris lokasi
        const lokasiRows = group.rows.map(row => {
            const level = _getLokasiLevel(row);
            const levelNames = ['Provinsi','Kabupaten','Kecamatan','Desa'];
            const locTitle = levelNames[level] ?? '';

            // Nilai per kolom
            const nilaiRows = cols.map(c => {
                const val = row.values?.[c.label];
                const fmt = (val !== null && val !== undefined && val !== '')
                    ? parseFloat(val).toLocaleString('id-ID', { minimumFractionDigits:0, maximumFractionDigits:2 })
                    : '—';
                return `<div class="meta-card-row">
                    <span class="text-gray-400">${_esc(c.label)}</span>
                    <span class="font-mono font-medium text-gray-700">${fmt}</span>
                </div>`;
            }).join('');

            const grafikUrl = `${TMPL_URLS.grafik}?metadata_id=${row.metadata_id}&location_id=${row.location_id ?? ''}`;

            return `
                <div class="border-t border-gray-100">
                    <div class="flex items-center justify-between px-4 py-2 bg-gray-50 gap-2">
                        <span class="text-xs font-semibold text-gray-600">
                            ${_esc(row.lokasi ?? 'Semua Wilayah')}
                            <span class="text-gray-400 font-normal ml-1">${_esc(locTitle)}</span>
                        </span>
                        <a href="${grafikUrl}" class="btn-grafik shrink-0">
                            <i class="fas fa-chart-bar"></i> Grafik
                        </a>
                    </div>
                    ${nilaiRows}
                    ${row.sumber ? `
                    <div class="px-4 py-1.5 text-[10px] text-gray-400 border-t border-gray-50">
                        <span class="font-medium text-gray-500">Sumber:</span> ${_esc(row.sumber)}
                    </div>` : ''}
                </div>`;
        }).join('');

        return `
            <div class="meta-card">
                {{-- Header card accordion --}}
                <div class="meta-card-header" onclick="toggleMetaCard('mc-${idx}')">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-gray-800 leading-tight">
                            ${_esc(group.nama)}
                        </p>
                        ${group.klasifikasi
                            ? `<p class="text-[10px] text-gray-400 mt-0.5">${_esc(group.klasifikasi)}</p>`
                            : ''}
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            Satuan: ${_esc(group.satuan ?? '-')}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                onclick="event.stopPropagation(); openMetadataModal(${group.rows[0]?.metadata_id})"
                                class="text-blue-400 hover:text-sky-600 transition-colors">
                            <i class="fas fa-info-circle text-xs"></i>
                        </button>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" id="mc-chevron-${idx}"></i>
                    </div>
                </div>

                {{-- Body accordion --}}
                <div class="meta-card-body" id="mc-${idx}">
                    ${lokasiRows}
                </div>
            </div>`;
    }).join('');
}

function toggleMetaCard(id) {
    const body    = document.getElementById(id);
    const chevron = document.getElementById(id.replace('mc-', 'mc-chevron-'));
    if (!body) return;
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

// ─── Export CSV ───────────────────────────────────────────────
function exportCsv() {
    const table = document.getElementById('pivotTable');
    if (!table) return;
    const rows = [];
    table.querySelectorAll('tr').forEach(tr => {
        const cols = [];
        tr.querySelectorAll('th, td').forEach(td => {
            cols.push('"' + td.innerText.replace(/"/g,'""').replace(/\n/g,' ').trim() + '"');
        });
        if (cols.length) rows.push(cols.join(','));
    });
    const blob = new Blob(['\uFEFF' + rows.join('\n')], {type:'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = `data-template-${TS.tampilan_id ?? 'export'}.csv`;
    a.click(); URL.revokeObjectURL(url);
}

// ─── Export Dropdown ──────────────────────────────────────────
function toggleExportDropdown() {
    const dd      = document.getElementById('exportDropdown');
    const chevron = document.getElementById('exportChevron');
    const isOpen  = !dd.classList.contains('hidden');
    if (isOpen) {
        dd.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    } else {
        dd.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    }
}

function closeExportDropdown() {
    document.getElementById('exportDropdown')?.classList.add('hidden');
    document.getElementById('exportChevron')?.classList.remove('rotate-180');
}

// Tutup dropdown kalau klik di luar
document.addEventListener('click', e => {
    const group = document.getElementById('exportBtnGroup');
    if (group && !group.contains(e.target)) closeExportDropdown();
});

// ─── Reset ────────────────────────────────────────────────────
function resetFilter() { window.location.href = TMPL_URLS.base; }

function _resetDataTable() {
    ['dataTableSection','tableLoading','tableEmpty','tableWrap','tablePagination']
        .forEach(id => document.getElementById(id)?.classList.add('hidden'));
        hideExportButtons();
}

function _esc(str) {
    const d = document.createElement('div');
    d.innerText = str ?? '';
    return d.innerHTML;
}

// ─── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (IS_GUEST) {
        loadGuestTemplates();
    } else if (TS.tampilan_id) {
        loadFreqCounts(TS.tampilan_id);
    }
});
</script>