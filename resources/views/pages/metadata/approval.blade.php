@extends('layouts.main')

@section('content')

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- ══════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════ --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Approval Metadata</h1>
            <p class="text-sm text-gray-400 mt-1">Verifikasi metadata sebelum dapat digunakan</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p id="current-date"></p>
            <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
        </div>
    </div>

    {{-- ALERT: session flash + feedback AJAX --}}
    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    <div id="ajaxAlert" class="hidden mt-4"></div>

    {{-- ══════════════════════════════════════════════
         STATS
    ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-3 gap-4 mt-5">
        <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4 cursor-pointer hover:border-yellow-300 transition-colors"
             onclick="switchTab(1)">
            <p class="text-xs text-yellow-500 font-semibold uppercase tracking-wide">Pending</p>
            <p class="text-2xl font-bold text-yellow-700 mt-1" id="statPending">{{ number_format($countPending, 0, ',', '.') }}</p>
            <p class="text-xs text-yellow-400 mt-1">menunggu verifikasi</p>
        </div>
        <div class="bg-green-50 border border-green-100 rounded-lg p-4 cursor-pointer hover:border-green-300 transition-colors"
             onclick="switchTab(2)">
            <p class="text-xs text-green-500 font-semibold uppercase tracking-wide">Active</p>
            <p class="text-2xl font-bold text-green-700 mt-1" id="statActive">{{ number_format($countActive, 0, ',', '.') }}</p>
            <p class="text-xs text-green-400 mt-1">metadata aktif</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-400 transition-colors"
             onclick="switchTab(3)">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Inactive</p>
            <p class="text-2xl font-bold text-gray-600 mt-1" id="statInactive">{{ number_format($countInactive, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">metadata nonaktif</p>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         TAB + SEARCH 
    ══════════════════════════════════════════════ --}}
    <div class="flex border-b border-gray-200 mt-6">
        @php
            $tabs = [
                1 => ['label' => 'Pending',  'count' => $countPending],
                2 => ['label' => 'Active',   'count' => $countActive],
                3 => ['label' => 'Inactive', 'count' => $countInactive],
            ];
            $tabBorderColor = [1 => '#f59e0b', 2 => '#22c55e', 3 => '#9ca3af'];
            $tabTextColor   = [1 => '#b45309', 2 => '#15803d', 3 => '#6b7280'];
            $tabBadgeBg     = [1 => '#fef3c7', 2 => '#dcfce7', 3 => '#f3f4f6'];
        @endphp

        @foreach($tabs as $statusVal => $tab)
            <button id="tab-btn-{{ $statusVal }}"
                    onclick="switchTab({{ $statusVal }})"
                    class="tab-btn relative px-5 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2"
                    style="{{ (int)$statusFilter === $statusVal
                        ? 'border-color:' . $tabBorderColor[$statusVal] . '; color:' . $tabTextColor[$statusVal] . ';'
                        : 'border-color:transparent; color:#9ca3af;' }}">
                {{ $tab['label'] }}
                <span class="tab-badge-{{ $statusVal }} text-xs font-bold px-1.5 py-0.5 rounded-full"
                      style="background:{{ $tabBadgeBg[$statusVal] }}; color:{{ $tabTextColor[$statusVal] }};">
                    {{ $tab['count'] }}
                </span>
            </button>
        @endforeach

        <form method="GET" id="searchForm" class="ml-auto flex items-center pb-1">
            <input type="hidden" name="status"             value="{{ $statusFilter }}">
            <input type="hidden" name="filter_klasifikasi" value="{{ request('filter_klasifikasi') }}">
            <input type="hidden" name="filter_produsen_id" value="{{ request('filter_produsen_id') }}">
            <input type="hidden" name="filter_tipe_data"   value="{{ request('filter_tipe_data') }}">
            <input type="hidden" name="filter_user"        value="{{ request('filter_user') }}">
            <input type="hidden" name="filter_date_from"   value="{{ request('filter_date_from') }}">
            <input type="hidden" name="filter_date_to"     value="{{ request('filter_date_to') }}">
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama metadata..."
                       class="border border-gray-300 rounded-md pl-8 pr-3 py-1.5 text-sm w-56
                              focus:outline-none focus:ring-2 focus:ring-sky-400">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>
            @if(request()->hasAny(['search','filter_klasifikasi','filter_produsen_id','filter_tipe_data','filter_user','filter_date_from','filter_date_to']))
                <a href="{{ route('metadata.approval', ['status' => $statusFilter]) }}"
                   class="ml-2 text-gray-400 hover:text-red-400 text-xs transition-colors" title="Reset semua filter">
                    <i class="fas fa-times-circle text-base"></i>
                </a>
            @endif
        </form>
    </div>

    {{-- ══════════════════════════════════════════════
        TOOLBAR — hanya muncul di tab Pending
    ══════════════════════════════════════════════ --}}
    @if((int)$statusFilter === 1 && $data->total() > 0)
        <div class="flex items-center justify-between mt-4 mb-1">
            <p class="text-xs text-gray-400">
                <span id="selectedCount">0</span> baris dipilih
            </p>
            <div class="flex gap-2">
                <button id="btnApproveSelected"
                        onclick="approveSelected()"
                        disabled
                        class="hidden items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                               text-white rounded-md shadow-sm transition-colors
                               disabled:opacity-40 disabled:cursor-not-allowed"
                        style="background:#22c55e;"
                        onmouseover="if(!this.disabled) this.style.background='#16a34a'"
                        onmouseout="if(!this.disabled) this.style.background='#22c55e'">
                    <i class="fas fa-check-double"></i>
                    Approve Terpilih (<span id="selectedCountBtn">0</span>)
                </button>

                <button onclick="approveAll(this)"
                        class="flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold
                               text-white rounded-md shadow-sm transition-colors"
                        style="background:#0284c7;"
                        onmouseover="this.style.background='#0369a1'"
                        onmouseout="this.style.background='#0284c7'"
                        title="Approve semua {{ number_format($data->total()) }} data Pending">
                    <i class="fas fa-check-circle"></i>
                    Approve Semua ({{ number_format($data->total()) }})
                </button>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         TABLE
    ══════════════════════════════════════════════ --}}
    <div class="mt-2 border rounded-lg">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">

                <tr>
                    @if((int)$statusFilter === 1)
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="checkAll"
                                   class="rounded border-gray-300 text-sky-600 cursor-pointer"
                                   onchange="toggleCheckAll(this)">
                        </th>
                    @else
                        <th class="px-4 py-3 font-semibold w-10">No</th>
                    @endif
                    <th class="px-4 py-3 font-semibold min-w-52">Nama</th>
                    <th class="px-4 py-3 font-semibold min-w-32">Klasifikasi</th>
                    <th class="px-4 py-3 font-semibold min-w-36">Sumber Metadata Pertama</th>
                    <th class="px-4 py-3 font-semibold min-w-24">Tipe Data</th>
                    <th class="px-4 py-3 font-semibold min-w-28">Diinput Oleh</th>
                    <th class="px-4 py-3 font-semibold text-center min-w-24">Status</th>
                    <th class="px-4 py-3 font-semibold text-center min-w-36">Aksi</th>
                </tr>

                
                <tr class="bg-white border-t border-gray-100">
                    <td class="px-2 py-1.5"></td>

                    <td class="px-2 py-1.5">
                        <input type="text" id="filterNama"
                               value="{{ request('filter_nama') }}"
                               placeholder="Filter nama..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                      focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    <td class="px-2 py-1.5">
                        <select id="filterKlasifikasi"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($klasifikasiList as $k)
                                <option value="{{ $k->klasifikasi_id }}"
                                    {{ (string)request('filter_klasifikasi') === (string)$k->klasifikasi_id ? 'selected' : '' }}>
                                    {{ $k->nama_klasifikasi }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td class="px-2 py-1.5">
                        <select id="filterProdusen"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($produsenList as $p)
                                <option value="{{ $p->produsen_id }}"
                                    {{ (string)request('filter_produsen_id') === (string)$p->produsen_id ? 'selected' : '' }}>
                                    {{ $p->nama_produsen }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td class="px-2 py-1.5">
                        <select id="filterTipeData"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($tipeDataList as $t)
                                <option value="{{ $t }}"
                                    {{ request('filter_tipe_data') === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td class="px-2 py-1.5">
                        <input type="text" id="filterUser"
                               value="{{ request('filter_user') }}"
                               placeholder="Filter user..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                      focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    <td class="px-2 py-1.5"></td>

                    <td class="px-2 py-1.5 text-center">
                        <button onclick="resetFilters()"
                                class="text-xs text-gray-400 hover:text-red-400 transition-colors whitespace-nowrap"
                                title="Reset semua filter kolom">
                            <i class="fas fa-filter-slash"></i> Reset
                        </button>
                    </td>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100" id="tableBody">
                @forelse($data as $index => $item)
                    <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $item->metadata_id }}">

                        @if((int)$statusFilter === 1)
                            <td class="px-4 py-3">
                                <input type="checkbox"
                                       class="row-check rounded border-gray-300 text-sky-600 cursor-pointer"
                                       value="{{ $item->metadata_id }}"
                                       onchange="updateSelectedCount()">
                            </td>
                        @else
                            <td class="px-4 py-3 text-gray-400 text-xs">
                                {{ $data->firstItem() + $index }}
                            </td>
                        @endif

                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $item->nama }}</p>
                            @if($item->alias)
                                <p class="text-xs text-gray-400 italic mt-0.5">{{ $item->alias }}</p>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-sky-50 text-sky-700 text-xs rounded-full font-medium">
                                {{ $item->klasifikasi?->nama_klasifikasi ?? '-' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $item->produsen?->nama_produsen
                                ? Str::limit($item->produsen->nama_produsen, 30)
                                : '-' }}
                        </td>

                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->tipe_data }}</td>

                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $item->user?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @php
                                $badge = match((int)$item->status) {
                                    2 => ['label'=>'Active',   'style'=>'background:#dcfce7;color:#15803d;'],
                                    3 => ['label'=>'Inactive', 'style'=>'background:#f3f4f6;color:#6b7280;'],
                                    default => ['label'=>'Pending','style'=>'background:#fef3c7;color:#b45309;'],
                                };
                            @endphp
                            <span style="{{ $badge['style'] }}"
                                  class="px-2.5 py-1 rounded-full text-xs font-semibold">
                                {{ $badge['label'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5 flex-wrap">

                                <a href="{{ route('metadata.detail', ['metadata' => $item->metadata_id, 'from' => 'approval']) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold
                                          text-white rounded-md transition-colors shadow-sm btn-primary">
                                    <i class="fas fa-clipboard-check"></i>
                                    Detail
                                </a>

                                @if((int)$item->status === 1)
                                    <button onclick="quickApprove({{ $item->metadata_id }}, '{{ addslashes($item->nama) }}', this)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold
                                                   text-white rounded-md transition-colors shadow-sm"
                                            style="background:#22c55e;"
                                            onmouseover="this.style.background='#16a34a'"
                                            onmouseout="this.style.background='#22c55e'">
                                        <i class="fas fa-check"></i>
                                        Approve
                                    </button>
                                @endif

                                @if((int)$item->status === 2)
                                    <button onclick="quickReject({{ $item->metadata_id }}, '{{ addslashes($item->nama) }}', this)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold
                                                   rounded-md transition-colors shadow-sm"
                                            style="border:1px solid #fca5a5; color:#ef4444; background:transparent;"
                                            onmouseover="this.style.background='#fef2f2'"
                                            onmouseout="this.style.background='transparent'">
                                        <i class="fas fa-ban"></i>
                                        Nonaktifkan
                                    </button>
                                @endif
                                @if((int)$item->status === 3)
                                    <button onclick="quickReactivate({{ $item->metadata_id }}, '{{ addslashes($item->nama) }}', this)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold
                                                   text-white rounded-md transition-colors shadow-sm"
                                            style="background:#22c55e;"
                                            onmouseover="this.style.background='#16a34a'"
                                            onmouseout="this.style.background='#22c55e'">
                                        <i class="fas fa-redo"></i>
                                        Aktifkan
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyRow">
                        <td colspan="9" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                @php
                                    $emptyIcon = match((int)$statusFilter) { 2=>'fa-check-circle', 3=>'fa-ban', default=>'fa-clock' };
                                    $emptyMsg  = match((int)$statusFilter) {
                                        2 => 'Belum ada metadata aktif',
                                        3 => 'Tidak ada metadata yang dinonaktifkan',
                                        default => 'Tidak ada metadata yang menunggu verifikasi'
                                    };
                                @endphp
                                <i class="fas {{ $emptyIcon }} text-4xl text-gray-300"></i>
                                <p class="font-medium text-gray-500">{{ $emptyMsg }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION (tidak diubah dari aslinya) --}}
    @if($data->hasPages())
        <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
            <p>
                Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
                dari {{ number_format($data->total()) }} data
            </p>
            {{ $data->withQueryString()->links() }}
        </div>
    @endif

</div>

<script>
/* ── Konstanta ───────────────────────────────────────────────── */
const CSRF          = '{{ csrf_token() }}';
const STATUS_FILTER = {{ (int)$statusFilter }};
const ROUTES = {
    approve:     id => `/metadata/${id}/approve`,
    reject:      id => `/metadata/${id}/reject`,
    reactivate:  id => `/metadata/${id}/reactivate`,
    bulkApprove: '/metadata/bulk-approve',
    approveAll:  '/metadata/bulk-approve-all',
};

/* ── Live clock (tidak diubah) ───────────────────────────────── */
function updateDateTime() {
    const now = new Date();
    document.getElementById('current-date').textContent =
        now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('current-time').textContent =
        now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' }) + ' WITA';
}
updateDateTime();
setInterval(updateDateTime, 1000);

/* ── Tab switcher (tidak diubah) ─────────────────────────────── */
const tabBorderColor = { 1:'#f59e0b', 2:'#22c55e', 3:'#9ca3af' };
const tabTextColor   = { 1:'#b45309', 2:'#15803d', 3:'#6b7280' };

function switchTab(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    ['page','search','filter_nama','filter_klasifikasi','filter_produsen_id',
     'filter_tipe_data','filter_user','filter_date_from','filter_date_to']
        .forEach(k => url.searchParams.delete(k));
    window.location.href = url.toString();
}

[1, 2, 3].forEach(s => {
    const btn = document.getElementById('tab-btn-' + s);
    if (!btn) return;
    btn.style.borderColor = s === STATUS_FILTER ? tabBorderColor[s] : 'transparent';
    btn.style.color       = s === STATUS_FILTER ? tabTextColor[s]   : '#9ca3af';
});

/* ════════════════════════════════════════════════════════════════
   [BARU] FILTER SERVER-SIDE
════════════════════════════════════════════════════════════════ */
function applyFilters() {
    const url = new URL(window.location.href);
    url.searchParams.set('status',             STATUS_FILTER);
    url.searchParams.set('filter_nama',        document.getElementById('filterNama')?.value        ?? '');
    url.searchParams.set('filter_klasifikasi', document.getElementById('filterKlasifikasi')?.value ?? '');
    url.searchParams.set('filter_produsen_id', document.getElementById('filterProdusen')?.value    ?? '');
    url.searchParams.set('filter_tipe_data',   document.getElementById('filterTipeData')?.value    ?? '');
    url.searchParams.set('filter_user',        document.getElementById('filterUser')?.value        ?? '');
    url.searchParams.set('filter_date_from',   document.getElementById('filterDateFrom')?.value    ?? '');
    url.searchParams.set('filter_date_to',     document.getElementById('filterDateTo')?.value      ?? '');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function resetFilters() {
    const url = new URL(window.location.href);
    ['filter_nama','filter_klasifikasi','filter_produsen_id','filter_tipe_data',
     'filter_user','filter_date_from','filter_date_to','search','page']
        .forEach(k => url.searchParams.delete(k));
    window.location.href = url.toString();
}

/* ── Checkbox select all ─────────────────────────────────────── */
function toggleCheckAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => { cb.checked = master.checked; });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    document.querySelectorAll('#selectedCount, #selectedCountBtn')
            .forEach(el => el.textContent = checked);

    const btn = document.getElementById('btnApproveSelected');
    if (btn) {
        btn.disabled = checked === 0;
        btn.classList.toggle('hidden', checked === 0);
        btn.classList.toggle('flex',   checked > 0);
    }

    const checkAll = document.getElementById('checkAll');
    const total    = document.querySelectorAll('.row-check').length;
    if (checkAll) {
        checkAll.checked       = checked > 0 && checked === total;
        checkAll.indeterminate = checked > 0 && checked < total;
    }
}

/* ── Utility: Alert & Loading ────────────────────────────────── */
function showAlert(type, msg) {
    const el   = document.getElementById('ajaxAlert');
    const isOk = type === 'success';
    el.innerHTML = `
        <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm border
                    ${isOk ? 'bg-green-50 border-green-200 text-green-700'
                           : 'bg-red-50 border-red-200 text-red-700'}">
            <i class="fas ${isOk ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'} shrink-0"></i>
            <span>${msg}</span>
        </div>`;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4500);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setBtnLoading(btn, loading, text = '') {
    if (!btn) return;
    if (loading) {
        btn._html     = btn.innerHTML;
        btn._disabled = btn.disabled;
        btn.disabled  = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>${text ? ' ' + text : ''}`;
    } else {
        btn.disabled  = btn._disabled ?? false;
        btn.innerHTML = btn._html ?? btn.innerHTML;
    }
}

/* ── Utility: animasi hapus baris & update counter ───────────── */
function fadeRemoveRow(row) {
    if (!row) return;
    row.style.transition = 'opacity 0.3s, transform 0.3s';
    row.style.opacity    = '0';
    row.style.transform  = 'translateX(8px)';
    setTimeout(() => { row.remove(); checkTableEmpty(); }, 300);
}

function updateStatBadge(dPending, dActive, dInactive) {
    [[dPending, '#statPending', '.tab-badge-1'],
     [dActive,  '#statActive',  '.tab-badge-2'],
     [dInactive,'#statInactive','.tab-badge-3']].forEach(([d, s1, s2]) => {
        if (d === 0) return;
        [s1, s2].forEach(sel => {
            const el = document.querySelector(sel);
            if (!el) return;
            el.textContent = Math.max(0, (parseInt(el.textContent.replace(/\D/g,''))||0) + d)
                                .toLocaleString('id-ID');
        });
    });
}

function checkTableEmpty() {
    const tbody = document.getElementById('tableBody');
    if (!tbody || tbody.querySelectorAll('tr[data-id]').length > 0) return;
    const icons = {1:'fa-clock',2:'fa-check-circle',3:'fa-ban'};
    const msgs  = {1:'Tidak ada metadata yang menunggu verifikasi',
                   2:'Belum ada metadata aktif',
                   3:'Tidak ada metadata yang dinonaktifkan'};
    tbody.innerHTML = `
        <tr><td colspan="9" class="px-4 py-16 text-center">
            <div class="flex flex-col items-center gap-3 text-gray-400">
                <i class="fas ${icons[STATUS_FILTER]||'fa-inbox'} text-4xl text-gray-300"></i>
                <p class="font-medium text-gray-500">${msgs[STATUS_FILTER]||'Data kosong'}</p>
            </div>
        </td></tr>`;
}

/* ════════════════════════════════════════════════════════════════
   QUICK APPROVE per baris (AJAX)
════════════════════════════════════════════════════════════════ */
async function quickApprove(id, nama, btn) {
    if (!confirm(`Approve metadata "${nama}"?`)) return;
    setBtnLoading(btn, true);
    try {
        const res  = await fetch(ROUTES.approve(id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (json.success) {
            fadeRemoveRow(btn.closest('tr'));
            updateStatBadge(-1, 0, 1);
            showAlert('success', json.message);
        } else {
            setBtnLoading(btn, false);
            showAlert('error', json.message || 'Gagal approve.');
        }
    } catch { setBtnLoading(btn, false); showAlert('error', 'Terjadi kesalahan jaringan.'); }
}

/* ════════════════════════════════════════════════════════════════
   QUICK REJECT (AJAX)
════════════════════════════════════════════════════════════════ */
async function quickReject(id, nama, btn) {
    if (!confirm(`Nonaktifkan metadata "${nama}"?`)) return;
    setBtnLoading(btn, true);
    try {
        const res  = await fetch(ROUTES.reject(id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (json.success) {
            fadeRemoveRow(btn.closest('tr'));
            updateStatBadge(0, -1, 0);
            showAlert('success', json.message);
        } else {
            setBtnLoading(btn, false);
            showAlert('error', json.message || 'Gagal nonaktifkan.');
        }
    } catch { setBtnLoading(btn, false); showAlert('error', 'Terjadi kesalahan jaringan.'); }
}

/* ════════════════════════════════════════════════════════════════
   QUICK REACTIVATE (AJAX)
════════════════════════════════════════════════════════════════ */
async function quickReactivate(id, nama, btn) {
    if (!confirm(`Aktifkan kembali metadata "${nama}"?`)) return;
    setBtnLoading(btn, true);
    try {
        const res  = await fetch(ROUTES.reactivate(id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (json.success) {
            fadeRemoveRow(btn.closest('tr'));
            updateStatBadge(0, 1, -1);
            showAlert('success', json.message);
        } else {
            setBtnLoading(btn, false);
            showAlert('error', json.message || 'Gagal aktifkan.');
        }
    } catch { setBtnLoading(btn, false); showAlert('error', 'Terjadi kesalahan jaringan.'); }
}

/* ════════════════════════════════════════════════════════════════
    APPROVE SELECTED — checkbox terpilih (AJAX bulk)
════════════════════════════════════════════════════════════════ */
async function approveSelected() {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(cb => cb.value);
    if (!ids.length) return;
    if (!confirm(`Approve ${ids.length} metadata terpilih?`)) return;

    const btn = document.getElementById('btnApproveSelected');
    setBtnLoading(btn, true, 'Menyetujui...');
    try {
        const res  = await fetch(ROUTES.bulkApprove, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ ids }),
        });
        const json = await res.json();
        if (json.success) {
            ids.forEach(id => document.querySelector(`tr[data-id="${id}"]`)?.remove());
            updateStatBadge(-ids.length, 0, ids.length);
            updateSelectedCount();
            checkTableEmpty();
            showAlert('success', json.message);
        } else {
            showAlert('error', json.message || 'Gagal bulk approve.');
        }
    } catch { showAlert('error', 'Terjadi kesalahan jaringan.'); }
    finally  { setBtnLoading(btn, false); }
}

/* ════════════════════════════════════════════════════════════════
    APPROVE ALL — semua data pending di DB (AJAX)
════════════════════════════════════════════════════════════════ */
async function approveAll(btn) {
    const total = parseInt('{{ $data->total() }}');
    if (!confirm(
        `Approve SEMUA ${total.toLocaleString('id-ID')} metadata Pending?\n\nTindakan ini tidak dapat dibatalkan.`
    )) return;

    setBtnLoading(btn, true, 'Menyetujui semua...');
    try {
        const res  = await fetch(ROUTES.approveAll, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ approve_all: true }),
        });
        const json = await res.json();
        if (json.success) {
            showAlert('success', json.message);
            setTimeout(() => {
                window.location.href = '{{ route("metadata.approval", ["status" => 2]) }}';
            }, 1500);
        } else {
            setBtnLoading(btn, false);
            showAlert('error', json.message || 'Gagal approve all.');
        }
    } catch { setBtnLoading(btn, false); showAlert('error', 'Terjadi kesalahan jaringan.'); }
}
</script>

@endsection