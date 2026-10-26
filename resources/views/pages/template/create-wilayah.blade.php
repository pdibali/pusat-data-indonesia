@extends('layouts.main')

@section('content')
<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('template.create') }}" class="hover:text-sky-500 transition-colors">Buat Template</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Template Wilayah</span>
    </div>

    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Buat Template Wilayah</h1>
            <p class="text-sm text-gray-400 mt-1">Pilih wilayah bertingkat, sistem menampilkan metadata beserta hierarkinya</p>
        </div>
        <span class="hidden sm:block px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-100 text-xs font-semibold rounded-full">
            <i class="fas fa-tags mr-1"></i> Jenis: Wilayah
        </span>
    </div>

    {{-- ═══ SECTION 1 — Cascade Pilih Wilayah ═══ --}}
    <div class="p-5 border border-gray-200 rounded-xl mb-2 bg-gray-50/40">
        <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white text-xs font-bold">1</span>
            Pilih Wilayah
            <span class="text-xs font-normal text-gray-400">(pilih sampai tingkat yang diinginkan)</span>
        </h2>

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
        <div id="selectedWilayahBadge" class="hidden mt-4 flex items-center gap-2.5 p-3 bg-emerald-50 border border-emerald-200 rounded-lg w-fit">
            <i class="fas fa-map-marker-alt text-emerald-500"></i>
            <div>
                <p class="text-xs text-emerald-700 font-semibold" id="badgeNama">—</p>
                <p class="text-xs text-emerald-500 mt-0.5" id="badgeLevel">—</p>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="button" id="btnPilih" onclick="loadWilayahTable()" disabled
                class="px-5 py-2.5 bg-emerald-500 text-white text-sm font-semibold rounded-lg
                       shadow-md flex items-center gap-2 transition-all
                       disabled:opacity-40 disabled:cursor-not-allowed
                       enabled:hover:bg-emerald-600 enabled:shadow-emerald-400/30">
                <i class="fas fa-search"></i> Pilih &amp; Tampilkan
            </button>
            <p class="text-xs text-gray-400" id="pilihHint">Pilih minimal tingkat Provinsi</p>
        </div>
    </div>

    {{-- ═══ SECTION 2 — Tab Frekuensi + Tabel ═══ --}}
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
                  class="hidden sm:block text-xs bg-emerald-50 text-emerald-600 border border-emerald-100 px-2.5 py-1 rounded-full font-semibold">
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
                        <th class="px-4 py-3 font-semibold text-gray-600">Metadata</th>
                        <th class="w-16 px-3 py-3 text-center font-semibold text-gray-600">Info</th>
                        <th class="w-20 px-3 py-3 text-center font-semibold text-gray-600">
                            <span class="block sm:hidden">Det.</span>
                            <span class="hidden sm:block">Detail</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="resultTbody" class="divide-y divide-gray-100 bg-white"></tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="paginWrap" class="hidden mt-3 flex flex-wrap items-center justify-between gap-y-2 text-xs text-gray-500">
            <span id="paginInfo" class="shrink-0 hidden sm:block"></span>
            <div id="paginBtns" class="flex flex-wrap gap-1 mx-auto sm:mx-0"></div>
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
                <input type="text" id="inputNama" placeholder="cth: Data Wilayah Bali"
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
                id="btnCancelSaveTemplate"
                class="w-full sm:w-auto
                    border border-gray-300
                    text-gray-500 hover:bg-gray-100
                    px-4 py-2 rounded-lg text-sm
                    transition-colors">
                Batal
            </button>

            <button
                onclick="submitTemplate()"
                id="btnSaveTemplate"
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

{{-- ═══ DATA SEMUA LOKASI — di-embed dari server tanpa loading ═══ --}}
<script>

const URL_RESTORE_STATE = '{{ route("template.restore_state") }}';

// Deteksi jika kembali dari grafik
const urlParams = new URLSearchParams(window.location.search);
const shouldRestore = urlParams.get('restore') === '1';

if (shouldRestore) {
    restoreWilayahState();
}

// Tambahkan fungsi ini setelah fungsi updatePilihBtn()
async function restoreWilayahState() {
    try {
        const grafikState = sessionStorage.getItem('grafikState');
        if (!grafikState) return;

        const state = JSON.parse(grafikState);
        console.log('[Restore] Memulihkan state:', state);

        // 1. Restore pilihan wilayah
        await restoreWilayahSelection(state.locId);
        
        // 2. Auto-click "Pilih & Tampilkan"
        setTimeout(() => {
            document.getElementById('btnPilih').click();
        }, 500);

    } catch (err) {
        console.error('[Restore] Error:', err);
    }
}

async function restoreWilayahSelection(locId) {
    // Parse location_id untuk menentukan level dan cascade selection
    const locStr = String(locId);
    
    if (locStr.slice(-8) === '00000000') {
        // Provinsi
        const provinsi = ALL_LOCATIONS.find(l => l.id === locId);
        if (provinsi) selectLevel('provinsi', provinsi.id, provinsi.nama);
        
    } else if (locStr.slice(-6) === '000000') {
        // Kabupaten
        const kab = ALL_LOCATIONS.find(l => l.id === locId);
        if (kab) {
            const provId = locStr.slice(0, 2) + '00000000';
            const prov = LOC_PROVINSI.find(l => l.id === provId);
            if (prov) selectLevel('provinsi', prov.id, prov.nama);
            setTimeout(() => selectLevel('kabupaten', kab.id, kab.nama), 100);
        }
        
    } else if (locStr.slice(-4) === '0000') {
        // Kecamatan
        const kec = ALL_LOCATIONS.find(l => l.id === locId);
        if (kec) {
            const provId = locStr.slice(0, 2) + '00000000';
            const kabId = locStr.slice(0, 4) + '000000';
            const prov = LOC_PROVINSI.find(l => l.id === provId);
            const kab = ALL_LOCATIONS.find(l => l.id === kabId);
            
            if (prov) selectLevel('provinsi', prov.id, prov.nama);
            setTimeout(() => {
                if (kab) selectLevel('kabupaten', kab.id, kab.nama);
                setTimeout(() => selectLevel('kecamatan', kec.id, kec.nama), 100);
            }, 100);
        }
        
    } else {
        // Desa - full cascade
        const desa = ALL_LOCATIONS.find(l => l.id === locId);
        if (desa) {
            const kecId = locStr.slice(0, 6) + '0000';
            const kabId = locStr.slice(0, 4) + '000000';
            const provId = locStr.slice(0, 2) + '00000000';
            
            const prov = LOC_PROVINSI.find(l => l.id === provId);
            const kab = ALL_LOCATIONS.find(l => l.id === kabId);
            const kec = ALL_LOCATIONS.find(l => l.id === kecId);
            
            if (prov) selectLevel('provinsi', prov.id, prov.nama);
            setTimeout(() => {
                if (kab) selectLevel('kabupaten', kab.id, kab.nama);
                setTimeout(() => {
                    if (kec) selectLevel('kecamatan', kec.id, kec.nama);
                    setTimeout(() => selectLevel('desa', desa.id, desa.nama), 100);
                }, 100);
            }, 100);
        }
    }
}

// Semua data lokasi di-inject langsung dari PHP (tanpa AJAX saat buka halaman)
// Format: { location_id, nama_wilayah }
// Dikelompokkan berdasarkan level menggunakan suffix location_id

const ALL_LOCATIONS = @json(
    \App\Models\Location::select('location_id', 'nama_wilayah')
        ->orderBy('nama_wilayah')
        ->get()
        ->map(fn($l) => ['id' => (string)$l->location_id, 'nama' => $l->nama_wilayah])
        ->values()
);

// ─── Pre-group per level (langsung di memori, tanpa fetch) ────
// Provinsi: suffix 8 nol   → PPXXXXXXXX di mana XXXXXXXX = 00000000
// Kabupaten: suffix 6 nol, bukan 8 nol  → PPKK000000
// Kecamatan: suffix 4 nol, bukan 6 nol  → PPKKKK0000 (6 digit pertama unik)
// Desa: tidak ada suffix 4 nol

const LOC_PROVINSI  = ALL_LOCATIONS.filter(l => l.id.slice(-8) === '00000000');
const LOC_KABUPATEN = {}; // diisi saat provinsi dipilih (filter by prefix 2 digit)
const LOC_KECAMATAN = {}; // diisi saat kabupaten dipilih (filter by prefix 4 digit)
const LOC_DESA      = {}; // diisi saat kecamatan dipilih (filter by prefix 6 digit)

// Pre-build index per prefix agar filter O(1)
const idxKab = {}; // key: prefix 2 digit  → [{id,nama}]
const idxKec = {}; // key: prefix 4 digit  → [{id,nama}]
const idxDes = {}; // key: prefix 6 digit  → [{id,nama}]

ALL_LOCATIONS.forEach(l => {
    const len = l.id.length;
    if (len < 10) return; // skip data tidak standar

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

// ─────────────────────────────────────────────────────────────
// ENDPOINTS (hanya untuk fetch metadata, bukan lokasi)
// ─────────────────────────────────────────────────────────────
const URL_FETCH_WIL = '{{ route("template.fetch_wilayah") }}';
const URL_CHILDREN  = '{{ route("template.child_locations") }}';
const CSRF          = '{{ csrf_token() }}';

// ─── State cascade ────────────────────────────────────────────
const LEVELS = ['provinsi', 'kabupaten', 'kecamatan', 'desa'];
const LEVEL_LABEL = {
    provinsi: 'Provinsi', kabupaten: 'Kabupaten/Kota',
    kecamatan: 'Kecamatan', desa: 'Desa/Kelurahan',
};

const selLoc = { provinsi: null, kabupaten: null, kecamatan: null, desa: null };

// ─── State tabel ──────────────────────────────────────────────
// allGrouped = { dekade: [], tahunan: [], semester: [], kuartal: [], bulanan: [] }
// allRows = flat array semua rows dari tab aktif
let allGrouped   = {};
let activeTab    = '';
let sortedRows   = [];
let selectedMap  = {};
let expandedMap  = {};
const PAGE_SIZE  = 15;
let currentPage  = 1;

// ═══════════════════════════════════════════════════════════════
// CASCADE DROPDOWN — NO AJAX, gunakan data yang sudah di-embed
// ═══════════════════════════════════════════════════════════════

function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

function onWilFocus(level) {
    const drop = document.getElementById('drop' + cap(level));
    if (!drop.classList.contains('hidden')) return;
    showDropForLevel(level, '');
}

function onWilInput(level) {
    const q = document.getElementById('input' + cap(level)).value.trim();
    showDropForLevel(level, q);
}

function getLocationsForLevel(level) {
    if (level === 'provinsi') return LOC_PROVINSI;
    if (level === 'kabupaten') {
        if (!selLoc.provinsi) return [];
        return idxKab[selLoc.provinsi.id.slice(0, 2)] || [];
    }
    if (level === 'kecamatan') {
        if (!selLoc.kabupaten) return [];
        return idxKec[selLoc.kabupaten.id.slice(0, 4)] || [];
    }
    if (level === 'desa') {
        if (!selLoc.kecamatan) return [];
        return idxDes[selLoc.kecamatan.id.slice(0, 6)] || [];
    }
    return [];
}

function showDropForLevel(level, q) {
    const drop = document.getElementById('drop' + cap(level));
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
        const isSel = selLoc[level] && selLoc[level].id === x.id;
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
    selLoc[level] = { id, nama };
    document.getElementById('input' + cap(level)).value = nama;
    document.getElementById('val'   + cap(level)).value = id;
    document.getElementById('clear' + cap(level)).classList.remove('hidden');
    document.getElementById('drop'  + cap(level)).classList.add('hidden');

    const idx = LEVELS.indexOf(level);
    LEVELS.slice(idx + 1).forEach(l => {
        selLoc[l] = null;
        document.getElementById('input' + cap(l)).value = '';
        document.getElementById('val'   + cap(l)).value = '';
        document.getElementById('clear' + cap(l)).classList.add('hidden');
        document.getElementById('drop'  + cap(l)).classList.add('hidden');
    });

    const next = LEVELS[idx + 1];
    if (next) {
        const ni = document.getElementById('input' + cap(next));
        ni.disabled = false;
        ni.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        ni.classList.add('bg-white', 'text-gray-700');
        ni.placeholder = 'Cari ' + LEVEL_LABEL[next] + '...';
    }

    updateBadge();
    updatePilihBtn();
}

function clearLevel(level) {
    const idx = LEVELS.indexOf(level);
    LEVELS.slice(idx).forEach(l => {
        selLoc[l] = null;
        document.getElementById('input' + cap(l)).value = '';
        document.getElementById('val'   + cap(l)).value = '';
        document.getElementById('clear' + cap(l)).classList.add('hidden');
        document.getElementById('drop'  + cap(l)).classList.add('hidden');
        if (l !== 'provinsi') {
            const ni = document.getElementById('input' + cap(l));
            ni.disabled = true;
            ni.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            ni.classList.remove('bg-white', 'text-gray-700');
            ni.placeholder = 'Pilih ' + LEVEL_LABEL[LEVELS[LEVELS.indexOf(l) - 1]] + ' dulu...';
        }
    });
    updateBadge();
    updatePilihBtn();
}

function updateBadge() {
    let deepest = null;
    for (let i = LEVELS.length - 1; i >= 0; i--) {
        if (selLoc[LEVELS[i]]) { deepest = { level: LEVELS[i], ...selLoc[LEVELS[i]] }; break; }
    }
    const badge = document.getElementById('selectedWilayahBadge');
    if (deepest) {
        badge.classList.remove('hidden');
        document.getElementById('badgeNama').textContent  = deepest.nama;
        document.getElementById('badgeLevel').textContent = 'Level: ' + LEVEL_LABEL[deepest.level];
    } else {
        badge.classList.add('hidden');
    }
}

function updatePilihBtn() {
    const hasAny = LEVELS.some(l => selLoc[l]);
    document.getElementById('btnPilih').disabled = !hasAny;
    document.getElementById('pilihHint').textContent = hasAny
        ? 'Klik "Pilih & Tampilkan" untuk memuat metadata'  
        : 'Pilih minimal tingkat Provinsi';
}

// ═══════════════════════════════════════════════════════════════
// LOAD TABEL — fetch metadata dari server berdasarkan lokasi
// ═══════════════════════════════════════════════════════════════

function getDeepLocId() {
    for (let i = LEVELS.length - 1; i >= 0; i--) {
        if (selLoc[LEVELS[i]]) return selLoc[LEVELS[i]].id;
    }
    return null;
}

function buildTimeBody() {
    const body = new URLSearchParams();
    body.append('_token', CSRF);

    const deepLocId = getDeepLocId();
    if (deepLocId) body.append('location_ids[]', deepLocId);

    if (activeTab) {
        body.append('frekuensi', activeTab);

        if (['dekade', 'tahunan'].includes(activeTab)) {
            const from = document.getElementById('periodFromSimple')?.value;
            const to   = document.getElementById('periodToSimple')?.value;
            if (from) body.append('period_from', from);
            if (to)   body.append('period_to',   to);
        } else if (activeTab) {
            const yf = document.getElementById('yearFrom')?.value;
            const yt = document.getElementById('yearTo')?.value;
            const pf = document.getElementById('periodFrom')?.value;
            const pt = document.getElementById('periodTo')?.value;
            if (yf) body.append('year_from',   yf);
            if (yt) body.append('year_to',     yt);
            if (pf) body.append('period_from', pf);
            if (pt) body.append('period_to',   pt);
        }
    }
    return body;
}

async function loadWilayahTable() {
    const deepLocId = getDeepLocId();
    if (!deepLocId) return;

    

    const btn = document.getElementById('btnPilih');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat...';

    try {
        const body = buildTimeBody();
        const r = await fetch(URL_FETCH_WIL, { method: 'POST', body });
        const d = await r.json();
        if (!d.success) throw new Error('Server error');

        allGrouped  = d.grouped || {};
        selectedMap = {};
        expandedMap = {};
        currentPage = 1;

        // Aktifkan tab yang punya data
        activateTabs(allGrouped);

        document.getElementById('sectionResult').classList.remove('hidden');

        let deepName = '';
        for (let i = LEVELS.length - 1; i >= 0; i--) {
            if (selLoc[LEVELS[i]]) { deepName = selLoc[LEVELS[i]].nama; break; }
        }
        document.getElementById('resultDesc').textContent = 'Metadata dari: ' + deepName;

    } catch (e) {
        alert('Gagal memuat data: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search mr-2"></i> Pilih &amp; Tampilkan';
    }
}

// ═══════════════════════════════════════════════════════════════
// TAB FREKUENSI — aktifkan dan switch
// ═══════════════════════════════════════════════════════════════

const FREQ_KEYS = ['dekade', 'tahunan', 'semester', 'kuartal', 'bulanan'];

function activateTabs(grouped) {
    let firstActive = '';
    FREQ_KEYS.forEach(freq => {
        const count   = (grouped[freq] || []).length;
        const tabBtn  = document.getElementById('tab-' + freq);
        const tabCnt  = document.getElementById('tab-count-' + freq);

        tabCnt.textContent = count;

        if (count > 0) {
            tabBtn.disabled = false;
            tabBtn.classList.remove('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.add('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCnt.classList.remove('bg-gray-100', 'text-gray-400');
            tabCnt.classList.add('bg-emerald-100', 'text-emerald-600');
            if (!firstActive) firstActive = freq;
        } else {
            tabBtn.disabled = true;
            tabBtn.classList.add('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.remove('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCnt.classList.add('bg-gray-100', 'text-gray-400');
            tabCnt.classList.remove('bg-emerald-100', 'text-emerald-600');
        }
    });

    if (firstActive) {
        switchTab(firstActive);
    } else {
        document.getElementById('totalBadge').textContent = '0 baris';
    }
}

function switchTab(freq) {
    activeTab = freq;

    FREQ_KEYS.forEach(f => {
        const btn = document.getElementById('tab-' + f);
        if (f === freq) {
            btn.classList.add('border-emerald-500', 'text-emerald-600');
            btn.classList.remove('border-transparent', 'text-gray-600');
        } else {
            btn.classList.remove('border-emerald-500', 'text-emerald-600');
            if (!btn.disabled) btn.classList.add('border-transparent', 'text-gray-600');
        }
    });

    // Render tabel dari grouped yang sudah ada (tanpa reload server)
    sortedRows  = [...(allGrouped[freq] || [])];
    document.getElementById('totalBadge').textContent = sortedRows.length + ' baris';
    expandedMap = {};
    currentPage = 1;
    renderTable();
}

// ═══════════════════════════════════════════════════════════════
// RENDER TABEL
// ═══════════════════════════════════════════════════════════════

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

function rowKey(row) {
    return `${row.metadata_id}_${row.location_id}_${row.depth || 0}`;
}

function renderTable() {
    const tbody = document.getElementById('resultTbody');
    const flat  = buildFlat(sortedRows);
    const total = flat.length;

    if (!total) {
        tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-10 text-center text-gray-400 text-sm">
            <i class="fas fa-inbox text-3xl text-gray-200 block mb-2"></i>
            Tidak ada metadata dari wilayah dan periode ini.
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
        document.getElementById('paginInfo').textContent = `Menampilkan ${start + 1}–${end} dari ${total}`;
        function paginBtnHtml(totalPages, cur) {
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
            const prev = cur > 1 ? `<button onclick="goPage(${cur-1})" class="min-w-[28px] h-7 px-1 text-xs rounded-md font-medium border border-gray-200 text-gray-500 hover:bg-gray-50">&lt;</button>` : '';
            const next = cur < totalPages ? `<button onclick="goPage(${cur+1})" class="min-w-[28px] h-7 px-1 text-xs rounded-md font-medium border border-gray-200 text-gray-500 hover:bg-gray-50">&gt;</button>` : '';
            const mid = pages.map(p => p === '...'
                ? `<span class="px-1 text-gray-400 text-xs self-center">…</span>`
                : `<button onclick="goPage(${p})" class="min-w-[28px] h-7 px-1 text-xs rounded-md font-medium transition-colors ${p===cur?'bg-emerald-500 text-white':'border border-gray-200 text-gray-500 hover:bg-gray-50'}">${p}</button>`
            ).join('');
            return prev + mid + next;
        }
        document.getElementById('paginBtns').innerHTML = paginBtnHtml(totalPages, currentPage);
    } else {
        pw.classList.add('hidden');
    }
}

function buildRow(row) {
    const key        = rowKey(row);
    const depth      = row.depth || 0;
    const checked    = !!selectedMap[key];
    const indent     = depth * 24;
    const isExpanded = !!expandedMap[key];
    const displayName = `<span class="font-${depth === 0 ? 'semibold' : 'medium'} text-gray-800 leading-snug">${escH(row.nama)} di ${escH(row.nama_wilayah)}</span>`;

    let detailBtn = `<span class="text-gray-300 text-xs">—</span>`;
    if (row.has_children) {
        detailBtn = `<button type="button"
            onclick="toggleExpand('${key}')"
            title="${isExpanded ? 'Sembunyikan turunan' : 'Tampilkan 1 level wilayah di bawah'}"
            class="inline-flex items-center justify-center w-7 h-7 rounded border font-bold text-xs select-none transition-colors
                   ${isExpanded
                       ? 'bg-emerald-100 border-emerald-300 text-emerald-700 hover:bg-emerald-200'
                       : 'bg-white border-gray-300 text-gray-600 hover:border-emerald-400 hover:text-emerald-600'}">
            ${isExpanded ? '<i class="fa-solid fa-angle-left"></i>' : '<i class="fa-solid fa-angle-down"></i>'}
        </button>`;
    }

    const rowBg = depth === 0
        ? 'hover:bg-emerald-50'
        : (depth === 1 ? 'bg-sky-50/50 hover:bg-sky-100/60' : 'bg-violet-50/40 hover:bg-violet-100/50');

    const borderStyle = depth > 0
        ? `border-left: 3px solid ${depth === 1 ? '#6ee7b7' : '#c4b5fd'};` : '';

    // BARU
    return `<tr class="${rowBg} transition-colors">
        <td class="py-3 pr-2" style="padding-left:${8 + indent}px; ${borderStyle}">
            <input type="checkbox" class="row-chk rounded border-gray-300 cursor-pointer"
                value="${escH(key)}"
                onchange="onRowCheck(this, ${row.metadata_id}, ${row.location_id}, ${depth})"
                ${checked ? 'checked' : ''}>
        </td>
        <td class="px-4 py-3 text-xs min-w-0 break-words" style="${depth > 0 ? 'padding-left:' + (16 + indent) + 'px' : ''}">
            ${depth > 0 ? '<span class="text-gray-400 mr-1">↳</span>' : ''}
            ${displayName}
            ${row.frekuensi_penerbitan ? `<span class="ml-1 text-gray-400 font-normal">(${escH(row.frekuensi_penerbitan)})</span>` : ''}
        </td>
        <td class="px-3 py-3 text-center">
            <button type="button"
                    onclick="openMetadataModal(${row.metadata_id})"
                    title="Lihat detail metadata"
                    class="inline-flex items-center justify-center bg-transparent border-0 p-0 cursor-pointer">
                <i class="fas fa-circle-info text-sky-500 hover:text-sky-600 transition-colors"></i>
            </button>
        </td>
        <td class="px-3 py-3 text-center">${detailBtn}</td>
    </tr>`;
}

// ═══════════════════════════════════════════════════════════════
// EXPAND / COLLAPSE — hanya 1 level langsung di bawah
// ═══════════════════════════════════════════════════════════════

async function toggleExpand(key) {
    if (expandedMap[key]) {
        // Collapse seluruh turunan nested
        collapseKey(key);
        renderTable();
        return;
    }

    const parts      = key.split('_');
    const metadataId = parseInt(parts[0]);
    const locationId = parseInt(parts[1]);
    const depth      = parseInt(parts[2] || '0');

    const parentRow = findRowByKey(key);

    try {
        // Fetch HANYA 1 level langsung di bawah locationId
        const r = await fetch(`${URL_CHILDREN}?metadata_id=${metadataId}&location_id=${locationId}`);
        const d = await r.json();

        if (!d.children || !d.children.length) {
            // Tandai row tidak punya anak agar tombol v disembunyikan
            if (parentRow) parentRow.has_children = false;
            renderTable();
            return;
        }

        // Anak hanya 1 level di bawah (server sudah mengembalikan direct children saja)
        expandedMap[key] = d.children.map(c => ({
            metadata_id:           metadataId,
            nama:                  parentRow ? parentRow.nama : '',
            klasifikasi:           parentRow ? parentRow.klasifikasi : '',
            satuan_data:           parentRow ? parentRow.satuan_data : '',
            frekuensi_penerbitan:  parentRow ? parentRow.frekuensi_penerbitan : '',
            location_id:           c.location_id,
            nama_wilayah:          c.nama_wilayah,
            has_children:          c.has_children,
            depth:                 depth + 1,
        }));

        renderTable();
    } catch (e) {
        console.error('Expand error:', e);
    }
}

function collapseKey(key) {
    if (expandedMap[key]) {
        expandedMap[key].forEach(child => collapseKey(rowKey(child)));
        delete expandedMap[key];
    }
}

function findRowByKey(key) {
    // Cari di sortedRows (depth 0)
    for (const row of sortedRows) {
        if (rowKey(row) === key) return row;
    }
    // Cari di expandedMap
    for (const children of Object.values(expandedMap)) {
        for (const child of children) {
            if (rowKey(child) === key) return child;
        }
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
    } else {
        delete selectedMap[key];
    }
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
            if (byKlas) {
                const k = (a.klasifikasi || '').localeCompare(b.klasifikasi || '');
                if (k !== 0) return k;
            }
            if (byWil) {
                const w = (a.nama_wilayah || '').localeCompare(b.nama_wilayah || '');
                if (w !== 0) return w;
            }
            return a.nama.localeCompare(b.nama);
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

let templateSaveInProgress = false;

function setTemplateSaveState(isSaving) {
    templateSaveInProgress = isSaving;
    const saveButton = document.getElementById('btnSaveTemplate');
    const cancelButton = document.getElementById('btnCancelSaveTemplate');
    if (saveButton) {
        saveButton.disabled = isSaving;
        saveButton.classList.toggle('opacity-60', isSaving);
        saveButton.classList.toggle('cursor-not-allowed', isSaving);
        saveButton.innerHTML = isSaving
            ? '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...'
            : '<i class="fas fa-save"></i> Simpan';
    }
    if (cancelButton) cancelButton.disabled = isSaving;
}

function openSaveModal() {
    if (templateSaveInProgress) return;
    const count = Object.keys(selectedMap).length;
    if (!count) { alert('Pilih minimal 1 metadata untuk disimpan.'); return; }
    document.getElementById('modalMetaCount').textContent = count;
    document.getElementById('inputNama').value = '';
    document.getElementById('errNama').classList.add('hidden');
    document.getElementById('modalSave').style.display = 'flex';
    setTimeout(() => document.getElementById('inputNama').focus(), 100);
}

function closeModal() {
    if (templateSaveInProgress) return;
    document.getElementById('modalSave').style.display = 'none';
}

async function submitTemplate() {
    if (templateSaveInProgress) return;

    const nama = document.getElementById('inputNama').value.trim();
    if (!nama) { document.getElementById('errNama').classList.remove('hidden'); return; }
 
    const count = Object.keys(selectedMap).length;
    if (!count) { alert('Pilih minimal 1 metadata untuk disimpan.'); return; }
 
    // ── Bangun peta lokasi per metadata dari selectedMap ─────────────────────
    // Tiap baris selectedMap punya: { metadata_id, location_id, ... }
    const metaSet    = new Set(Object.values(selectedMap).map(r => r.metadata_id));
    const metaLocMap = {};
 
    Object.values(selectedMap).forEach(row => {
        const mid = String(row.metadata_id);
        const lid = row.location_id;
        if (!metaLocMap[mid]) metaLocMap[mid] = [];
        if (lid && !metaLocMap[mid].includes(lid)) {
            metaLocMap[mid].push(lid);
        }
    });
    // Pastikan semua metadata terdaftar
    metaSet.forEach(mid => {
        if (!metaLocMap[String(mid)]) metaLocMap[String(mid)] = [];
    });
 
    setTemplateSaveState(true);
    try {
        const r = await fetch('{{ route("template.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                nama_tampilan: nama,
                jenis_template: 'wilayah',
                metadata_ids: [...metaSet],
                metadata_location_ids: JSON.stringify(metaLocMap),
            }),
        });
        const d = await r.json();
        if (!r.ok || !d.success) {
            const validationMessage = Object.values(d.errors || {}).flat()[0];
            throw new Error(validationMessage || d.message || 'Gagal menyimpan template.');
        }

        if (d.storage === 'local') {
            const existing = JSON.parse(localStorage.getItem('guest_templates') || '[]');
            if (existing.length >= 2) {
                setTemplateSaveState(false);
                alert('Anda telah mencapai batas maksimal 2 template untuk pengguna guest. Login untuk menyimpan lebih banyak template.');
                return;
            }
            d.template_data._local_id = 'local_' + Date.now();
            existing.push(d.template_data);
            localStorage.setItem('guest_templates', JSON.stringify(existing));
            alert(`Template "${nama}" disimpan di browser.\n(Login untuk menyimpan ke server)`);
        }

        closeModal();
        window.location.href = d.redirect;
    } catch (e) {
        setTemplateSaveState(false);
        alert('Gagal menyimpan: ' + e.message);
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

// Tutup dropdown saat klik di luar
document.addEventListener('click', e => {
    LEVELS.forEach(level => {
        const wrap = document.getElementById('wrap' + cap(level));
        const drop = document.getElementById('drop' + cap(level));
        if (wrap && drop && !wrap.contains(e.target)) drop.classList.add('hidden');
    });
    if (e.target === document.getElementById('modalSave')) closeModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>
@endsection