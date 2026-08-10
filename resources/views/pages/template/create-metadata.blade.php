@extends('layouts.main')

@section('content')

{{-- ── Aksen warna untuk partial _preview-table (sky/biru) ── --}}
<style>
    :root { --row-indent: 12px; }
    @media (min-width: 640px) { :root { --row-indent: 24px; } }
    .preview-badge        { background:#f0f9ff; color:#0284c7; border-color:#bae6fd; }
    .preview-btn-primary  { background:#0ea5e9; }
    .preview-btn-primary:hover { background:#0284c7; }
    #accentBar            { background:#0ea5e9; }
    .preview-modal-header { background: linear-gradient(135deg, #0ea5e9, #0369a1); }
    .preview-summary-box  { background:#f0f9ff; border-color:#bae6fd; }
    .preview-summary-icon { background:#bae6fd; color:#0284c7; }
    .preview-summary-text { color:#0369a1; }
    .preview-summary-sub  { color:#0284c7; }
    #selectionBarPreview  { background:#f0f9ff; border-color:#bae6fd; }
    #selectionBarPreview p { color:#0369a1; }
    #selectionBarPreview button { color:#0284c7; }
</style>

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('template.create') }}" class="hover:text-sky-500 transition-colors">Buat Template</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Template Metadata</span>
    </div>

    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Buat Template Metadata</h1>
            <p class="text-sm text-gray-400 mt-1">Pilih metadata dan wilayah yang ingin Anda tampilkan</p>
        </div>
        <span class="hidden sm:block px-3 py-1.5 bg-violet-50 text-violet-600 border border-violet-100 text-xs font-semibold rounded-full">
            <i class="fas fa-tags mr-1"></i> Jenis: Metadata
        </span>
    </div>

    {{-- ═══ SECTION 1: FORM FILTER ═══ --}}
    <div class="p-5 border border-gray-200 rounded-xl mb-2">
        <h2 class="text-sm font-bold text-gray-700 mb-4">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-sky-500 text-white text-xs font-bold mr-2">1</span>
            Pilih Metadata &amp; Wilayah
        </h2>

        <div class="space-y-4">

            {{-- ── METADATA multi-select ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-database mr-1 text-gray-400"></i> Metadata
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative" id="metaDropWrap">
                    <input type="text" id="metaSearch"
                            placeholder="Ketik untuk mencari metadata..."
                            autocomplete="off"
                            oninput="onMetaSearchInput()"
                            onfocus="onMetaSearchFocus()"
                            class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2.5 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-sky-400 transition-shadow">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>

                    {{-- Dropdown list --}}
                    <div id="metaDropList"
                         class="hidden absolute z-20 w-full mt-1 bg-white border border-gray-200
                                rounded-xl shadow-xl max-h-72 overflow-y-auto">
                        <p class="px-4 py-3 text-xs text-gray-400 text-center">Mulai ketik untuk mencari metadata</p>
                    </div>
                </div>

                {{-- Chips metadata terpilih --}}
                <div id="selectedMetaChips" class="flex flex-wrap gap-1.5 mt-2 min-h-6"></div>
            </div>

            {{-- ── WILAYAH cascade ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-2">
                    <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i> Wilayah
                    <span class="text-gray-400 font-normal">(opsional — pilih hingga level yang diinginkan)</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    {{-- PROVINSI --}}
                    <div>
                        <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Provinsi</label>
                        <div class="relative" id="wrapProvinsi">
                            <input type="text" id="inputProvinsi" placeholder="Cari provinsi..."
                                autocomplete="off"
                                oninput="onWilInput('provinsi')"
                                onfocus="onWilFocus('provinsi')"
                                class="w-full border border-gray-300 rounded-lg pl-7 pr-7 py-2 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            <button type="button" id="clearProvinsi" onclick="clearLevel('provinsi')"
                                class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm leading-none">×</button>
                            <div id="dropProvinsi"
                                class="hidden absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-52 overflow-y-auto"></div>
                        </div>
                        <input type="hidden" id="valProvinsi">
                    </div>

                    {{-- KABUPATEN --}}
                    <div>
                        <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Kabupaten / Kota</label>
                        <div class="relative" id="wrapKabupaten">
                            <input type="text" id="inputKabupaten" placeholder="Pilih provinsi dulu..."
                                autocomplete="off" disabled
                                oninput="onWilInput('kabupaten')"
                                onfocus="onWilFocus('kabupaten')"
                                class="w-full border border-gray-200 rounded-lg pl-7 pr-7 py-2 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-gray-100 text-gray-400 cursor-not-allowed">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                            <button type="button" id="clearKabupaten" onclick="clearLevel('kabupaten')"
                                class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm leading-none">×</button>
                            <div id="dropKabupaten"
                                class="hidden absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-52 overflow-y-auto"></div>
                        </div>
                        <input type="hidden" id="valKabupaten">
                    </div>

                    {{-- KECAMATAN --}}
                    <div>
                        <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Kecamatan</label>
                        <div class="relative" id="wrapKecamatan">
                            <input type="text" id="inputKecamatan" placeholder="Pilih kabupaten dulu..."
                                autocomplete="off" disabled
                                oninput="onWilInput('kecamatan')"
                                onfocus="onWilFocus('kecamatan')"
                                class="w-full border border-gray-200 rounded-lg pl-7 pr-7 py-2 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-gray-100 text-gray-400 cursor-not-allowed">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                            <button type="button" id="clearKecamatan" onclick="clearLevel('kecamatan')"
                                class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm leading-none">×</button>
                            <div id="dropKecamatan"
                                class="hidden absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-52 overflow-y-auto"></div>
                        </div>
                        <input type="hidden" id="valKecamatan">
                    </div>

                    {{-- DESA --}}
                    <div>
                        <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Desa / Kelurahan</label>
                        <div class="relative" id="wrapDesa">
                            <input type="text" id="inputDesa" placeholder="Pilih kecamatan dulu..."
                                autocomplete="off" disabled
                                oninput="onWilInput('desa')"
                                onfocus="onWilFocus('desa')"
                                class="w-full border border-gray-200 rounded-lg pl-7 pr-7 py-2 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-gray-100 text-gray-400 cursor-not-allowed">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs pointer-events-none"></i>
                            <button type="button" id="clearDesa" onclick="clearLevel('desa')"
                                class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm leading-none">×</button>
                            <div id="dropDesa"
                                class="hidden absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-52 overflow-y-auto"></div>
                        </div>
                        <input type="hidden" id="valDesa">
                    </div>
                </div>

                {{-- Badge wilayah terpilih --}}
                <div id="m_selectedWilayahBadge"
                     class="hidden mt-3 flex items-center gap-2 p-2.5 bg-sky-50 border border-sky-200 rounded-lg w-fit">
                    <i class="fas fa-map-marker-alt text-sky-500 text-xs"></i>
                    <span class="text-xs text-sky-700 font-semibold" id="m_badgeNama">—</span>
                    <span class="text-xs text-sky-500" id="m_badgeLevel">—</span>
                </div>
            </div>

            <button type="button" onclick="loadMetadataPreview()"
                class="px-5 py-2.5 bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white text-sm font-semibold rounded-lg
                       shadow-md shadow-sky-400/30 flex items-center gap-2 transition-all">
                <i class="fas fa-search"></i> Pilih &amp; Tampilkan
            </button>
        </div>
    </div>

    {{-- ═══ SECTION 2: PREVIEW TABLE ═══ --}}
    <div id="sectionResult" class="hidden mt-6">

        {{-- TAB SWITCHER --}}
        <div class="border-b border-gray-200 mb-4">
            <div class="flex gap-1 overflow-x-auto" id="freqTabs">
                @foreach(['dekade' => 'Dekade', 'tahunan' => 'Tahunan', 'semester' => 'Semester', 'kuartal' => 'Kuartal', 'bulanan' => 'Bulanan'] as $key => $label)
                    <button type="button"
                        id="tab-{{ $key }}"
                        onclick="switchTab('{{ $key }}')"
                        class="tab-btn shrink-0 px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors
                               border-transparent text-gray-400 cursor-not-allowed"
                        disabled>
                        {{ $label }}
                        <span id="tab-count-{{ $key }}"
                              class="ml-1.5 text-xs font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400">0</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Info bar --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-bold text-gray-700 text-sm">Hasil Metadata</h3>
                <p class="text-xs text-gray-400 mt-0.5" id="resultDesc">—</p>
            </div>
            <span id="totalBadge"
                  class="hidden sm:block text-xs bg-sky-50 text-sky-600 border border-sky-100 px-2.5 py-1 rounded-full font-semibold">
                0 baris
            </span>
        </div>

        {{-- Tabel --}}
         <div class="border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full table-fixed text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="w-12 px-4 py-3">
                            <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                                class="rounded border-gray-300 cursor-pointer">
                        </th>
                        {{-- Metadata --}}
                        <th class="px-4 py-3 font-semibold text-gray-600">
                            Metadata
                        </th>

                        {{-- Info --}}
                        <th class="w-10 sm:w-16 px-1 sm:px-3 py-3 text-center font-semibold text-gray-600">
                            Info
                        </th>
                        <th class="w-12 sm:w-20 px-1 sm:px-3 py-3 text-center font-semibold text-gray-600">
                            <span class="block sm:hidden">Det.</span>
                            <span class="hidden sm:block">Detail</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="resultTbody" class="divide-y divide-gray-100 bg-white"></tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="paginWrap" class="hidden mt-3 flex items-center justify-between text-xs text-gray-500">
            <span id="paginInfo" class="hidden sm:block"></span>
            <div id="paginBtns" class="flex gap-1 mx-auto sm:mx-0"></div>
        </div>

        {{-- Seleksi bar --}}
        <div id="selBar" class="mt-3 hidden items-center justify-between px-4 py-2.5 rounded-lg"
             style="background:#f0fdf4; border:1px solid #bbf7d0;">
            <p class="font-medium text-emerald-700 flex items-center gap-2 text-sm">
                <i class="fas fa-check-square"></i>
                <span id="selCount">0 metadata dipilih</span>
            </p>
            <button onclick="clearAllSel()" class="text-xs font-medium text-emerald-600 hover:underline">
                <i class="fas fa-times mr-1"></i> Batalkan Pilihan
            </button>
        </div>

        {{-- Pengaturan urutan --}}
        <div class="mt-5 p-4 bg-gray-50 border border-gray-200 rounded-xl">
            <p class="text-xs font-bold text-gray-600 mb-3 flex items-center gap-1.5">
                <i class="fas fa-sort-amount-down text-gray-400"></i> Pengaturan Urutan Tampilan
            </p>
            <div class="flex flex-col sm:flex-row sm:flex-wrap items-start gap-4">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chkKlasifikasi" class="rounded border-gray-300 cursor-pointer">
                    <span class="text-xs text-gray-600">Atur berdasarkan Klasifikasi</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chkWilayah" class="rounded border-gray-300 cursor-pointer">
                    <span class="text-xs text-gray-600">Atur berdasarkan Wilayah</span>
                </label>
                <button type="button" onclick="terapkanUrutan()"
                    class="px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white text-xs font-semibold rounded-lg transition-colors">
                    Terapkan
                </button>
            </div>
        </div>

        {{-- Tombol Simpan --}}
        <div class="mt-5
                    flex flex-col-reverse sm:flex-row
                    sm:justify-end gap-3">

            <a href="{{ route('template.create') }}"
                class="w-full sm:w-auto
                    border border-gray-300
                    text-gray-500 hover:bg-gray-50
                    px-4 py-2.5 rounded-lg text-sm
                    text-center transition-colors">

                Batal
            </a>

            <button
                type="button"
                onclick="openSaveModal()"

                class="w-full sm:w-auto
                    px-6 py-2.5
                    text-white text-sm font-semibold
                    rounded-lg
                    flex items-center justify-center gap-2
                    bg-violet-500 hover:bg-violet-600
                    transition-colors">

                <i class="fas fa-save"></i>
                Simpan Template
            </button>

        </div>
    </div>
</div>

{{-- ═══ MODAL SIMPAN ═══ --}}
<div id="modalSave"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,0.45);display:none;align-items:center;justify-content:center;padding:1rem;">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 rounded-t-xl" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-bold text-base flex items-center gap-2">
                    <i class="fas fa-bookmark"></i> Simpan Template
                </h3>
                <button onclick="closeModal()" class="text-purple-200 hover:text-white text-2xl leading-none">×</button>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Template <span class="text-red-500">*</span>
                </label>
                <input type="text" id="inputNama" placeholder="cth: Data kepadatan penduduk Bali"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-purple-400">
                <p id="errNama" class="hidden mt-1 text-xs text-red-500">
                    <i class="fas fa-exclamation-circle mr-1"></i> Nama template wajib diisi.
                </p>
            </div>
            <div class="p-3 bg-purple-50 rounded-lg border border-purple-100 text-xs text-purple-700">
                <i class="fas fa-list-check mr-1"></i>
                <span id="modalMetaCount">0</span> metadata akan disimpan.
            </div>
            @guest
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                <i class="fas fa-info-circle mr-1"></i>
                Anda belum login. Template akan disimpan di browser ini saja.
                <a href="{{ route('login') }}" class="underline font-semibold">Login</a> untuk menyimpan ke server.
            </div>
            @endguest
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl
            flex flex-col-reverse sm:flex-row
                    sm:justify-end gap-3">

            <button
                onclick="closeModal()"
                class="w-full sm:w-auto
                    border border-gray-300
                    text-gray-500 hover:bg-gray-100
                    px-4 py-2 rounded-lg text-sm
                    transition-colors">
                Batal
            </button>

            <button
                onclick="submitTemplate()"
                class="w-full sm:w-auto
                    px-5 py-2 text-white text-sm font-semibold
                    rounded-lg flex items-center justify-center gap-2
                    bg-violet-500 hover:bg-violet-600
                    transition-colors">

                <i class="fas fa-save"></i>
                Simpan
            </button>

        </div>
    </div>
</div>

@include('pages.template.metadata-detail-modal')

{{-- HIDDEN FORM --}}
<form id="formSaveTemplateHidden" action="{{ route('template.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="jenis_template" value="metadata">
    <input type="hidden" name="nama_tampilan"  id="hidNama">
    <div id="hidMetadataIds"></div>
    <div id="hidLocationIds"></div>
    <div id="hidUrutanBy"></div>
</form>

<script>
// ═══════════════════════════════════════════════════════════════
// DATA LOKASI — di-embed dari server
// ═══════════════════════════════════════════════════════════════
const ALL_LOCATIONS = @json(
    \App\Models\Location::select('location_id', 'nama_wilayah')
        ->orderBy('nama_wilayah')
        ->get()
        ->map(fn($l) => ['id' => (string)$l->location_id, 'nama' => $l->nama_wilayah])
        ->values()
);

const LOC_PROVINSI = ALL_LOCATIONS.filter(l => l.id.slice(-8) === '00000000');
const idxKab = {};
const idxKec = {};
const idxDes = {};

ALL_LOCATIONS.forEach(l => {
    const len = l.id.length;
    if (len < 10) return;

    const isProvinsi  = l.id.slice(-8) === '00000000';
    const isKabupaten = !isProvinsi && l.id.slice(-6) === '000000';
    const isKecamatan = !isProvinsi && !isKabupaten && l.id.slice(-4) === '0000';
    const isDesa      = !isProvinsi && !isKabupaten && !isKecamatan;

    if (isKabupaten) {
        const p = l.id.slice(0, 2);
        if (!idxKab[p]) idxKab[p] = [];
        idxKab[p].push(l);
    } else if (isKecamatan) {
        const p = l.id.slice(0, 4);
        if (!idxKec[p]) idxKec[p] = [];
        idxKec[p].push(l);
    } else if (isDesa) {
        const p = l.id.slice(0, 6);
        if (!idxDes[p]) idxDes[p] = [];
        idxDes[p].push(l);
    }
});


// ═══════════════════════════════════════════════════════════════
// ENDPOINTS & CONFIG
// ═══════════════════════════════════════════════════════════════
const SEARCH_META_URL   = '{{ route("template.search_metadata") }}';
const FETCH_PREVIEW_URL = '{{ route("template.fetch_preview") }}';
const URL_CHILDREN      = '{{ route("template.child_locations") }}';
const CSRF              = '{{ csrf_token() }}';
const IS_LOGGED_IN      = {{ Auth::check() ? 'true' : 'false' }};

// ═══════════════════════════════════════════════════════════════
// STATE CASCADE WILAYAH (native <select> — prefix m_)
// ═══════════════════════════════════════════════════════════════
const M_LEVELS = ['provinsi', 'kabupaten', 'kecamatan', 'desa'];
const M_LEVEL_LABEL = {
    provinsi: 'Provinsi', kabupaten: 'Kabupaten/Kota',
    kecamatan: 'Kecamatan', desa: 'Desa/Kelurahan',
};
const M_URLS = {
    kabupaten: '{{ route("template.get_kabupaten") }}',
    kecamatan: '{{ route("template.get_kecamatan_wil") }}',
    desa     : '{{ route("template.get_desa_wil") }}',
};
const mSelLoc = { provinsi: null, kabupaten: null, kecamatan: null, desa: null };
const mCaches = { kabupaten: {}, kecamatan: {}, desa: {} };

function mCap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

function onWilFocus(level) {
    const drop = document.getElementById('drop' + mCap(level));
    if (!drop.classList.contains('hidden')) return;
    showDropForLevel(level, '');
}

function onWilInput(level) {
    const q = document.getElementById('input' + mCap(level)).value.trim();
    showDropForLevel(level, q);
}

function getLocationsForLevel(level) {
    if (level === 'provinsi') return LOC_PROVINSI;
    if (level === 'kabupaten') {
        if (!mSelLoc.provinsi) return [];
        return idxKab[mSelLoc.provinsi.id.slice(0, 2)] || [];
    }
    if (level === 'kecamatan') {
        if (!mSelLoc.kabupaten) return [];
        return idxKec[mSelLoc.kabupaten.id.slice(0, 4)] || [];
    }
    if (level === 'desa') {
        if (!mSelLoc.kecamatan) return [];
        return idxDes[mSelLoc.kecamatan.id.slice(0, 6)] || [];
    }
    return [];
}

function showDropForLevel(level, q) {
    const drop = document.getElementById('drop' + mCap(level));
    let items = getLocationsForLevel(level);

    if (q) {
        items = items.filter(x => x.nama.toLowerCase().includes(q.toLowerCase()));
    }

    if (!items.length) {
        drop.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada hasil</p>';
        drop.classList.remove('hidden');
        return;
    }

    drop.innerHTML = items.map(x => {
        const isSel = mSelLoc[level] && mSelLoc[level].id === x.id;
        return `<button type="button"
            onclick="selectLevel('${level}', '${x.id}', '${escH(x.nama).replace(/'/g, "\\'")}')"
            class="w-full text-left px-4 py-2.5 flex items-center gap-2.5 border-b border-gray-50 last:border-0 transition-colors
                   ${isSel ? 'bg-emerald-50' : 'hover:bg-gray-50'}">
            ${isSel
                ? '<i class="fas fa-check-circle text-emerald-500 text-xs shrink-0"></i>'
                : '<i class="far fa-circle text-gray-300 text-xs shrink-0"></i>'}
            <span class="text-xs ${isSel ? 'font-semibold text-emerald-700' : 'text-gray-700'}">${escH(x.nama)}</span>
        </button>`;
    }).join('');
    drop.classList.remove('hidden');
}

function selectLevel(level, id, nama) {
    mSelLoc[level] = { id, nama };
    document.getElementById('input' + mCap(level)).value = nama;
    document.getElementById('val'   + mCap(level)).value = id;
    document.getElementById('clear' + mCap(level)).classList.remove('hidden');
    document.getElementById('drop'  + mCap(level)).classList.add('hidden');

    const idx = M_LEVELS.indexOf(level);
    M_LEVELS.slice(idx + 1).forEach(l => {
        mSelLoc[l] = null;
        document.getElementById('input' + mCap(l)).value = '';
        document.getElementById('val'   + mCap(l)).value = '';
        document.getElementById('clear' + mCap(l)).classList.add('hidden');
        document.getElementById('drop'  + mCap(l)).classList.add('hidden');
    });

    const next = M_LEVELS[idx + 1];
    if (next) {
        const ni = document.getElementById('input' + mCap(next));
        ni.disabled = false;
        ni.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        ni.classList.add('bg-white', 'text-gray-700');
        ni.placeholder = 'Cari ' + M_LEVEL_LABEL[next] + '...';
    }

    mUpdateBadge();
    updatePilihBtn();
}

function clearLevel(level) {
    const idx = M_LEVELS.indexOf(level);
    M_LEVELS.slice(idx).forEach(l => {
        mSelLoc[l] = null;
        document.getElementById('input' + mCap(l)).value = '';
        document.getElementById('val'   + mCap(l)).value = '';
        document.getElementById('clear' + mCap(l)).classList.add('hidden');
        document.getElementById('drop'  + mCap(l)).classList.add('hidden');
        if (l !== 'provinsi') {
            const ni = document.getElementById('input' + mCap(l));
            ni.disabled = true;
            ni.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            ni.classList.remove('bg-white', 'text-gray-700');
            ni.placeholder = 'Pilih ' + M_LEVEL_LABEL[M_LEVELS[M_LEVELS.indexOf(l) - 1]] + ' dulu...';
        }
    });
    mUpdateBadge();
    updatePilihBtn();
}

function updatePilihBtn() {
    // Halaman ini pakai dropdown metadata custom (selectedMeta), bukan <select id="metadataSelect">.
    const hasMeta = Object.keys(selectedMeta || {}).length > 0;
    const btn  = document.getElementById('btnPilih');
    const hint = document.getElementById('pilihHint');
    if (btn) btn.disabled = !hasMeta;
    if (hint) hint.textContent = hasMeta ? 'Wilayah bersifat opsional' : 'Pilih metadata terlebih dahulu';
}



function mUpdateBadge() {
    let deepest = null;
    for (let i = M_LEVELS.length-1; i >= 0; i--) {
        if (mSelLoc[M_LEVELS[i]]) { deepest = { level:M_LEVELS[i], ...mSelLoc[M_LEVELS[i]] }; break; }
    }
    const badge = document.getElementById('m_selectedWilayahBadge');
    if (deepest) {
        badge.classList.remove('hidden');
        document.getElementById('m_badgeNama').textContent  = deepest.nama;
        document.getElementById('m_badgeLevel').textContent = '(' + M_LEVEL_LABEL[deepest.level] + ')';
    } else { badge.classList.add('hidden'); }
}

function mGetDeepestLocId() {
    for (let i = M_LEVELS.length-1; i >= 0; i--) {
        if (mSelLoc[M_LEVELS[i]]) return mSelLoc[M_LEVELS[i]].id;
    }
    return null;
}



// ═══════════════════════════════════════════════════════════════
// METADATA DROPDOWN — pre-load saat DOMContentLoaded
// ═══════════════════════════════════════════════════════════════
let selectedMeta = {};
let metaTimeout  = null;
let metaLastResults = [];

async function fetchMetaSearch(q) {
    const params = new URLSearchParams();
    if (q) params.append('q', q);
    try {
        const r = await fetch(SEARCH_META_URL + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        });
        if (!r.ok) return [];
        return await r.json();
    } catch (e) {
        console.error('search metadata error', e);
        return [];
    }
}

function onMetaSearchFocus() {
    const box = document.getElementById('metaDropList');
    if (!box.classList.contains('hidden')) return;
    loadMetaDrop('');
}

function onMetaSearchInput() {
    clearTimeout(metaTimeout);
    const q = document.getElementById('metaSearch').value.trim();
    metaTimeout = setTimeout(() => loadMetaDrop(q), 250);
}

async function loadMetaDrop(q) {
    const box = document.getElementById('metaDropList');
    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat...</p>';
    box.classList.remove('hidden');

    const results = await fetchMetaSearch(q);
    metaLastResults = results;
    renderMetaDrop(results);
}

function renderMetaDrop(results) {
    const box = document.getElementById('metaDropList');
    if (!results.length) {
        box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada hasil</p>';
        box.classList.remove('hidden');
        return;
    }
    box.innerHTML = results.map(m => {
        const sel = !!selectedMeta[m.metadata_id];
        return `<button type="button"
            data-meta-id="${m.metadata_id}"
            onclick="toggleMetaById(this.dataset.metaId)"
            class="w-full text-left px-4 py-2.5 flex items-start gap-2.5 border-b border-gray-50 last:border-0 transition-colors
                   ${sel ? 'bg-sky-50' : 'hover:bg-gray-50'}">
            <span class="mt-0.5 shrink-0">
                ${sel ? '<i class="fas fa-check-circle text-sky-500 text-xs"></i>' : '<i class="far fa-circle text-gray-300 text-xs"></i>'}
            </span>
            <span class="flex flex-col gap-0.5 min-w-0">
                <span class="font-medium text-gray-800 text-xs">${escH(m.nama)}</span>
                <span class="text-gray-400 text-xs truncate">
                    ${escH(m.klasifikasi||'')}${m.satuan_data ? ' · ' + escH(m.satuan_data) : ''}${m.frekuensi_penerbitan ? ' · ' + escH(m.frekuensi_penerbitan) : ''}
                </span>
            </span>
        </button>`;
    }).join('');
    box.classList.remove('hidden');
}

function toggleMetaById(idStr) {
    const id = parseInt(idStr);
    const m  = metaLastResults.find(x => x.metadata_id === id);
    if (!m) return;
    toggleMeta(m.metadata_id, m.nama, m.klasifikasi || '', m.satuan_data || '', m.frekuensi_penerbitan || '');
}

function toggleMeta(id, nama, klasifikasi, satuan, frekuensi) {
    if (selectedMeta[id]) { delete selectedMeta[id]; }
    else { selectedMeta[id] = { metadata_id: id, nama, klasifikasi, satuan_data: satuan, frekuensi_penerbitan: frekuensi }; }
    renderMetaChips();
    renderMetaDrop(metaLastResults); // re-render pakai hasil AJAX terakhir, bukan query ulang
}

function renderMetaChips() {
    document.getElementById('selectedMetaChips').innerHTML =
        Object.values(selectedMeta).map(m =>
            `<span class="inline-flex items-center gap-1 bg-sky-50 border border-sky-200 text-sky-700 text-xs px-2.5 py-1 rounded-full">
                <i class="fas fa-database text-sky-300 text-xs"></i>
                ${escH(m.nama)}
                <button type="button" onclick="removeMeta(${m.metadata_id})"
                        class="text-sky-400 hover:text-sky-600 ml-1 leading-none">×</button>
            </span>`
        ).join('');
    // Sinkronkan state tombol/hint (jika ada di halaman).
    updatePilihBtn();
}

function removeMeta(id) { delete selectedMeta[id]; renderMetaChips(); }

// ═══════════════════════════════════════════════════════════════
// LOAD PREVIEW — fetch dari server
// ═══════════════════════════════════════════════════════════════
let allGrouped  = {};
let activeTab   = '';
let sortedRows  = [];
let selectedMap = {};
let expandedMap = {};
const PAGE_SIZE = 15;
let currentPage = 1;

async function loadMetadataPreview() {
    const metaIds = Object.keys(selectedMeta);
    if (!metaIds.length) { alert('Pilih minimal 1 metadata terlebih dahulu.'); return; }

    const locId = mGetDeepestLocId();
    const body  = new URLSearchParams();
    body.append('_token', CSRF);
    metaIds.forEach(id => body.append('metadata_ids[]', id));
    if (locId) body.append('location_ids[]', locId);

    const btn = document.querySelector('button[onclick="loadMetadataPreview()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat...'; }

    try {
        const r = await fetch(FETCH_PREVIEW_URL, {
        method: 'POST',
        body,
        headers: { 'Accept': 'application/json' }
    });

    const text = await r.text(); // baca raw response dulu
    console.log('RAW RESPONSE:', text);
    console.log('HTTP STATUS:', r.status);

    let d;
    try {
        d = JSON.parse(text);
    } catch(parseErr) {
        alert('Server tidak return JSON.\nStatus: ' + r.status + '\nResponse:\n' + text.slice(0, 500));
        return;
    }

    if (!d.success) {
        alert('Server error: ' + (d.message || JSON.stringify(d)));
        return;
    }

        allGrouped = flattenGrouped(d.grouped || {});
        selectedMap = {};
        expandedMap = {};
        currentPage = 1;

        activateTabs(allGrouped);
        document.getElementById('sectionResult').classList.remove('hidden');

        const metaNames = Object.values(selectedMeta).map(m => m.nama).join(', ');
        document.getElementById('resultDesc').textContent =
            (metaNames.length > 60 ? metaNames.slice(0, 60) + '…' : metaNames) +
            (locId ? ' · ' + (mSelLoc.desa||mSelLoc.kecamatan||mSelLoc.kabupaten||mSelLoc.provinsi)?.nama : '');
    } catch (e) {
        alert('Network error: ' + e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-search mr-2"></i> Pilih &amp; Tampilkan'; }
    }
}

// ═══════════════════════════════════════════════════════════════
// TAB FREKUENSI
// ═══════════════════════════════════════════════════════════════
const FREQ_KEYS = ['dekade', 'tahunan', 'semester', 'kuartal', 'bulanan'];

function activateTabs(grouped) {
    let firstActive = '';
    FREQ_KEYS.forEach(freq => {
        const count  = (grouped[freq] || []).length;
        const tabBtn = document.getElementById('tab-' + freq);
        const tabCnt = document.getElementById('tab-count-' + freq);
        tabCnt.textContent = count;
        if (count > 0) {
            tabBtn.disabled = false;
            tabBtn.classList.remove('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.add('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCnt.classList.remove('bg-gray-100', 'text-gray-400');
            tabCnt.classList.add('bg-sky-100', 'text-sky-600');
            if (!firstActive) firstActive = freq;
        } else {
            tabBtn.disabled = true;
            tabBtn.classList.add('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.remove('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCnt.classList.add('bg-gray-100', 'text-gray-400');
            tabCnt.classList.remove('bg-sky-100', 'text-sky-600');
        }
    });
    const total = FREQ_KEYS.reduce((s, f) => s + (grouped[f] || []).length, 0);
    document.getElementById('totalBadge').textContent = total + ' baris';
    if (firstActive) switchTab(firstActive);
}

function switchTab(freq) {
    activeTab = freq;
    FREQ_KEYS.forEach(f => {
        const btn = document.getElementById('tab-' + f);
        if (f === freq) {
            btn.classList.add('border-sky-500', 'text-sky-600');
            btn.classList.remove('border-transparent', 'text-gray-600');
        } else {
            btn.classList.remove('border-sky-500', 'text-sky-600');
            if (!btn.disabled) btn.classList.add('border-transparent', 'text-gray-600');
        }
    });

    sortedRows  = [...(allGrouped[freq] || [])];
    expandedMap = {};
    currentPage = 1;
    renderTable();
}


function flattenGrouped(grouped) {
    const result = {};
    Object.keys(grouped).forEach(freq => {
        result[freq] = [];
        (grouped[freq] || []).forEach(m => {
            const locs = (m.locations && m.locations.length)
                ? m.locations
                : [{ location_id: 0, nama_wilayah: 'Semua Wilayah', has_children: false }];
            locs.forEach(loc => {
                result[freq].push({
                    metadata_id:          m.metadata_id,
                    nama:                 m.nama,
                    klasifikasi:          m.klasifikasi || '',
                    satuan_data:          m.satuan_data || '',
                    frekuensi_penerbitan: m.frekuensi_penerbitan || '',
                    location_id:          loc.location_id,
                    nama_wilayah:         loc.nama_wilayah,
                    has_children:         loc.has_children,
                    depth:                0,
                });
            });
        });
    });
    return result;
}

// ═══════════════════════════════════════════════════════════════
// RENDER TABEL
// ═══════════════════════════════════════════════════════════════
function rowKey(row) { return `${row.metadata_id}_${row.location_id}_${row.depth || 0}`; }

function buildFlat(baseRows) {
    const result = [];
    function addRows(rows) {
        rows.forEach(row => {
            result.push(row);
            const key = rowKey(row);
            if (expandedMap[key]) addRows(expandedMap[key]);
        });
    }
    addRows(baseRows);
    return result;
}

function renderTable() {
    const tbody = document.getElementById('resultTbody');
    const flat  = buildFlat(sortedRows);
    const total = flat.length;

    if (!total) {
        tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-10 text-center text-gray-400 text-sm">
            <i class="fas fa-inbox text-3xl text-gray-200 block mb-2"></i>
            Tidak ada metadata untuk filter ini.
        </td></tr>`;
        document.getElementById('paginWrap').classList.add('hidden');
        return;
    }

    const start = (currentPage - 1) * PAGE_SIZE;
    const end   = Math.min(start + PAGE_SIZE, total);
    tbody.innerHTML = flat.slice(start, end).map(row => buildRow(row)).join('');
    const visibleRows = flat.slice(start, end);
    const allChecked = visibleRows.length > 0 &&
        visibleRows.every(row => selectedMap[rowKey(row)]);

    document.getElementById('checkAll').checked = allChecked;

    const totalPages = Math.ceil(total / PAGE_SIZE);
    const pw = document.getElementById('paginWrap');
    if (totalPages > 1) {
        pw.classList.remove('hidden');
        document.getElementById('paginInfo').textContent = `Menampilkan ${start+1}–${end} dari ${total}`;
        function buildPaginBtns(totalPages, cur) {
            let pages = [];
            if (totalPages <= 7) {
                for (let p = 1; p <= totalPages; p++) pages.push(p);
            } else {
                pages = [1];
                if (cur > 3) pages.push('...');
                const s = Math.max(2, cur - 1), e = Math.min(totalPages - 1, cur + 1);
                for (let p = s; p <= e; p++) pages.push(p);
                if (cur < totalPages - 2) pages.push('...');
                pages.push(totalPages);
            }
            const prev = cur > 1 ? `<button onclick="goPage(${cur-1})" class="w-7 h-7 text-xs rounded-md font-medium border border-gray-200 text-gray-500 hover:bg-gray-50">&lt;</button>` : '';
            const next = cur < totalPages ? `<button onclick="goPage(${cur+1})" class="w-7 h-7 text-xs rounded-md font-medium border border-gray-200 text-gray-500 hover:bg-gray-50">&gt;</button>` : '';
            const mid = pages.map(p => p === '...'
                ? `<span class="px-1 text-gray-400 text-xs self-center">…</span>`
                : `<button onclick="goPage(${p})" class="w-7 h-7 text-xs rounded-md font-medium transition-colors ${p===cur?'bg-sky-500 text-white':'border border-gray-200 text-gray-500 hover:bg-gray-50'}">${p}</button>`
            ).join('');
            return prev + mid + next;
        }

        document.getElementById('paginBtns').innerHTML = buildPaginBtns(totalPages, currentPage);
    } else {
        pw.classList.add('hidden');
    }
}

function buildRow(row) {
    const key        = rowKey(row);
    const depth      = row.depth || 0;
    const checked    = !!selectedMap[key];
    const isExpanded = !!expandedMap[key];

    // Indent hanya membesar di layar >= sm; di mobile tetap flat (lihat CSS var di bawah)
    const indentPx = `calc(var(--row-indent) * ${depth})`;

    const displayName = `<span class="font-${depth === 0 ? 'semibold' : 'medium'} text-gray-800">${escH(row.nama)} di ${escH(row.nama_wilayah)}</span>`;

    let detailBtn = `<span class="text-gray-300 text-xs">—</span>`;
    if (row.has_children) {
        detailBtn = `<button type="button"
            onclick="toggleExpand('${key}')"
            title="${isExpanded ? 'Sembunyikan turunan' : 'Tampilkan 1 level wilayah di bawah'}"
            class="inline-flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 rounded border font-bold text-xs select-none transition-colors
                   ${isExpanded
                       ? 'bg-sky-100 border-sky-300 text-sky-700 hover:bg-sky-200'
                       : 'bg-white border-gray-300 text-gray-600 hover:border-sky-400 hover:text-sky-600'}">
            ${isExpanded ? '<i class="fa-solid fa-angle-left"></i>' : '<i class="fa-solid fa-angle-down"></i>'}
        </button>`;
    }

    const levelLabel = depth === 1 ? 'Turunan wilayah' : depth > 1 ? 'Turunan wilayah lanjutan' : '';
    const rowBg = depth === 0
        ? 'hover:bg-sky-50'
        : (depth === 1 ? 'bg-sky-50/50 hover:bg-sky-100/60' : 'bg-violet-50/40 hover:bg-violet-100/50');
    const borderColor = depth === 1 ? '#7dd3fc' : (depth > 1 ? '#c4b5fd' : 'transparent');

    return `<tr class="${rowBg} transition-colors">
        <td class="py-3 pr-2" style="padding-left:8px; border-left:3px solid ${borderColor};">
            <input type="checkbox" class="row-chk rounded border-gray-300 cursor-pointer"
                value="${escH(key)}"
                onchange="onRowCheck(this, ${row.metadata_id}, ${row.location_id}, ${depth})"
                ${checked ? 'checked' : ''}>
        </td>
        <td class="px-3 sm:px-4 py-3 text-xs min-w-0 break-words" style="padding-left: calc(12px + ${depth > 0 ? 'var(--row-indent)' : '0px'})">
            ${depth > 0 ? `<span class="block sm:inline text-gray-400 text-[10px] sm:text-xs font-semibold uppercase tracking-wide mb-0.5 sm:mb-0 sm:mr-1.5">↳ ${levelLabel}</span>` : ''}
            ${displayName}
            ${row.frekuensi_penerbitan ? `<span class="ml-1 text-gray-400 font-normal">(${escH(row.frekuensi_penerbitan)})</span>` : ''}
        </td>
        <td class="px-1 sm:px-3 py-3 text-center">
            <button type="button"
                    onclick="openMetadataModal(${row.metadata_id})"
                    title="Lihat detail metadata"
                    class="inline-flex items-center justify-center bg-transparent border-0 p-0 cursor-pointer">
                <i class="fas fa-circle-info text-sky-500 hover:text-sky-600 transition-colors"></i>
            </button>
        </td>
        <td class="px-1 sm:px-3 py-3 text-center">${detailBtn}</td>
    </tr>`;
}

// ═══════════════════════════════════════════════════════════════
// EXPAND / COLLAPSE
// ═══════════════════════════════════════════════════════════════
async function toggleExpand(key) {
    if (expandedMap[key]) { collapseKey(key); renderTable(); return; }

    const parts      = key.split('_');
    const metadataId = parseInt(parts[0]);
    const locationId = parseInt(parts[1]);
    const depth      = parseInt(parts[2] || '0');
    const parentRow  = findRowByKey(key);

    try {
        const r = await fetch(`${URL_CHILDREN}?metadata_id=${metadataId}&location_id=${locationId}`);
        const d = await r.json();
        if (!d.children || !d.children.length) {
            if (parentRow) parentRow.has_children = false;
            renderTable(); return;
        }
        expandedMap[key] = d.children.map(c => ({
            metadata_id:          metadataId,
            nama:                 parentRow ? parentRow.nama : '',
            klasifikasi:          parentRow ? parentRow.klasifikasi : '',
            satuan_data:          parentRow ? parentRow.satuan_data : '',
            frekuensi_penerbitan: parentRow ? parentRow.frekuensi_penerbitan : '',
            location_id:          c.location_id,
            nama_wilayah:         c.nama_wilayah,
            has_children:         c.has_children,
            depth:                depth + 1,
        }));
        renderTable();
    } catch(e) { console.error('Expand error:', e); }
}

function collapseKey(key) {
    if (expandedMap[key]) {
        expandedMap[key].forEach(child => collapseKey(rowKey(child)));
        delete expandedMap[key];
    }
}

function findRowByKey(key) {
    for (const row of sortedRows) { if (rowKey(row) === key) return row; }
    for (const children of Object.values(expandedMap)) {
        for (const child of children) { if (rowKey(child) === key) return child; }
    }
    return null;
}

// ═══════════════════════════════════════════════════════════════
// SELECTION
// ═══════════════════════════════════════════════════════════════
function onRowCheck(cb, metadataId, locationId, depth) {
    const key = cb.value;
    if (cb.checked) {
        const flat = buildFlat(sortedRows);
        const row  = flat.find(r => rowKey(r) === key);
        if (row) selectedMap[key] = row;
    } else { delete selectedMap[key]; }
    updateSelBar();
}

function toggleAll(masterCb) {
    const flat = buildFlat(sortedRows);

    if (masterCb.checked) {

        // Checklist semua data dari seluruh halaman
        flat.forEach(row => {
            const key = rowKey(row);
            selectedMap[key] = row;
        });

    } else {

        // Hapus semua pilihan
        selectedMap = {};
    }

    // Re-render supaya semua checkbox sinkron
    renderTable();

    // Update info selection bar
    updateSelBar();
}

function clearAllSel() {
    selectedMap = {};
    document.querySelectorAll('.row-chk').forEach(cb => cb.checked = false);
    const ca = document.getElementById('checkAll'); if (ca) ca.checked = false;
    updateSelBar();
}

function updateSelBar() {
    const count = Object.keys(selectedMap).length;
    const bar = document.getElementById('selBar');
    if (count > 0) { bar.classList.remove('hidden'); bar.style.display = 'flex'; }
    else { bar.classList.add('hidden'); bar.style.display = 'none'; }
    document.getElementById('selCount').textContent = count + ' metadata dipilih';
}

// ═══════════════════════════════════════════════════════════════
// PENGATURAN URUTAN
// ═══════════════════════════════════════════════════════════════
function terapkanUrutan() {
    const byKlas = document.getElementById('chkKlasifikasi').checked;
    const byWil  = document.getElementById('chkWilayah').checked;
    const base   = allGrouped[activeTab] || [];
    if (!byKlas && !byWil) {
        sortedRows = [...base];
    } else {
        sortedRows = [...base].sort((a, b) => {
            if (byKlas) { const k = (a.klasifikasi||'').localeCompare(b.klasifikasi||''); if (k !== 0) return k; }
            if (byWil)  { const w = (a.nama_wilayah||'').localeCompare(b.nama_wilayah||''); if (w !== 0) return w; }
            return (a.nama||'').localeCompare(b.nama||'');
        });
    }
    expandedMap = {};
    currentPage = 1;
    renderTable();
}

// ═══════════════════════════════════════════════════════════════
// PAGINATION
// ═══════════════════════════════════════════════════════════════
function goPage(p) { currentPage = p; renderTable(); }

// ═══════════════════════════════════════════════════════════════
// MODAL SIMPAN
// ═══════════════════════════════════════════════════════════════
function openSaveModal() {
    // Bisa simpan dari pilihan tabel preview (selectedMap) atau langsung dari chips metadata (selectedMeta).
    const metaCountFromMap = Object.keys(selectedMap || {}).length
        ? new Set(Object.values(selectedMap).map(r => r.metadata_id)).size
        : 0;
    const metaCountFromChips = Object.keys(selectedMeta || {}).length;

    const metaCount = metaCountFromMap || metaCountFromChips;
    if (!metaCount) { alert('Pilih minimal 1 metadata untuk disimpan.'); return; }

    document.getElementById('modalMetaCount').textContent = metaCount;
    document.getElementById('inputNama').value = '';
    document.getElementById('errNama').classList.add('hidden');
    document.getElementById('modalSave').style.display = 'flex';
    setTimeout(() => document.getElementById('inputNama').focus(), 100);
}

function closeModal() { document.getElementById('modalSave').style.display = 'none'; }

async function submitTemplate() {
    const nama = document.getElementById('inputNama').value.trim();
    if (!nama) { document.getElementById('errNama').classList.remove('hidden'); return; }
 
    // ── Tentukan sumber data: selectedMap (dari tabel preview) atau selectedMeta (dari chips) ──
    const hasMapSelection = Object.keys(selectedMap || {}).length > 0;
 
    // Kumpulkan metadata_id yang unik
    const metaSet = hasMapSelection
        ? new Set(Object.values(selectedMap).map(r => r.metadata_id))
        : new Set(Object.keys(selectedMeta || {}).map(x => parseInt(x, 10)).filter(n => !Number.isNaN(n)));
 
    if (!metaSet.size) { alert('Pilih minimal 1 metadata.'); return; }
 
    // ── Bangun peta lokasi per metadata ──────────────────────────────────────
    // Format: { "metadata_id": [location_id, ...] }
    //
    // Sumber data bisa dari dua tempat:
    //   1. selectedMap  = hasil centang di tabel preview (sudah ada metadata_id & location_id per baris)
    //   2. selectedMeta = chips metadata + deepest wilayah dari cascade (satu lokasi untuk semua meta)
    const metaLocMap = {};
 
    if (hasMapSelection) {
        // Tiap baris di selectedMap sudah membawa metadata_id + location_id-nya sendiri
        Object.values(selectedMap).forEach(row => {
            const mid = String(row.metadata_id);
            const lid = row.location_id;
            if (!metaLocMap[mid]) metaLocMap[mid] = [];
            if (lid && !metaLocMap[mid].includes(lid)) {
                metaLocMap[mid].push(lid);
            }
        });
        // Pastikan semua metadata terdaftar (meski tanpa lokasi)
        metaSet.forEach(mid => {
            if (!metaLocMap[String(mid)]) metaLocMap[String(mid)] = [];
        });
    } else {
        // Mode chips: satu lokasi (deepest cascade) berlaku untuk semua metadata
        const deepest = mGetDeepestLocId();
        metaSet.forEach(mid => {
            metaLocMap[String(mid)] = deepest ? [parseInt(deepest)] : [];
        });
    }
 
    if (IS_LOGGED_IN) {
        // ── Submit via hidden form ──────────────────────────────────────
        document.getElementById('hidNama').value = nama;
 
        document.getElementById('hidMetadataIds').innerHTML =
            [...metaSet].map(id => `<input type="hidden" name="metadata_ids[]" value="${id}">`).join('');
 
        // Kirim peta lokasi sebagai JSON string (1 field)
        // Field location_ids[] lama tidak dipakai lagi
        let mapInput = document.getElementById('hidMetaLocMap');
        if (!mapInput) {
            mapInput = document.createElement('input');
            mapInput.type = 'hidden';
            mapInput.name = 'metadata_location_ids';
            mapInput.id   = 'hidMetaLocMap';
            document.getElementById('formSaveTemplateHidden').appendChild(mapInput);
        }
        mapInput.value = JSON.stringify(metaLocMap);
 
        document.getElementById('hidLocationIds').innerHTML = ''; // kosongkan field lama
        document.getElementById('hidUrutanBy').innerHTML =
            [...document.querySelectorAll('input[name="urutan_by[]"]:checked')]
                .map(c => `<input type="hidden" name="urutan_by[]" value="${c.value}">`).join('');
 
        document.getElementById('formSaveTemplateHidden').submit();
    } else {
        // ── Simpan ke localStorage (guest) ─────────────────────────────
        const body = new URLSearchParams();
        body.append('_token', CSRF);
        body.append('nama_tampilan', nama);
        body.append('jenis_template', 'metadata');
        [...metaSet].forEach(id => body.append('metadata_ids[]', id));
        body.append('metadata_location_ids', JSON.stringify(metaLocMap));
 
        try {
            const r = await fetch('{{ route("template.store") }}', { method: 'POST', body });
            const d = await r.json();
            if (d.success && d.storage === 'local') {
                const existing = JSON.parse(localStorage.getItem('guest_templates') || '[]');
                if (existing.length >= 2) {
                    alert('Anda telah mencapai batas maksimal 2 template untuk pengguna guest. Login untuk menyimpan lebih banyak template.');
                    return;
                }
                d.template_data._local_id = 'local_' + Date.now();
                existing.push(d.template_data);
                localStorage.setItem('guest_templates', JSON.stringify(existing));
                alert(`Template "${nama}" disimpan di browser.\n(Login untuk menyimpan ke server)`);
                closeModal();
                window.location.href = d.redirect;
            }
        } catch(e) { alert('Gagal menyimpan: ' + e.message); }
    }
}

// ═══════════════════════════════════════════════════════════════
// UTILS
// ═══════════════════════════════════════════════════════════════
function escH(s) {
    const d = document.createElement('div');
    d.innerText = s || '';
    return d.innerHTML;
}

document.addEventListener('click', e => {
    if (!document.getElementById('metaDropWrap').contains(e.target))
        document.getElementById('metaDropList').classList.add('hidden');
    if (e.target === document.getElementById('modalSave')) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
@endsection
