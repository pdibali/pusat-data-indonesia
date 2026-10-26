@extends('layouts.main')

@section('content')

<style>
    /* ── Tab aksen violet ── */
    .tab-active-violet { border-color:#8b5cf6 !important; color:#7c3aed !important; }
    /* ── Indentasi expand ── */
    .child-row-1 { border-left: 3px solid #a7f3d0; }
    .child-row-2 { border-left: 3px solid #c4b5fd; }
</style>

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('template.create') }}" class="hover:text-sky-500 transition-colors">Buat Template</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Template Klasifikasi</span>
    </div>

    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Buat Template Klasifikasi</h1>
            <p class="text-sm text-gray-400 mt-1">Pilih klasifikasi data dan wilayah yang ingin ditampilkan</p>
        </div>
        <span class="hidden sm:block px-3 py-1.5 bg-violet-50 text-violet-600 border border-violet-100 text-xs font-semibold rounded-full">
            <i class="fas fa-tags mr-1"></i> Jenis: Klasifikasi
        </span>
    </div>

    {{-- ═══ SECTION 1 ═══ --}}
    <div class="p-5 border border-gray-200 rounded-xl mb-2 bg-gray-50/40">
        <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-violet-500 text-white text-xs font-bold">1</span>
            Pilih Klasifikasi &amp; Wilayah
        </h2>

        {{-- KLASIFIKASI --}}
        <div class="mb-4">
            <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">
                Klasifikasi <span class="text-red-500">*</span>
            </label>

            <div class="relative" id="wrapKlasifikasi">
                <input
                    type="text"
                    id="inputKlasifikasi"
                    placeholder="Cari klasifikasi..."
                    autocomplete="off"
                    oninput="onKlasifikasiInput()"
                    onfocus="onKlasifikasiFocus()"
                    class="w-full border border-gray-300 rounded-lg pl-7 pr-7 py-2 text-sm
                        focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white"
                >

                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>

                <button
                    type="button"
                    id="clearKlasifikasi"
                    onclick="clearKlasifikasi()"
                    class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm leading-none"
                >
                    ×
                </button>

                <div
                    id="dropKlasifikasi"
                    class="hidden absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-60 overflow-y-auto"
                ></div>
            </div>

            <input type="hidden" id="valKlasifikasi" name="klasifikasi">
        </div>

        {{-- WILAYAH cascade — native <select> --}}
        <div class="mb-4">
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
            <div id="k_selectedWilayahBadge"
                 class="hidden mt-3 flex items-center gap-2.5 p-2.5 bg-violet-50 border border-violet-200 rounded-lg w-fit">
                <i class="fas fa-map-marker-alt text-violet-500 text-xs"></i>
                <span class="text-xs text-violet-700 font-semibold" id="k_badgeNama">—</span>
                <span class="text-xs text-violet-500" id="k_badgeLevel">—</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" id="btnPilih" onclick="loadKlasifikasiPreview()" disabled
                class="px-5 py-2.5 bg-violet-500 text-white text-sm font-semibold rounded-lg
                       shadow-md flex items-center gap-2 transition-all
                       disabled:opacity-40 disabled:cursor-not-allowed
                       enabled:hover:bg-violet-600 active:scale-95">
                <i class="fas fa-search"></i> Pilih &amp; Tampilkan
            </button>
            <p class="text-xs text-gray-400" id="pilihHint">Pilih klasifikasi terlebih dahulu</p>
        </div>
    </div>

    {{-- ═══ SECTION 2 ═══ --}}
    <div id="sectionResult" class="hidden mt-6">

        {{-- TAB FREKUENSI --}}
        <div class="border-b border-gray-200">
            <div class="flex gap-0 overflow-x-auto">
                @foreach(['dekade' => 'Dekade', 'tahunan' => 'Tahunan', 'semester' => 'Semester', 'kuartal' => 'Kuartal', 'bulanan' => 'Bulanan'] as $key => $label)
                    <button type="button"
                        id="tab-{{ $key }}"
                        onclick="switchTab('{{ $key }}')"
                        class="shrink-0 px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors
                               border-transparent text-gray-400 cursor-not-allowed"
                        disabled>
                        {{ $label }}
                        <span id="tab-count-{{ $key }}"
                              class="ml-1.5 text-xs font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400">0</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- INFO BAR --}}
        <div class="flex items-center justify-between my-3">
            <div>
                <h3 class="font-bold text-gray-700 text-sm">Hasil Metadata</h3>
                <p class="text-xs text-gray-400 mt-0.5" id="resultDesc">—</p>
            </div>
            <span id="totalFound"
                  class="hidden sm:block text-xs bg-violet-50 text-violet-600 border border-violet-100 px-2.5 py-1 rounded-full font-semibold">
                0 baris
            </span>
        </div>

        {{-- TABEL --}}
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full table-fixed text-sm text-left">

                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        {{-- Checkbox --}}
                        <th class="w-12 px-4 py-3">
                            <input
                                type="checkbox"
                                id="checkAllPreview"
                                onchange="toggleAll(this)"
                                class="rounded border-gray-300 cursor-pointer">
                        </th>

                        {{-- Metadata --}}
                        <th class="px-4 py-3 font-semibold text-gray-600">
                            Metadata
                        </th>

                        {{-- Info --}}
                        <th class="w-16 px-3 py-3 text-center font-semibold text-gray-600">
                            Info
                        </th>

                        {{-- Detail --}}
                        <th class="w-20 px-3 py-3 text-center font-semibold text-gray-600">
                            <span class="block sm:hidden">Det.</span>
                            <span class="hidden sm:block">Detail</span>
                        </th>
                    </tr>
                </thead>

                <tbody id="previewTableBody"
                    class="divide-y divide-gray-100 bg-white">

                    <tr>
                        <td colspan="4"
                            class="px-4 py-10 text-center text-gray-400">

                            <i class="fas fa-table text-gray-200 text-3xl block mb-2"></i>

                            Belum ada data —
                            klik
                            <strong class="text-gray-500">
                                "Pilih & Tampilkan"
                            </strong>

                        </td>
                    </tr>

                </tbody>

            </table>
        </div>

        {{-- PAGINATION --}}
        <div id="previewPagination" class="hidden mt-3 flex items-center justify-between text-xs text-gray-500">
            <span id="paginationInfo" class="text-gray-400 hidden sm:block"></span>
            <div class="flex gap-1 mx-auto sm:mx-0" id="paginationButtons"></div>
        </div>

        {{-- SELEKSI BAR --}}
        <div id="selectionBarPreview"
             class="mt-3 hidden items-center justify-between px-4 py-2.5 rounded-lg"
             style="background:#f5f3ff; border:1px solid #ddd6fe;">
            <p class="font-medium text-violet-700 flex items-center gap-2 text-sm">
                <i class="fas fa-check-square text-violet-500"></i>
                <span id="selectionCountPreview">0 metadata dipilih</span>
            </p>
            <button onclick="clearAllPreviewSelection()"
                    class="text-xs font-medium text-violet-500 hover:underline flex items-center gap-1">
                <i class="fas fa-times"></i> Batalkan Pilihan
            </button>
        </div>

        {{-- PENGATURAN URUTAN --}}
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
<div id="modalSaveTemplate"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,0.45);display:none;
            align-items:center;justify-content:center;padding:1rem;">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 rounded-t-xl" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-bold text-base flex items-center gap-2">
                    <i class="fas fa-bookmark"></i> Simpan Template
                </h3>
                <button onclick="closeSaveModal()" class="text-white/70 hover:text-white text-2xl leading-none">×</button>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Template <span class="text-red-500">*</span>
                </label>
                <input type="text" id="inputNamaTemplate" placeholder="cth: Data Ekonomi Bali 2020–2024"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-violet-400">
                <p id="errorNamaTemplate" class="hidden mt-1 text-xs text-red-500">
                    <i class="fas fa-exclamation-circle mr-1"></i> Nama template wajib diisi.
                </p>
            </div>
            <div class="p-3 bg-violet-50 rounded-lg border border-violet-100 text-xs text-violet-700">
                <i class="fas fa-list-check mr-1"></i>
                <span id="saveMetadataCount">0</span> metadata akan disimpan.
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
                onclick="closeSaveModal()"
                id="btnCancelSaveTemplate"
                class="w-full sm:w-auto
                    border border-gray-300
                    text-gray-500 hover:bg-gray-100
                    px-4 py-2 rounded-lg text-sm
                    transition-colors">
                Batal
            </button>

            <button
                onclick="submitSaveTemplate()"
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

<script>
// ─────────────────────────────────────────────────────────────
// CONFIG
// ─────────────────────────────────────────────────────────────
const FETCH_KLASIFIKASI_URL = '{{ route("template.fetch_klasifikasi") }}';
const URL_CHILDREN          = '{{ route("template.child_locations") }}';
const CSRF                  = '{{ csrf_token() }}';

const FREQ_KEYS   = ['dekade','tahunan','semester','kuartal','bulanan'];
const FREQ_LABELS = { dekade:'Dekade', tahunan:'Tahunan', semester:'Semester', kuartal:'Kuartal', bulanan:'Bulanan' };

// ─────────────────────────────────────────────
// SEARCHABLE KLASIFIKASI DROPDOWN
// ─────────────────────────────────────────────

const klasifikasiList = @json($klasifikasiList->values());

function renderKlasifikasiDropdown(items) {
    const drop = document.getElementById('dropKlasifikasi');

    if (!items.length) {
        drop.innerHTML = `
            <div class="px-3 py-2 text-xs text-gray-400">
                Tidak ada klasifikasi ditemukan
            </div>
        `;
        drop.classList.remove('hidden');
        return;
    }

    drop.innerHTML = items.map(item => `
        <button
            type="button"
            onclick="selectKlasifikasi('${item.replace(/'/g, "\\'")}')"
            class="w-full text-left px-3 py-2 text-sm hover:bg-violet-50
                   transition-colors border-b border-gray-100 last:border-0"
        >
            ${item}
        </button>
    `).join('');

    drop.classList.remove('hidden');
}

function onKlasifikasiInput() {
    const keyword = document.getElementById('inputKlasifikasi')
        .value
        .toLowerCase()
        .trim();

    const filtered = klasifikasiList.filter(item =>
        item.toLowerCase().includes(keyword)
    );

    renderKlasifikasiDropdown(filtered);

    document.getElementById('valKlasifikasi').value = '';
    document.getElementById('clearKlasifikasi').classList.remove('hidden');

    document.getElementById('btnPilih').disabled = true;
    document.getElementById('pilihHint').textContent =
        'Pilih klasifikasi terlebih dahulu';
}

function onKlasifikasiFocus() {
    renderKlasifikasiDropdown(klasifikasiList);
}

function selectKlasifikasi(value) {
    document.getElementById('inputKlasifikasi').value = value;
    document.getElementById('valKlasifikasi').value = value;

    document.getElementById('dropKlasifikasi').classList.add('hidden');
    document.getElementById('clearKlasifikasi').classList.remove('hidden');

    document.getElementById('btnPilih').disabled = false;
    document.getElementById('pilihHint').textContent =
        'Wilayah bersifat opsional';

    updatePilihBtn();
}

function clearKlasifikasi() {
    document.getElementById('inputKlasifikasi').value = '';
    document.getElementById('valKlasifikasi').value = '';

    document.getElementById('dropKlasifikasi').classList.add('hidden');
    document.getElementById('clearKlasifikasi').classList.add('hidden');

    document.getElementById('btnPilih').disabled = true;
    document.getElementById('pilihHint').textContent =
        'Pilih klasifikasi terlebih dahulu';
    
    updatePilihBtn(); 
}

document.addEventListener('click', function (e) {
    const wrap = document.getElementById('wrapKlasifikasi');

    if (!wrap.contains(e.target)) {
        document.getElementById('dropKlasifikasi')
            .classList.add('hidden');
    }
});

// ─────────────────────────────────────────────────────────────
// CASCADE WILAYAH — native <select>, data embedded tanpa AJAX
// ─────────────────────────────────────────────────────────────
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

const K_LEVELS = ['provinsi','kabupaten','kecamatan','desa'];
const K_LEVEL_LABEL = {
    provinsi:'Provinsi', kabupaten:'Kabupaten/Kota',
    kecamatan:'Kecamatan', desa:'Desa/Kelurahan',
};
const K_URLS = {
    kabupaten: '{{ route("template.get_kabupaten") }}',
    kecamatan: '{{ route("template.get_kecamatan_wil") }}',
    desa:      '{{ route("template.get_desa_wil") }}',
};
const kSelLoc = { provinsi:null, kabupaten:null, kecamatan:null, desa:null };
const kCaches = { kabupaten:{}, kecamatan:{}, desa:{} };
function kCap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

function onWilFocus(level) {
    const drop = document.getElementById('drop' + kCap(level));
    if (!drop.classList.contains('hidden')) return;
    showDropForLevel(level, '');
}

function onWilInput(level) {
    const q = document.getElementById('input' + kCap(level)).value.trim();
    showDropForLevel(level, q);
}

function getLocationsForLevel(level) {
    if (level === 'provinsi') return LOC_PROVINSI;
    if (level === 'kabupaten') {
        if (!kSelLoc.provinsi) return [];
        return idxKab[kSelLoc.provinsi.id.slice(0, 2)] || [];
    }
    if (level === 'kecamatan') {
        if (!kSelLoc.kabupaten) return [];
        return idxKec[kSelLoc.kabupaten.id.slice(0, 4)] || [];
    }
    if (level === 'desa') {
        if (!kSelLoc.kecamatan) return [];
        return idxDes[kSelLoc.kecamatan.id.slice(0, 6)] || [];
    }
    return [];
}

function showDropForLevel(level, q) {
    const drop = document.getElementById('drop' + kCap(level));
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
        const isSel = kSelLoc[level] && kSelLoc[level].id === x.id;
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
    kSelLoc[level] = { id, nama };
    document.getElementById('input' + kCap(level)).value = nama;
    document.getElementById('val'   + kCap(level)).value = id;
    document.getElementById('clear' + kCap(level)).classList.remove('hidden');
    document.getElementById('drop'  + kCap(level)).classList.add('hidden');

    const idx = K_LEVELS.indexOf(level);
    K_LEVELS.slice(idx + 1).forEach(l => {
        kSelLoc[l] = null;
        document.getElementById('input' + kCap(l)).value = '';
        document.getElementById('val'   + kCap(l)).value = '';
        document.getElementById('clear' + kCap(l)).classList.add('hidden');
        document.getElementById('drop'  + kCap(l)).classList.add('hidden');
    });

    const next = K_LEVELS[idx + 1];
    if (next) {
        const ni = document.getElementById('input' + kCap(next));
        ni.disabled = false;
        ni.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        ni.classList.add('bg-white', 'text-gray-700');
        ni.placeholder = 'Cari ' + K_LEVEL_LABEL[next] + '...';
    }

    kUpdateBadge();
    updatePilihBtn();
}

function clearLevel(level) {
    const idx = K_LEVELS.indexOf(level);
    K_LEVELS.slice(idx).forEach(l => {
        kSelLoc[l] = null;
        document.getElementById('input' + kCap(l)).value = '';
        document.getElementById('val'   + kCap(l)).value = '';
        document.getElementById('clear' + kCap(l)).classList.add('hidden');
        document.getElementById('drop'  + kCap(l)).classList.add('hidden');
        if (l !== 'provinsi') {
            const ni = document.getElementById('input' + kCap(l));
            ni.disabled = true;
            ni.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            ni.classList.remove('bg-white', 'text-gray-700');
            ni.placeholder = 'Pilih ' + K_LEVEL_LABEL[K_LEVELS[K_LEVELS.indexOf(l) - 1]] + ' dulu...';
        }
    });
    kUpdateBadge();
    updatePilihBtn();
}

function updatePilihBtn() {
    const klasifikasi = document.getElementById('valKlasifikasi').value;

    document.getElementById('btnPilih').disabled = !klasifikasi;
    document.getElementById('pilihHint').textContent = klasifikasi
        ? 'Wilayah bersifat opsional'
        : 'Pilih klasifikasi terlebih dahulu';
}

function kUpdateBadge() {
    let deepest = null;
    for (let i = K_LEVELS.length-1; i >= 0; i--) {
        if (kSelLoc[K_LEVELS[i]]) { deepest = { level:K_LEVELS[i], ...kSelLoc[K_LEVELS[i]] }; break; }
    }
    const badge = document.getElementById('k_selectedWilayahBadge');
    if (deepest) {
        badge.classList.remove('hidden');
        document.getElementById('k_badgeNama').textContent  = deepest.nama;
        document.getElementById('k_badgeLevel').textContent = '(' + K_LEVEL_LABEL[deepest.level] + ')';
    } else { badge.classList.add('hidden'); }
}

function kGetDeepestLocId() {
    for (let i = K_LEVELS.length-1; i >= 0; i--) {
        if (kSelLoc[K_LEVELS[i]]) return kSelLoc[K_LEVELS[i]].id;
    }
    return null;
}


// ─────────────────────────────────────────────────────────────
// STATE TABEL
// ─────────────────────────────────────────────────────────────
let allGrouped          = {};   // { dekade:[], tahunan:[], ... }
let activeTab           = '';
let sortedRows          = [];   // rows tab aktif setelah urutan
let expandedMap         = {};   // key → [childRows]
let selectedPreviewItems = {};
const PAGE_SIZE         = 15;
let currentPage         = 1;

// ─────────────────────────────────────────────────────────────
// LOAD DATA
// ─────────────────────────────────────────────────────────────
async function loadKlasifikasiPreview() {
    const klasifikasi = document.getElementById('valKlasifikasi').value;
    if (!klasifikasi) { alert('Pilih klasifikasi terlebih dahulu.'); return; }

    const body = new URLSearchParams();
    body.append('_token', CSRF);
    body.append('klasifikasi', klasifikasi);
    const locId = kGetDeepestLocId();
    if (locId) body.append('location_ids[]', locId);

    const btn = document.getElementById('btnPilih');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat...';

    try {
        const r = await fetch(FETCH_KLASIFIKASI_URL, { method:'POST', body });
        const d = await r.json();
        if (!d.success) { alert('Gagal memuat preview.'); return; }
        allGrouped          = d.grouped;
        expandedMap         = {};
        selectedPreviewItems = {};
        currentPage         = 1;
        activateTabs(allGrouped);
        document.getElementById('sectionResult').classList.remove('hidden');

        // Deskripsi wilayah
        let locName = 'Semua Wilayah';
        for (let i = K_LEVELS.length-1; i >= 0; i--) {
            if (kSelLoc[K_LEVELS[i]]) { locName = kSelLoc[K_LEVELS[i]].nama; break; }
        }
        document.getElementById('resultDesc').textContent =
            `Klasifikasi: ${klasifikasi} · Wilayah: ${locName}`;
    } catch(e) { alert('Terjadi kesalahan: ' + e.message); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search mr-2"></i> Pilih &amp; Tampilkan';
    }
}

// ─────────────────────────────────────────────────────────────
// TABS
// ─────────────────────────────────────────────────────────────
function activateTabs(grouped) {
    let firstActive = '';
    FREQ_KEYS.forEach(freq => {
        const count  = (grouped[freq] || []).length;
        const btn    = document.getElementById('tab-' + freq);
        const badge  = document.getElementById('tab-count-' + freq);
        badge.textContent = count;
        if (count > 0) {
            btn.disabled = false;
            btn.classList.remove('cursor-not-allowed','text-gray-400');
            btn.classList.add('cursor-pointer','text-gray-600','hover:text-gray-800');
            badge.classList.remove('bg-gray-100','text-gray-400');
            badge.classList.add('bg-violet-100','text-violet-600');
            if (!firstActive) firstActive = freq;
        } else {
            btn.disabled = true;
            btn.classList.add('cursor-not-allowed','text-gray-400');
            badge.classList.add('bg-gray-100','text-gray-400');
            badge.classList.remove('bg-violet-100','text-violet-600');
        }
    });
    if (firstActive) {
        switchTab(firstActive);
    } else {
        document.getElementById('totalFound').textContent = '0 baris';
    }
}

function switchTab(freq) {
    activeTab = freq;
    FREQ_KEYS.forEach(f => {
        const btn = document.getElementById('tab-' + f);
        if (f === freq) {
            btn.classList.add('border-violet-500','text-violet-600');
            btn.classList.remove('border-transparent','text-gray-600');
        } else {
            btn.classList.remove('border-violet-500','text-violet-600');
            if (!btn.disabled) btn.classList.add('border-transparent','text-gray-600');
        }
    });

    sortedRows  = [...(allGrouped[freq] || [])];
    document.getElementById('totalFound').textContent = sortedRows.length + ' baris';
    expandedMap = {};
    currentPage = 1;
    renderTable();
}

// ─────────────────────────────────────────────────────────────
// RENDER TABEL — flat rows termasuk expand children
// ─────────────────────────────────────────────────────────────
function rowKey(row) { return `${row.metadata_id}_${row.location_id}_${row.depth||0}`; }

function buildFlat(baseRows) {
    const result = [];
    function walk(rows) {
        rows.forEach(row => {
            result.push(row);
            const k = rowKey(row);
            if (expandedMap[k]) walk(expandedMap[k]);
        });
    }
    walk(baseRows);
    return result;
}

function renderTable() {
    const tbody = document.getElementById('previewTableBody');
    const flat  = buildFlat(sortedRows);
    const total = flat.length;

    if (!total) {
        tbody.innerHTML = `<tr><td colspan="4"
            class="px-4 py-10 text-center text-gray-400 text-sm">
            <i class="fas fa-inbox text-gray-200 text-3xl block mb-2"></i>
            Tidak ada metadata dengan frekuensi <strong>${FREQ_LABELS[activeTab]||''}</strong>
        </td></tr>`;
        document.getElementById('previewPagination').classList.add('hidden');
        return;
    }

    const start = (currentPage - 1) * PAGE_SIZE;
    const end   = Math.min(start + PAGE_SIZE, total);
    const paged = flat.slice(start, end);

    tbody.innerHTML = paged.map(row => buildRow(row)).join('');
    const visibleRows = flat.slice(start, end);

    const allChecked =
        visibleRows.length > 0 &&
        visibleRows.every(row =>
            selectedPreviewItems[rowKey(row)]
        );

    const masterCheckbox = document.getElementById('checkAllPreview');

    if (masterCheckbox) {
        masterCheckbox.checked = allChecked;
    }

    // Pagination
    const totalPages = Math.ceil(total / PAGE_SIZE);
    const pw = document.getElementById('previewPagination');
    if (totalPages > 1) {
        pw.classList.remove('hidden');
        document.getElementById('paginationInfo').textContent =
            `Menampilkan ${start+1}–${end} dari ${total}`;
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
                : `<button onclick="goPage(${p})" class="w-7 h-7 text-xs rounded-md font-medium transition-colors ${p===cur?'bg-violet-500 text-white':'border border-gray-200 text-gray-500 hover:bg-gray-50'}">${p}</button>`
            ).join('');
            return prev + mid + next;
        }

        document.getElementById('paginationButtons').innerHTML = buildPaginBtns(totalPages, currentPage);
    } else { pw.classList.add('hidden'); }
}

function buildRow(row) {
    const key      = rowKey(row);
    const depth    = row.depth || 0;
    const checked  = !!selectedPreviewItems[key];
    const indent   = depth * 20;
    const isExpanded = !!expandedMap[key];

    const locLabel = row.nama_wilayah || 'Semua Wilayah';
    const freqBadge = row.frekuensi_penerbitan
        ? `<span class="text-gray-400 font-normal ml-1">(${escH(row.frekuensi_penerbitan)})</span>` : '';

    let detailBtn = `<span class="text-gray-300 text-sm">—</span>`;
    if (row.has_children) {
        detailBtn = `<button type="button"
            onclick="toggleExpand('${key}', ${row.metadata_id}, ${row.location_id}, ${depth})"
            title="${isExpanded ? 'Sembunyikan turunan' : 'Tampilkan wilayah di bawahnya'}"
            class="inline-flex items-center justify-center w-7 h-7 rounded border font-bold text-xs
                   select-none transition-colors
                   ${isExpanded
                       ? 'bg-violet-100 border-violet-300 text-violet-700 hover:bg-violet-200'
                       : 'bg-white border-gray-300 text-gray-600 hover:border-violet-400 hover:text-violet-600'}">
            <i class="fas ${isExpanded ? 'fa-angle-left' : 'fa-angle-down'} text-xs"></i>
        </button>`;
    }

    const rowBg = depth === 0
        ? 'hover:bg-violet-50/40'
        : depth === 1 ? 'bg-sky-50/30 hover:bg-sky-50/60'
        : 'bg-violet-50/30 hover:bg-violet-50/60';

    const borderStyle = depth > 0
        ? `border-left: 3px solid ${depth===1 ? '#a7f3d0' : '#c4b5fd'};` : '';

    return `<tr class="${rowBg} transition-colors">
        <td class="py-3 pr-2" style="padding-left:${12 + indent}px; ${borderStyle}">
            <input type="checkbox" class="preview-check rounded border-gray-300 cursor-pointer"
                value="${escH(key)}"
                data-meta-id="${row.metadata_id}"
                data-loc-id="${row.location_id}"
                data-meta-nama="${escH(row.nama||'')}"
                data-loc-nama="${escH(locLabel)}"
                onchange="onRowCheck(this)"
                ${checked ? 'checked' : ''}>
        </td>
        <td class="px-2 py-3 text-xs w-1/2 min-w-0 break-words" style="${depth > 0 ? 'padding-left:' + (8+indent) + 'px' : 'padding-left:8px'}">
            ${depth > 0 ? '<span class="text-gray-400 mr-1">↳</span>' : ''}
            <span class="${depth===0 ? 'font-semibold' : 'font-medium'} text-gray-800 leading-snug">
                ${escH(row.nama||'')} di ${escH(locLabel)}
            </span>
            ${freqBadge}
        </td>
        <td class="px-2 py-3 text-center">
            <button type="button"
                    onclick="openMetadataModal(${row.metadata_id})"
                    title="Lihat detail metadata"
                    class="inline-flex items-center justify-center bg-transparent border-0 p-0 cursor-pointer">
                <i class="fas fa-circle-info text-sky-500 hover:text-sky-600 transition-colors"></i>
            </button>
        </td>
        <td class="px-2 py-3 text-center">${detailBtn}</td>
    </tr>`;
}

function goPage(p) { currentPage = p; renderTable(); }

// ─────────────────────────────────────────────────────────────
// EXPAND / COLLAPSE TURUNAN WILAYAH
// ─────────────────────────────────────────────────────────────
async function toggleExpand(key, metadataId, locationId, depth) {
    if (expandedMap[key]) {
        collapseKey(key);
        renderTable();
        return;
    }

    // Fetch direct children
    try {
        const r = await fetch(`${URL_CHILDREN}?metadata_id=${metadataId}&location_id=${locationId}`);
        const d = await r.json();
        if (!d.children || !d.children.length) {
            // Tandai tidak punya anak
            const parentRow = findRow(key);
            if (parentRow) parentRow.has_children = false;
            renderTable();
            return;
        }
        const parentRow = findRow(key);
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
    if (!expandedMap[key]) return;
    expandedMap[key].forEach(child => collapseKey(rowKey(child)));
    delete expandedMap[key];
}

function findRow(key) {
    // Cari di sortedRows
    for (const row of sortedRows) { if (rowKey(row) === key) return row; }
    // Cari di expanded children
    for (const children of Object.values(expandedMap)) {
        for (const child of children) { if (rowKey(child) === key) return child; }
    }
    return null;
}

// ─────────────────────────────────────────────────────────────
// PENGATURAN URUTAN
// ─────────────────────────────────────────────────────────────
function terapkanUrutan() {
    const byKlas = document.getElementById('chkKlasifikasi').checked;
    const byWil  = document.getElementById('chkWilayah').checked;
    const base   = allGrouped[activeTab] || [];
    sortedRows = [...base].sort((a, b) => {
        if (byKlas) {
            const k = (a.klasifikasi||'').localeCompare(b.klasifikasi||'');
            if (k !== 0) return k;
        }
        if (byWil) {
            const w = (a.nama_wilayah||'').localeCompare(b.nama_wilayah||'');
            if (w !== 0) return w;
        }
        return (a.nama||'').localeCompare(b.nama||'');
    });
    expandedMap = {};
    currentPage = 1;
    renderTable();
}

// ─────────────────────────────────────────────────────────────
// SELECTION
// ─────────────────────────────────────────────────────────────
function onRowCheck(cb) {
    const key = cb.value;
    if (cb.checked) {
        selectedPreviewItems[key] = {
            key,
            metadataId: cb.dataset.metaId,
            locationId: cb.dataset.locId,
            metaNama:   cb.dataset.metaNama,
            locNama:    cb.dataset.locNama,
        };
    } else { delete selectedPreviewItems[key]; }
    updateSelBar();
}

function toggleAll(masterCb) {
    const flat = buildFlat(sortedRows);

    if (masterCb.checked) {

        flat.forEach(row => {
            const key = rowKey(row);

            selectedPreviewItems[key] = {
                key,
                metadataId: row.metadata_id,
                locationId: row.location_id,
                metaNama: row.nama || '',
                locNama: row.nama_wilayah || ''
            };
        });

    } else {
        selectedPreviewItems = {};
    }

    renderTable();
    updateSelBar();
}

function clearAllPreviewSelection() {
    selectedPreviewItems = {};
    document.querySelectorAll('.preview-check').forEach(cb => cb.checked = false);
    const ca = document.getElementById('checkAllPreview');
    if (ca) ca.checked = false;
    updateSelBar();
}

function updateSelBar() {
    const count = Object.keys(selectedPreviewItems).length;
    const bar   = document.getElementById('selectionBarPreview');
    if (count > 0) { bar.classList.remove('hidden'); bar.style.display = 'flex'; }
    else           { bar.classList.add('hidden');    bar.style.display = 'none'; }
    document.getElementById('selectionCountPreview').textContent = count + ' metadata dipilih';
}

// ─────────────────────────────────────────────────────────────
// SAVE MODAL
// ─────────────────────────────────────────────────────────────
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
    const count = Object.keys(selectedPreviewItems).length;
    if (!count) { alert('Pilih minimal 1 metadata terlebih dahulu.'); return; }
    document.getElementById('saveMetadataCount').textContent = count;
    document.getElementById('inputNamaTemplate').value = '';
    document.getElementById('errorNamaTemplate').classList.add('hidden');
    document.getElementById('modalSaveTemplate').style.display = 'flex';
    setTimeout(() => document.getElementById('inputNamaTemplate').focus(), 100);
}

function closeSaveModal() {
    if (templateSaveInProgress) return;
    document.getElementById('modalSaveTemplate').style.display = 'none';
}

async function submitSaveTemplate() {
    if (templateSaveInProgress) return;

    const nama = document.getElementById('inputNamaTemplate').value.trim();
    if (!nama) {
        document.getElementById('errorNamaTemplate').classList.remove('hidden');
        return;
    }

    // ── Ambil data dari preview ───────────────────────────────
    const hasSelection = Object.keys(selectedPreviewItems || {}).length > 0;

    // Metadata unik
    const metaSet = new Set(
        Object.values(selectedPreviewItems).map(r => r.metadataId)
    );

    if (!metaSet.size) {
        alert('Pilih minimal 1 metadata.');
        return;
    }

    // ── Build mapping metadata → lokasi ───────────────────────
    const metaLocMap = {};

    if (hasSelection) {
        Object.values(selectedPreviewItems).forEach(row => {
            const mid = String(row.metadataId);
            const lid = row.locationId;

            if (!metaLocMap[mid]) metaLocMap[mid] = [];

            if (lid && !metaLocMap[mid].includes(lid)) {
                metaLocMap[mid].push(lid);
            }
        });

        // Pastikan semua metadata ada key-nya
        metaSet.forEach(mid => {
            if (!metaLocMap[String(mid)]) {
                metaLocMap[String(mid)] = [];
            }
        });
    }

    // ── Urutan by ─────────────────────────────────────────────
    const urutanBy = [];
    if (document.getElementById('chkKlasifikasi').checked) urutanBy.push('klasifikasi');
    if (document.getElementById('chkWilayah').checked)     urutanBy.push('wilayah');

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
                jenis_template: 'klasifikasi',
                klasifikasi: document.getElementById('valKlasifikasi').value,
                metadata_ids: [...metaSet],
                metadata_location_ids: JSON.stringify(metaLocMap),
                urutan_by: urutanBy,
            }),
        });
        const d = await r.json();
        if (!r.ok || !d.success) {
            const validationMessage = Object.values(d.errors || {}).flat()[0];
            throw new Error(validationMessage || d.message || 'Gagal menyimpan template.');
        }

        if (d.storage === 'local') {
            const existing = JSON.parse(localStorage.getItem('guest_templates') ?? '[]');
            if (existing.length >= 2) {
                setTemplateSaveState(false);
                alert('Anda telah mencapai batas maksimal 2 template untuk pengguna guest. Login untuk menyimpan lebih banyak template.');
                return;
            }
            d.template_data._local_id = 'local_' + Date.now();
            existing.push(d.template_data);
            localStorage.setItem('guest_templates', JSON.stringify(existing));
            alert(`Template "${nama}" disimpan di browser.\nLogin untuk menyimpan ke server`);
        }

        closeSaveModal();
        window.location.href = d.redirect;
    } catch (e) {
        setTemplateSaveState(false);
        alert('Gagal menyimpan: ' + e.message);
    }
}

// ─────────────────────────────────────────────────────────────
// UTILS
// ─────────────────────────────────────────────────────────────
function escH(s) {
    const d = document.createElement('div');
    d.innerText = s || '';
    return d.innerHTML;
}

document.addEventListener('click', e => {
    if (e.target === document.getElementById('modalSaveTemplate')) closeSaveModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSaveModal(); });
</script>

@endsection