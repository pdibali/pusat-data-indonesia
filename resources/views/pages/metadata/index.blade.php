@extends('layouts.main')

@section('content')

<div class="mt-2 bg-white rounded-xl shadow p-6">

    <div class="flex justify-between items-start flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Daftar Metadata</h1>
            <p class="text-sm text-gray-400 mt-1">Metadata aktif yang tersedia dalam sistem</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p id="current-date"></p>
            <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 mt-4 flex-wrap">
        @if($pendingCount > 0)
            <a href="{{ route('metadata.approval') }}"
               class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold border transition-colors"
               style="background:#fffbeb; border-color:#fde68a; color:#b45309;"
               onmouseover="this.style.background='#fef3c7'"
               onmouseout="this.style.background='#fffbeb'">
                <i class="fas fa-clock"></i>
                {{ $pendingCount }} Pending Approval
            </a>
        @endif
        <button onclick="openExportModal()"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold text-white transition-colors shadow-sm btn-primary">
            <i class="fas fa-file-excel"></i> Export Metadata
        </button>
        <button onclick="openTemplateModal()"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold text-white transition-colors shadow-sm btn-primary">
            <i class="fas fa-table-columns"></i> Export Template
        </button>
        <a href="{{ route('metadata.create') }}"
           class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold text-white transition-colors shadow-sm btn-primary">
            <i class="fas fa-plus"></i> Tambah Metadata
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Search --}}
    <div class="flex items-center gap-2 mt-5">
        <form method="GET" class="flex items-center gap-2 flex-1">
            <input type="hidden" name="filter_nama"        value="{{ request('filter_nama') }}">
            <input type="hidden" name="filter_klasifikasi" value="{{ request('filter_klasifikasi') }}">
            <input type="hidden" name="filter_tipe_data"   value="{{ request('filter_tipe_data') }}">
            <input type="hidden" name="filter_satuan"      value="{{ request('filter_satuan') }}">
            <input type="hidden" name="filter_frekuensi"   value="{{ request('filter_frekuensi') }}">
            <input type="hidden" name="filter_produsen_id" value="{{ request('filter_produsen_id') }}">
            <input type="hidden" name="filter_akses"       value="{{ request('filter_akses') }}">
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, alias, atau tag..."
                       class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>
            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white rounded-md transition-colors btn-primary">Cari</button>
        </form>
        @if(request()->hasAny(['search','filter_nama','filter_klasifikasi','filter_tipe_data','filter_satuan','filter_frekuensi','filter_produsen_id','filter_akses']))
            <a href="{{ route('metadata.index') }}"
               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md border border-red-200 text-red-400 hover:bg-red-50 transition-colors">
                <i class="fas fa-times-circle"></i> Reset Filter
            </a>
        @endif
        <p class="text-xs text-gray-400 ml-auto">{{ number_format($data->total()) }} metadata aktif</p>
    </div>

    {{-- Table --}}
    <div class="mt-4 border rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3 font-semibold w-10">No</th>
                    <th class="px-4 py-3 font-semibold min-w-52">Nama</th>
                    <th class="px-4 py-3 font-semibold min-w-28">Klasifikasi</th>
                    <th class="px-4 py-3 font-semibold min-w-20">Tipe Data</th>
                    <th class="px-4 py-3 font-semibold min-w-20">Satuan</th>
                    <th class="px-4 py-3 font-semibold min-w-24">Frekuensi</th>
                    <th class="px-4 py-3 font-semibold min-w-36">Sumber Metadata Pertama</th>
                    <th class="px-4 py-3 font-semibold text-center min-w-32">Akses</th>
                    <th class="px-4 py-3 font-semibold text-center min-w-24">Aksi</th>
                </tr>

                {{-- Filter row --}}
                <tr class="bg-white border-t border-gray-100">
                    <td class="px-2 py-1.5"></td>
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterNama" value="{{ request('filter_nama') }}"
                               placeholder="Filter nama..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>
                    <td class="px-2 py-1.5">
                        <select id="filterKlasifikasi" class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300" onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($klasifikasiList as $k)
                                <option value="{{ $k->klasifikasi_id }}" {{ (string)request('filter_klasifikasi') === (string)$k->klasifikasi_id ? 'selected' : '' }}>
                                    {{ $k->nama_klasifikasi }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-1.5">
                        <select id="filterTipeData" class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300" onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($tipeDataList as $t)
                                <option value="{{ $t }}" {{ request('filter_tipe_data') === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterSatuan" value="{{ request('filter_satuan') }}"
                               placeholder="Filter satuan..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>
                    <td class="px-2 py-1.5">
                        <select id="filterFrekuensi" class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300" onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($frekuensiList as $f)
                                <option value="{{ $f }}" {{ request('filter_frekuensi') === $f ? 'selected' : '' }}>{{ $f }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-2 py-1.5">
                        <select id="filterProdusen" class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300" onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($produsenList as $p)
                                <option value="{{ $p->produsen_id }}" {{ (string)request('filter_produsen_id') === (string)$p->produsen_id ? 'selected' : '' }}>
                                    {{ $p->nama_produsen }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    {{-- Filter Akses --}}
                    <td class="px-2 py-1.5">
                        <select id="filterAkses" class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300" onchange="applyFilters()">
                            <option value="">Semua</option>
                            <option value="gratis"  {{ request('filter_akses') === 'gratis'  ? 'selected' : '' }}>Gratis</option>
                            <option value="premium" {{ request('filter_akses') === 'premium' ? 'selected' : '' }}>Premium</option>
                        </select>
                    </td>
                    <td class="px-2 py-1.5 text-center">
                        <button onclick="resetFilters()" class="text-xs text-gray-400 hover:text-red-400 transition-colors whitespace-nowrap" title="Reset filter kolom">
                            <i class="fas fa-filter-slash"></i> Reset
                        </button>
                    </td>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($data as $index => $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $data->firstItem() + $index }}</td>

                        <td class="px-4 py-3">
                            <p class="text-xs text-gray-400 italic mt-0.5">#{{ $item->metadata_id }}</p>
                            <p class="font-semibold text-gray-800 leading-snug">{{ $item->nama }}</p>
                            @if($item->alias)
                                <p class="text-xs text-gray-400 italic mt-0.5">{{ $item->alias }}</p>
                            @endif
                            @if($item->tag && $item->tag !== '-')
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach(array_slice(explode(',', $item->tag), 0, 3) as $tag)
                                        <span class="text-xs px-1.5 py-0.5 rounded-full font-medium" style="background:#e0f2fe; color:#0369a1;">
                                            #{{ trim($tag) }}
                                        </span>
                                    @endforeach
                                    @if(count(explode(',', $item->tag)) > 3)
                                        <span class="text-xs text-gray-400">+{{ count(explode(',', $item->tag)) - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-sky-50 text-sky-700 text-xs rounded-full font-medium">
                                {{ $item->klasifikasi?->nama_klasifikasi ?? '-' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->tipe_data }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->satuan_data }}</td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#fffbeb; color:#b45309;">
                                {{ $item->frekuensi_penerbitan }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $item->produsen?->nama_produsen ? Str::limit($item->produsen->nama_produsen, 28) : '-' }}
                        </td>

                        {{-- Toggle Akses --}}
                        <td class="px-4 py-3 text-center">
                            <button
                                onclick="toggleFree({{ $item->metadata_id }}, {{ $item->is_free ? 'true' : 'false' }}, this)"
                                id="free-btn-{{ $item->metadata_id }}"
                                title="{{ $item->is_free ? 'Klik untuk jadikan Premium' : 'Klik untuk jadikan Gratis' }}"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-300 border-0 cursor-pointer"
                                style="{{ $item->is_free
                                    ? 'background:#dcfce7; color:#15803d;'
                                    : 'background:#f3f4f6; color:#6b7280;' }}">

                                {{-- Toggle pill --}}
                                <span class="relative inline-flex items-center w-9 h-5 rounded-full transition-colors duration-300 shrink-0"
                                      id="free-track-{{ $item->metadata_id }}"
                                      style="{{ $item->is_free ? 'background:#22c55e;' : 'background:#d1d5db;' }}">
                                    <span class="absolute w-3.5 h-3.5 bg-white rounded-full shadow transition-all duration-300"
                                          id="free-thumb-{{ $item->metadata_id }}"
                                          style="{{ $item->is_free ? 'left: 18px;' : 'left: 2px;' }}">
                                    </span>
                                </span>

                                <span id="free-label-{{ $item->metadata_id }}">
                                    {{ $item->is_free ? 'Gratis' : 'Premium' }}
                                </span>
                            </button>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('metadata.detail', $item->metadata_id) }}"
                                title="Lihat Detail"
                                class="w-7 h-7 flex items-center justify-center rounded-md bg-sky-600 text-white hover:bg-sky-700 transition">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>

                                <button type="button"
                                        onclick="openCoverage({{ $item->metadata_id }}, '{{ addslashes($item->nama) }}')"
                                        title="Lihat Cakupan Wilayah"
                                        class="w-7 h-7 flex items-center justify-center rounded-md bg-indigo-500 text-white hover:bg-indigo-600 transition">
                                    <i class="fas fa-map-marker-alt text-xs"></i>
                                </button>

                                <a href="{{ route('metadata.edit', $item->metadata_id) }}"
                                title="Edit Metadata"
                                class="w-7 h-7 flex items-center justify-center rounded-md bg-amber-500 text-white hover:bg-amber-600 transition">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                            </div>
                        </td>
                        
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-database text-4xl text-gray-200"></i>
                                <p class="font-medium text-gray-500">Tidak ada metadata ditemukan</p>
                                @if(request()->hasAny(['search','filter_nama','filter_klasifikasi','filter_tipe_data','filter_satuan','filter_frekuensi','filter_produsen_id','filter_akses']))
                                    <a href="{{ route('metadata.index') }}" class="text-xs text-sky-500 hover:text-sky-700 underline">Hapus semua filter</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
            <p>Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ number_format($data->total()) }} data</p>
            {{ $data->withQueryString()->links() }}
        </div>
    @endif

</div>

{{-- Modal Export --}}
<div id="exportModal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0,0,0,0.45);">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b" style="background: linear-gradient(90deg,#0284c7,#38bdf8);">
            <div class="flex items-center gap-2 text-white">
                <i class="fas fa-file-excel text-lg"></i>
                <h2 class="text-base font-bold">Export Metadata ke Excel</h2>
            </div>
            <button onclick="closeExportModal()" class="text-white/80 hover:text-white transition-colors text-xl leading-none"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500 mb-5">Pilih filter di bawah untuk menentukan data yang akan diekspor.</p>
            <form id="exportForm" method="GET" action="{{ route('metadata.export') }}">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="fas fa-building text-sky-400 mr-1"></i> Produsen Data</label>
                    <select name="produsen_id" id="exportProdusen" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 text-gray-700">
                        <option value="">— Semua Produsen —</option>
                        @foreach($produsenAll as $p)
                            <option value="{{ $p->produsen_id }}">{{ $p->nama_produsen }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="fas fa-calendar-alt text-green-400 mr-1"></i> Frekuensi Penerbitan</label>
                    <select name="frekuensi" id="exportFrekuensi" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 text-gray-700">
                        <option value="">— Semua Frekuensi —</option>
                        <option value="5 Tahun">5 Tahun</option>
                        <option value="Tahunan">Tahunan</option>
                        <option value="Semester">Semester</option>
                        <option value="Quarter">Quarter</option>
                        <option value="Bulanan">Bulanan</option>
                    </select>
                </div>
                <div id="exportPreview" class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-xs mb-5" style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1;">
                    <i class="fas fa-info-circle shrink-0"></i>
                    <span id="exportPreviewText">Menghitung jumlah data…</span>
                </div>
                <div class="flex gap-3">
                    <button type="submit" id="exportBtn" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors shadow-sm" style="background:#059669;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                        <i class="fas fa-download"></i> Download Excel
                    </button>
                    <button type="button" onclick="closeExportModal()" class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Template --}}
<div id="templateModal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0,0,0,0.45);">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b" style="background: linear-gradient(90deg,#7c3aed,#a78bfa);">
            <div class="flex items-center gap-2 text-white">
                <i class="fas fa-table-columns text-lg"></i>
                <h2 class="text-base font-bold">Export Template Metadata</h2>
            </div>
            <button onclick="closeTemplateModal()" class="text-white/80 hover:text-white transition-colors text-xl leading-none"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500 mb-5">Menghasilkan template Excel untuk proses input data.</p>
            <form id="templateForm" method="GET" action="{{ route('metadata.template') }}">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="fas fa-building text-violet-400 mr-1"></i> Produsen Data <span class="text-red-500">*</span></label>
                    <select name="produsen_id" id="tplProdusen" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 text-gray-700">
                        <option value="">— Pilih Produsen —</option>
                        @foreach($produsenAll as $p)
                            <option value="{{ $p->produsen_id }}">{{ $p->nama_produsen }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="fas fa-calendar text-green-400 mr-1"></i> Rentang Waktu <span class="text-red-500">*</span></label>
                    <select name="rentang" id="tplRentang" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 text-gray-700" onchange="updateTplPreview()">
                        <option value="">— Pilih Rentang —</option>
                        <option value="5-tahun">5 Tahun</option>
                        <option value="semester">Semester</option>
                        <option value="quarter">Quarter</option>
                        <option value="bulanan">Bulanan</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Rentang waktu selalu dalam 5 tahun.</p>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="fas fa-calendar-plus text-sky-400 mr-1"></i> Tahun Awal <span class="text-red-500">*</span></label>
                    <input type="number" name="tahun_awal" id="tplTahun" min="1990" max="2099" placeholder="Contoh: {{ date('Y') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 text-gray-700"
                           oninput="updateTplPreview()">
                </div>
                <div id="tplPreview" class="rounded-lg p-3 mb-5 text-xs" style="background:#faf5ff; border:1px solid #e9d5ff; color:#6d28d9; display:none;">
                    <p class="font-semibold mb-1.5 flex items-center gap-1.5"><i class="fas fa-eye"></i> Preview Kolom Periode</p>
                    <div id="tplPreviewCols" class="flex flex-wrap gap-1 mt-1 max-h-24 overflow-y-auto"></div>
                    <p id="tplPreviewCount" class="mt-2 text-violet-500 font-medium"></p>
                </div>
                <div id="tplMetaPreview" class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-xs mb-5" style="background:#faf5ff; border:1px solid #e9d5ff; color:#6d28d9; display:none;">
                    <i class="fas fa-database shrink-0"></i>
                    <span id="tplMetaPreviewText"></span>
                </div>
                <div class="flex gap-3">
                    <button type="submit" id="tplBtn" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors shadow-sm" style="background:#7c3aed;" onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
                        <i class="fas fa-download"></i> Download Template
                    </button>
                    <button type="button" onclick="closeTemplateModal()" class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ── MODAL COVERAGE ─────────────────────────────────── --}}
<div id="modal-coverage"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     onclick="if(event.target===this) closeCoverage()">

    {{-- backdrop --}}
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    {{-- panel --}}
    <div class="relative bg-stikom border border-white/10 rounded-xl shadow-2xl
                w-full max-w-2xl max-h-[80vh] flex flex-col">

        {{-- header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/10 shrink-0">
            <div>
                <p class="text-[10px] text-stikom-blue uppercase tracking-widest font-semibold mb-0.5">
                    Cakupan Wilayah
                </p>
                <h3 id="modal-coverage-title" class="text-sm font-bold text-white leading-tight"></h3>
            </div>
            <button onclick="closeCoverage()"
                    class="w-7 h-7 flex items-center justify-center rounded-lg
                           bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        {{-- body --}}
        <div class="flex-1 overflow-y-auto px-5 py-4">

            {{-- loading --}}
            <div id="modal-coverage-loading" class="flex items-center justify-center py-12 gap-3 text-gray-500">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <span class="text-sm">Memuat data...</span>
            </div>

            {{-- empty --}}
            <div id="modal-coverage-empty" class="hidden text-center py-12">
                <i class="fas fa-map-marker-slash text-3xl text-gray-600 mb-3"></i>
                <p class="text-sm text-gray-500">Belum ada data wilayah untuk metadata ini.</p>
            </div>

            {{-- tabel --}}
            <div id="modal-coverage-table" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <p id="modal-coverage-count" class="text-xs text-gray-500"></p>
                    <input
                        id="modal-coverage-search"
                        type="text"
                        placeholder="Filter wilayah..."
                        oninput="filterCoverageTable(this.value)"
                        class="text-xs bg-white/5 border border-white/10 text-gray-300
                               placeholder-gray-600 px-3 py-1.5 rounded-lg outline-none
                               focus:border-purple-400/50 w-44"/>
                </div>

                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-white/10 text-gray-500">
                            <th class="text-left py-2 pr-3 font-semibold w-8">#</th>
                            <th class="text-left py-2 pr-3 font-semibold">Nama Wilayah</th>
                            <th class="text-center py-2 pr-3 font-semibold">Jml Data</th>
                            <th class="text-center py-2 font-semibold">Rentang Tahun</th>
                        </tr>
                    </thead>
                    <tbody id="modal-coverage-tbody" class="divide-y divide-white/5 text-gray-300">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Smooth toggle animation */
#free-thumb-* { transition: left 0.25s cubic-bezier(.4,0,.2,1); }
</style>


<script>
let _coverageRows = [];

function openCoverage(metadataId, nama) {
    const modal   = document.getElementById('modal-coverage');
    const title   = document.getElementById('modal-coverage-title');
    const loading = document.getElementById('modal-coverage-loading');
    const empty   = document.getElementById('modal-coverage-empty');
    const table   = document.getElementById('modal-coverage-table');
    const search  = document.getElementById('modal-coverage-search');

    title.textContent = nama;
    search.value = '';
    loading.classList.remove('hidden');
    empty.classList.add('hidden');
    table.classList.add('hidden');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    fetch(`/metadata/${metadataId}/coverage`)
        .then(r => r.json())
        .then(data => {
            loading.classList.add('hidden');
            _coverageRows = data.locations;

            if (!_coverageRows.length) {
                empty.classList.remove('hidden');
                return;
            }

            renderCoverageTable(_coverageRows);
            table.classList.remove('hidden');
        })
        .catch(() => {
            loading.classList.add('hidden');
            empty.classList.remove('hidden');
        });
}

function renderCoverageTable(rows) {
    const tbody = document.getElementById('modal-coverage-tbody');
    const count = document.getElementById('modal-coverage-count');

    count.textContent = rows.length + ' wilayah ditemukan';

    // level label helper
    const levelLabel = (id) => {
        id = String(id).padStart(10, '0');
        if (id.slice(6) !== '0000')    return '<span class="px-1.5 py-0.5 rounded text-[10px] bg-green-500/10 text-green-400">Desa</span>';
        if (id.slice(4, 7) !== '000')  return '<span class="px-1.5 py-0.5 rounded text-[10px] bg-blue-500/10 text-blue-400">Kecamatan</span>';
        if (id.slice(2, 4) !== '00')   return '<span class="px-1.5 py-0.5 rounded text-[10px] bg-yellow-500/10 text-yellow-400">Kabupaten</span>';
        return '<span class="px-1.5 py-0.5 rounded text-[10px] bg-purple-500/10 text-purple-400">Provinsi</span>';
    };

    tbody.innerHTML = rows.map((loc, i) => `
        <tr class="hover:bg-white/5 transition">
            <td class="py-2 pr-3 text-gray-600">${i + 1}</td>
            <td class="py-2 pr-3 font-medium text-gray-200">${loc.nama_wilayah ?? '-'}</td>
            <td class="py-2 pr-3 text-center">
                <span class="px-2 py-0.5 bg-white/5 rounded font-mono">${loc.jumlah_data}</span>
            </td>
            <td class="py-2 text-center text-gray-400">
                ${loc.tahun_min === loc.tahun_max
                    ? loc.tahun_min
                    : `${loc.tahun_min} – ${loc.tahun_max}`}
            </td>
        </tr>
    `).join('');
}

function filterCoverageTable(q) {
    const filtered = q.trim().length < 1
        ? _coverageRows
        : _coverageRows.filter(r =>
            (r.nama_wilayah ?? '').toLowerCase().includes(q.toLowerCase()) ||
            String(r.location_id).includes(q)
          );
    renderCoverageTable(filtered);
}

function closeCoverage() {
    const modal = document.getElementById('modal-coverage');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeCoverage();
});
</script>

<script>
const CSRF = '{{ csrf_token() }}';

/* Clock */
function updateDateTime() {
    const now = new Date();
    const d = document.getElementById('current-date');
    const t = document.getElementById('current-time');
    if (d) d.textContent = now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    if (t) t.textContent = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
}
updateDateTime();
setInterval(updateDateTime, 1000);

/* Filters */
function applyFilters() {
    const url = new URL(window.location.href);
    url.searchParams.set('filter_nama',        document.getElementById('filterNama')?.value        ?? '');
    url.searchParams.set('filter_klasifikasi', document.getElementById('filterKlasifikasi')?.value ?? '');
    url.searchParams.set('filter_tipe_data',   document.getElementById('filterTipeData')?.value    ?? '');
    url.searchParams.set('filter_satuan',      document.getElementById('filterSatuan')?.value      ?? '');
    url.searchParams.set('filter_frekuensi',   document.getElementById('filterFrekuensi')?.value   ?? '');
    url.searchParams.set('filter_produsen_id', document.getElementById('filterProdusen')?.value    ?? '');
    url.searchParams.set('filter_akses',       document.getElementById('filterAkses')?.value       ?? '');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function resetFilters() {
    const url = new URL(window.location.href);
    ['filter_nama','filter_klasifikasi','filter_tipe_data','filter_satuan',
     'filter_frekuensi','filter_produsen_id','filter_akses','search','page']
        .forEach(k => url.searchParams.delete(k));
    window.location.href = url.toString();
}

/* ════════════════════════════════════════════════════════════════
   TOGGLE FREE — langsung tanpa confirm
════════════════════════════════════════════════════════════════ */
async function toggleFree(id, currentIsFree, btn) {
    // Optimistic update dulu
    const newIsFree = !currentIsFree;
    applyToggleUI(id, newIsFree, btn);

    try {
        const res  = await fetch(`/metadata/${id}/toggle-free`, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();

        if (json.success) {
            // Konfirmasi dari server
            applyToggleUI(id, json.is_free, btn);
            btn.setAttribute('onclick', `toggleFree(${id}, ${json.is_free}, this)`);
            btn.title = json.is_free ? 'Klik untuk jadikan Premium' : 'Klik untuk jadikan Gratis';
        } else {
            // Rollback
            applyToggleUI(id, currentIsFree, btn);
        }
    } catch {
        // Rollback jika error
        applyToggleUI(id, currentIsFree, btn);
    }
}

function applyToggleUI(id, isFree, btn) {
    const track  = document.getElementById(`free-track-${id}`);
    const thumb  = document.getElementById(`free-thumb-${id}`);
    const label  = document.getElementById(`free-label-${id}`);

    if (isFree) {
        track.style.background  = '#22c55e';
        thumb.style.left        = '18px';
        label.textContent       = 'Gratis';
        btn.style.background    = '#dcfce7';
        btn.style.color         = '#15803d';
    } else {
        track.style.background  = '#d1d5db';
        thumb.style.left        = '2px';
        label.textContent       = 'Premium';
        btn.style.background    = '#f3f4f6';
        btn.style.color         = '#6b7280';
    }
}

/* Export Modal */
const exportModal    = document.getElementById('exportModal');
const exportProdusen = document.getElementById('exportProdusen');
const exportFrekuensi= document.getElementById('exportFrekuensi');
const previewText    = document.getElementById('exportPreviewText');

function openExportModal()  { exportModal.classList.remove('hidden'); exportModal.classList.add('flex'); fetchPreviewCount(); }
function closeExportModal() { exportModal.classList.add('hidden'); exportModal.classList.remove('flex'); }
exportModal.addEventListener('click', e => { if (e.target === exportModal) closeExportModal(); });

let previewDebounce = null;
function fetchPreviewCount() {
    clearTimeout(previewDebounce);
    previewText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menghitung…';
    previewDebounce = setTimeout(async () => {
        try {
            const params = new URLSearchParams();
            if (exportProdusen.value)  params.set('produsen_id', exportProdusen.value);
            if (exportFrekuensi.value) params.set('frekuensi',   exportFrekuensi.value);
            const res  = await fetch('{{ route("metadata.export.count") }}?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            const previewEl = document.getElementById('exportPreview');
            const btnEl     = document.getElementById('exportBtn');
            if (json.count === 0) {
                previewText.textContent = 'Tidak ada data sesuai filter.';
                previewEl.style.cssText = 'background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;font-size:0.75rem;margin-bottom:1.25rem;';
                btnEl.disabled = true;
            } else {
                previewText.textContent = `${json.count.toLocaleString('id-ID')} metadata akan diekspor.`;
                previewEl.style.cssText = 'background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;font-size:0.75rem;margin-bottom:1.25rem;';
                btnEl.disabled = false;
            }
        } catch { previewText.textContent = 'Gagal menghitung data.'; }
    }, 350);
}
exportProdusen.addEventListener('change', fetchPreviewCount);
exportFrekuensi.addEventListener('change', fetchPreviewCount);
document.getElementById('exportForm').addEventListener('submit', function() {
    const btn = document.getElementById('exportBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan file…';
    setTimeout(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-download"></i> Download Excel'; }, 8000);
});

/* Template Modal */
const templateModal = document.getElementById('templateModal');
const tplProdusen   = document.getElementById('tplProdusen');
const tplRentang    = document.getElementById('tplRentang');
const tplTahun      = document.getElementById('tplTahun');

function openTemplateModal()  { templateModal.classList.remove('hidden'); templateModal.classList.add('flex'); }
function closeTemplateModal() { templateModal.classList.add('hidden'); templateModal.classList.remove('flex'); }
templateModal.addEventListener('click', e => { if (e.target === templateModal) closeTemplateModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeExportModal(); closeTemplateModal(); } });

const BULAN_PENDEK = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
function generatePeriodCols(rentang, tahunAwal) {
    const cols = []; const start = parseInt(tahunAwal);
    if (isNaN(start) || start < 1990 || start > 2099) return cols;
    switch (rentang) {
        case '5-tahun':  for (let y = start; y < start+5; y++) cols.push(String(y)); break;
        case 'semester': for (let y = start; y < start+5; y++) for (let s=1;s<=2;s++) cols.push(`${y}_S${s}`); break;
        case 'quarter':  for (let y = start; y < start+5; y++) for (let q=1;q<=4;q++) cols.push(`${y}_Q${q}`); break;
        case 'bulanan':  for (let y = start; y < start+5; y++) for (let m=0;m<12;m++) cols.push(`${BULAN_PENDEK[m]}_${y}`); break;
    }
    return cols;
}
function updateTplPreview() {
    const rentang = tplRentang.value; const tahun = tplTahun.value;
    const previewEl = document.getElementById('tplPreview');
    const colsEl    = document.getElementById('tplPreviewCols');
    const countEl   = document.getElementById('tplPreviewCount');
    if (!rentang || !tahun) { previewEl.style.display = 'none'; return; }
    const cols = generatePeriodCols(rentang, tahun);
    if (cols.length === 0) { previewEl.style.display = 'none'; return; }
    const preview = cols.slice(0, 20);
    colsEl.innerHTML = preview.map(c => `<span style="background:#ede9fe;color:#5b21b6;border-radius:9999px;padding:1px 8px;">${c}</span>`).join('')
        + (cols.length > 20 ? `<span style="color:#7c3aed;font-style:italic;">…+${cols.length-20} lagi</span>` : '');
    const label = {'5-tahun':'5 Tahun','semester':'Semester','quarter':'Quarter','bulanan':'Bulanan'};
    countEl.textContent = `Total: 4 kolom tetap + ${cols.length} kolom ${label[rentang]} = ${4+cols.length} kolom`;
    previewEl.style.display = 'block';
    fetchTplMetaCount();
}
let tplMetaDebounce = null;
function fetchTplMetaCount() {
    clearTimeout(tplMetaDebounce);
    const metaEl = document.getElementById('tplMetaPreview');
    const textEl = document.getElementById('tplMetaPreviewText');
    if (!tplProdusen.value) { metaEl.style.display = 'none'; return; }
    textEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menghitung…';
    metaEl.style.display = 'flex';
    tplMetaDebounce = setTimeout(async () => {
        try {
            const params = new URLSearchParams({ produsen_id: tplProdusen.value });
            const res  = await fetch('{{ route("metadata.export.count") }}?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (json.count === 0) {
                textEl.textContent = 'Produsen ini tidak memiliki metadata aktif.';
                metaEl.style.cssText = 'display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;font-size:0.75rem;margin-bottom:1.25rem;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;';
                document.getElementById('tplBtn').disabled = true;
            } else {
                textEl.textContent = `${json.count} baris metadata akan dimasukkan ke template.`;
                metaEl.style.cssText = 'display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;font-size:0.75rem;margin-bottom:1.25rem;background:#faf5ff;border:1px solid #e9d5ff;color:#6d28d9;';
                document.getElementById('tplBtn').disabled = false;
            }
        } catch { textEl.textContent = 'Gagal menghitung data.'; }
    }, 300);
}
tplProdusen.addEventListener('change', function() { updateTplPreview(); if (!tplRentang.value || !tplTahun.value) fetchTplMetaCount(); });
document.getElementById('templateForm').addEventListener('submit', function(e) {
    if (!tplProdusen.value || !tplRentang.value || !tplTahun.value) { e.preventDefault(); alert('Lengkapi semua field wajib.'); return; }
    const btn = document.getElementById('tplBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan template…';
    setTimeout(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-download"></i> Download Template'; }, 10000);
});
</script>

@endsection