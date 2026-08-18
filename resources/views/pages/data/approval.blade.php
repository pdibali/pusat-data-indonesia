@extends('layouts.main')

@section('content')

    <a href="{{ route('data.index') }}"
        class="flex items-center gap-1 font-semibold text-sky-600 pt-6 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
            <i class="fas fa-angle-left"></i> Kembali
    </a>

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Approval Data</h1>
            <p class="text-sm text-gray-400 mt-1">Verifikasi data sebelum ditampilkan ke halaman utama</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p id="current-date"></p>
            <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
        </div>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- STATS --}}
    <div class="grid grid-cols-3 gap-4 mt-6">
        <div class="bg-amber-50 border border-amber-100 rounded-lg p-4">
            <p class="text-xs text-amber-500 font-medium uppercase tracking-wide">Menunggu Verifikasi</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($pendingCount) }}</p>
            <p class="text-xs text-amber-400 mt-1">data pending</p>
        </div>
        <div class="bg-green-50 border border-green-100 rounded-lg p-4">
            <p class="text-xs text-green-500 font-medium uppercase tracking-wide">Sudah Disetujui</p>
            <p class="text-2xl font-bold text-green-700 mt-1">{{ number_format($approvedCount) }}</p>
            <p class="text-xs text-green-400 mt-1">data available</p>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-lg p-4">
            <p class="text-xs text-red-500 font-medium uppercase tracking-wide">Ditolak</p>
            <p class="text-2xl font-bold text-red-700 mt-1">{{ number_format($rejectedCount) }}</p>
            <p class="text-xs text-red-400 mt-1">data rejected</p>
        </div>
    </div>

    {{-- TOP CONTROLS: Status tab + Reset + Bulk Approve --}}
    <div class="mt-5 flex flex-wrap gap-3 items-end">
        <div class="min-w-36">
            <label class="block text-xs text-gray-500 font-medium mb-1">Tab Status</label>
            <select id="statusSelect"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white"
                onchange="applyFilters()">
                <option value="0" {{ request('status', '0') == '0' ? 'selected' : '' }}>Pending</option>
                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Approved</option>
                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>

        @if(request()->hasAny(['metadata_id','filter_lokasi','filter_tahun','filter_nilai_min','filter_nilai_max','filter_user','filter_tanggal_dari','filter_tanggal_sampai']))
            <a href="{{ route('data.approval', request()->only('status')) }}"
               class="border border-gray-300 hover:bg-gray-50 text-gray-500 px-4 py-2 rounded-md text-sm transition-colors self-end">
                <i class="fas fa-times mr-1"></i> Reset Semua Filter
            </a>
        @endif

        {{-- Bulk approve button — menghormati filter kolom yang aktif --}}
        @if($pendingCount > 0 && request('status', '0') == '0')
            <div class="ml-auto self-end">
                <button type="button" onclick="confirmBulkApprove()"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-semibold
                        flex items-center gap-2 transition-colors shadow-md shadow-green-400/30">
                    <i class="fas fa-check-double"></i>
                    Approve Semua Data ({{ number_format($pendingCount) }})
                </button>
            </div>
        @endif
    </div>

    {{-- TABLE --}}
    <div class="mt-4 border rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3 font-semibold w-10">No</th>
                    <th class="px-4 py-3 font-semibold min-w-44">Metadata</th>
                    <th class="px-4 py-3 font-semibold min-w-32">Lokasi</th>
                    <th class="px-4 py-3 font-semibold min-w-28">Waktu</th>
                    <th class="px-4 py-3 font-semibold min-w-32">Nilai</th>
                    <th class="px-4 py-3 font-semibold min-w-28">Diinput Oleh</th>
                    <th class="px-4 py-3 font-semibold min-w-36">Produsen</th>
                    <th class="px-4 py-3 font-semibold min-w-36">Tanggal Input</th>
                    <th class="px-4 py-3 font-semibold min-w-20">Status</th>
                    <th class="px-4 py-3 font-semibold text-center min-w-24">Aksi</th>
                </tr>

                {{-- ═══════════════ FILTER ROW ═══════════════ --}}
                <tr class="bg-white border-t border-gray-100">
                    <td class="px-2 py-1.5"></td>

                    {{-- Metadata --}}
                    <td class="px-2 py-1.5">
                        <select id="metadataSelect"
                            placeholder="Cari metadata..."
                            class="tom-select w-full rounded-md text-xs bg-white">
                            <option value=""></option>
                            @foreach($metadataList as $meta)
                                <option value="{{ $meta->metadata_id }}"
                                    {{ request('metadata_id') == $meta->metadata_id ? 'selected' : '' }}>
                                    {{ $meta->nama }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- Lokasi --}}
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterLokasi" value="{{ request('filter_lokasi') }}"
                               placeholder="Filter lokasi..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    {{-- Waktu (tahun + granularitas) --}}
                    <td class="px-2 py-1.5 space-y-1">
                        <input type="text" id="filterTahun" value="{{ request('filter_tahun') }}"
                               placeholder="Filter tahun..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">

                        <select id="filterGranularitas"
                                class="w-full border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="onGranularitasChange()">
                            <option value="">Semua Periode</option>
                            <option value="dekade"   @selected(request('filter_granularitas')==='dekade')>Dekade</option>
                            <option value="tahunan"  @selected(request('filter_granularitas')==='tahunan')>Tahunan</option>
                            <option value="semester" @selected(request('filter_granularitas')==='semester')>Semester</option>
                            <option value="quarter"  @selected(request('filter_granularitas')==='quarter')>Kuartal</option>
                            <option value="bulanan"  @selected(request('filter_granularitas')==='bulanan')>Bulanan</option>
                            <option value="tanggal"  @selected(request('filter_granularitas')==='tanggal')>Tanggal Lengkap</option>
                        </select>

                        {{-- sub-periode: semester / kuartal / bulan (diisi via JS) --}}
                        <select id="filterSubPeriode"
                                class="w-full border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300 hidden"
                                onchange="applyFilters()">
                        </select>

                        {{-- nilai dekade, mis. 1990 --}}
                        <input type="number" id="filterDekade" value="{{ request('filter_dekade') }}"
                               placeholder="cth: 1990" step="10"
                               class="w-full border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300 hidden"
                               onkeydown="if(event.key==='Enter') applyFilters()">

                        {{-- tanggal lengkap --}}
                        <input type="date" id="filterTanggalLengkap" value="{{ request('filter_tanggal_lengkap') }}"
                               class="w-full border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300 hidden"
                               onchange="applyFilters()">
                    </td>

                    {{-- Nilai (min - max) --}}
                    <td class="px-2 py-1.5">
                        <div class="flex gap-1">
                            <input type="number" id="filterNilaiMin" value="{{ request('filter_nilai_min') }}"
                                   placeholder="Min" step="any"
                                   class="w-1/2 border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                                   onkeydown="if(event.key==='Enter') applyFilters()">
                            <input type="number" id="filterNilaiMax" value="{{ request('filter_nilai_max') }}"
                                   placeholder="Max" step="any"
                                   class="w-1/2 border border-gray-200 rounded px-1.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                                   onkeydown="if(event.key==='Enter') applyFilters()">
                        </div>
                    </td>

                    {{-- Diinput Oleh --}}
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterUser" value="{{ request('filter_user') }}"
                               placeholder="Filter user..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    {{-- Produsen filter --}}
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterProdusen" value="{{ request('filter_produsen') }}"
                               placeholder="Filter produsen..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    {{-- Tanggal Input (rentang) --}}
                    <td class="px-2 py-1.5">
                        <div class="flex gap-1">
                            <input type="date" id="filterTanggalDari" value="{{ request('filter_tanggal_dari') }}"
                                   title="Dari tanggal"
                                   class="w-1/2 border border-gray-200 rounded px-1 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                                   onchange="applyFilters()">
                            <input type="date" id="filterTanggalSampai" value="{{ request('filter_tanggal_sampai') }}"
                                   title="Sampai tanggal"
                                   class="w-1/2 border border-gray-200 rounded px-1 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-300"
                                   onchange="applyFilters()">
                        </div>
                    </td>

                    {{-- Status: dikontrol via Tab Status di atas --}}
                    <td class="px-2 py-1.5">
                        <p class="text-[11px] text-gray-300 italic text-center leading-tight">via tab<br>di atas</p>
                    </td>

                    {{-- Reset --}}
                    <td class="px-2 py-1.5 text-center">
                        <button onclick="resetFilters()" class="text-xs text-gray-400 hover:text-red-400 transition-colors whitespace-nowrap" title="Reset semua filter kolom">
                            <i class="fas fa-filter-circle-xmark"></i> Reset
                        </button>
                    </td>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $index => $row)
                    <tr class="hover:bg-gray-50 transition-colors" id="row-{{ $row->id }}">
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $data->firstItem() + $index }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">
                                {{ $row->metadata->nama ?? '-' }}
                            </p>

                            @if($row->metadata?->klasifikasi?->nama_klasifikasi)
                                <p class="text-xs text-gray-400">
                                    {{ $row->metadata->klasifikasi->nama_klasifikasi }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($row->location)
                                <p class="font-medium text-gray-400">{{ $row->location->nama_wilayah }}</p>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($row->time)

                                {{--  Jika hanya dekade   --}}
                                @if($row->time->month == 0 && $row->time->year == 0)
                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->decade }}an
                                    </p>
                                    <p class="text-gray-400">Dekade</p>

                                {{-- jika hanya tahun --}}
                                @elseif($row->time->month == 0)

                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->year }}
                                    </p>
                                    <p class="text-gray-400">Tahunan</p>
                                
                                {{-- Jika hanya Semester --}}
                                @elseif($row->time->day == 0 && $row->time->month == 0)
                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->year }}, Semester {{ $row->time->semester }}
                                    </p>
                                    <p class="text-gray-400">Semester</p>
                                
                                {{-- Jika hanya Kuartal --}}
                                @elseif($row->time->day == 0 && $row->time->year == 0)
                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->year }}, Kuartal {{ $row->time->quarter }}
                                    </p>
                                    <p class="text-gray-400">Kuartal</p>

                                {{-- jika sampai bulan --}}
                                @elseif($row->time->day == 0)

                                    <p class="font-medium text-gray-700">
                                        {{ \Carbon\Carbon::create($row->time->year, $row->time->month, 1)
                                            ->translatedFormat('F Y') }}
                                    </p>
                                    <p class="text-gray-400">Bulanan</p>

                                {{-- jika tanggal lengkap --}}
                                @else

                                    <p class="font-medium text-gray-700">
                                        {{ \Carbon\Carbon::create($row->time->year, $row->time->month, $row->time->day)
                                            ->translatedFormat('d F Y') }}
                                    </p>

                                @endif

                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(!is_null($row->number_value))
                                <span class="font-semibold text-gray-800">
                                    {{ rtrim(rtrim(number_format($row->number_value, 2, ',', '.'), '0'), ',') }}
                                    <span class="text-xs font-normal text-gray-400">{{ $row->metadata?->satuan_data }}</span>
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <p>{{ $row->user->name ?? '-' }}</p>
                        </td>

                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $row->produsen?->nama_produsen ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($row->date_inputed)->translatedFormat('d M Y') }}
                            <p class="text-gray-400">
                                {{ \Carbon\Carbon::parse($row->date_inputed)->translatedFormat('H:i') }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusMap = [
                                    0 => ['label' => 'Pending',   'color' => 'yellow'],
                                    1 => ['label' => 'Available', 'color' => 'green'],
                                    2 => ['label' => 'Ditolak',   'color' => 'red'],
                                ];
                                $s = $statusMap[$row->status] ?? $statusMap[0];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                bg-{{ $s['color'] }}-100 text-{{ $s['color'] }}-700">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                {{-- Detail --}}
                                <a href="{{ route('data.show', ['datum' => $row->id, 'from' => 'approval']) }}"
                                   class="text-sky-500 hover:text-sky-700 text-xs font-medium transition-colors"
                                   title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($row->status == 0)
                                    {{-- Approve --}}
                                    <form action="{{ route('data.approve', $row->id) }}" method="POST"
                                          id="approve-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('approve', {{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-xs font-medium transition-colors"
                                            title="Setujui">
                                            <i class="fas fa-check-circle"></i> Setujui
                                        </button>
                                    </form>

                                    {{-- Reject --}}
                                    <form action="{{ route('data.reject', $row->id) }}" method="POST"
                                          id="reject-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('reject', {{ $row->id }})"
                                            class="text-red-400 hover:text-red-600 text-xs font-medium transition-colors"
                                            title="Tolak">
                                            <i class="fas fa-times-circle"></i> Tolak
                                        </button>
                                    </form>
                                @elseif($row->status == 1)
                                    {{-- Bisa di-reject ulang --}}
                                    <form action="{{ route('data.reject', $row->id) }}" method="POST"
                                          id="reject-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('reject', {{ $row->id }})"
                                            class="text-red-400 hover:text-red-600 text-xs font-medium transition-colors">
                                            <i class="fas fa-ban"></i> Cabut
                                        </button>
                                    </form>
                                @elseif($row->status == 2)
                                    {{-- Bisa di-approve ulang --}}
                                    <form action="{{ route('data.approve', $row->id) }}" method="POST"
                                          id="approve-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('approve', {{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-xs font-medium transition-colors">
                                            <i class="fas fa-redo"></i> Pulihkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-clipboard-check text-4xl text-gray-300"></i>
                                <p class="font-medium text-gray-500">
                                    @if(request()->hasAny(['metadata_id','filter_lokasi','filter_tahun','filter_nilai_min','filter_nilai_max','filter_user','filter_tanggal_dari','filter_tanggal_sampai']))
                                        Tidak ada data yang cocok dengan filter
                                    @elseif(request('status', '0') == '0')
                                        Tidak ada data yang menunggu verifikasi
                                    @elseif(request('status') == '1')
                                        Belum ada data yang disetujui
                                    @else
                                        Belum ada data yang ditolak
                                    @endif
                                </p>
                                <a href="{{ route('data.index') }}"
                                   class="text-sky-500 hover:text-sky-700 text-sm font-medium transition-colors">
                                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Data
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($data->hasPages())
        <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
            <p>
                Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
                dari {{ number_format($data->total()) }} data
            </p>
            {{ $data->links() }}
        </div>
    @endif

</div>

{{-- MODAL KONFIRMASI --}}
<div id="confirmModal"
     class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div id="modalIcon"
                 class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
            </div>
            <div>
                <h3 id="modalTitle" class="font-bold text-gray-800"></h3>
                <p id="modalDesc" class="text-sm text-gray-500 mt-0.5"></p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="closeModal()"
                class="border border-gray-300 text-gray-500 hover:bg-gray-50 px-4 py-2 rounded-md text-sm transition-colors">
                Batal
            </button>
            <button id="modalConfirmBtn" onclick="submitAction()"
                class="px-4 py-2 rounded-md text-sm font-semibold text-white transition-colors">
                Konfirmasi
            </button>
        </div>
    </div>
</div>

<script>
    function initTomSelect(selector) {
        document.querySelectorAll(selector).forEach(el => {
            if (!el.tomselect) {
                new TomSelect(el, {
                    create: true,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    }
                });
            }
        });
    }

    // ── Live clock ──
    function updateDateTime() {
        const now = new Date();
        document.getElementById('current-date').textContent =
            now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('current-time').textContent =
            now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WITA';
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ── Confirm modal ──
    let pendingAction = null;
    let pendingId     = null;

    function confirmAction(action, id) {
        pendingAction = action;
        pendingId     = id;

        const isApprove = action === 'approve';
        const icon      = document.getElementById('modalIcon');
        const btn       = document.getElementById('modalConfirmBtn');

        icon.className = 'w-10 h-10 rounded-full flex items-center justify-center shrink-0 ' +
            (isApprove ? 'bg-green-100' : 'bg-red-100');
        icon.innerHTML = isApprove
            ? '<i class="fas fa-check-circle text-green-600 text-lg"></i>'
            : '<i class="fas fa-times-circle text-red-500 text-lg"></i>';

        document.getElementById('modalTitle').textContent =
            isApprove ? 'Setujui Data?' : 'Tolak Data?';
        document.getElementById('modalDesc').textContent =
            isApprove
                ? 'Data akan ditandai sebagai Available dan akan muncul di halaman utama.'
                : 'Data akan ditolak dan tidak akan tampil di halaman utama.';

        btn.className = 'px-4 py-2 rounded-md text-sm font-semibold text-white transition-colors ' +
            (isApprove ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600');
        btn.textContent = isApprove ? 'Ya, Setujui' : 'Ya, Tolak';

        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        pendingAction = null;
        pendingId     = null;
    }

    function submitAction() {
        if (!pendingAction || !pendingId) return;
        const formId = pendingAction + '-form-' + pendingId;
        document.getElementById(formId).submit();
    }

    // ── Bulk approve ──
    function confirmBulkApprove() {
        if (!confirm('Setujui semua data sesuai filter yang aktif?')) return;
        document.getElementById('bulkApproveForm').submit();
    }

    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // ── FILTER PER KOLOM ────────────────────────────────────────
    function applyFilters() {
        const url = new URL(window.location.href);

        const setOrDelete = (key, val) => {
            val = (val ?? '').toString().trim();
            if (val) url.searchParams.set(key, val);
            else url.searchParams.delete(key);
        };

        setOrDelete('status',                document.getElementById('statusSelect')?.value);
        setOrDelete('metadata_id',           document.getElementById('metadataSelect')?.value);
        setOrDelete('filter_lokasi',         document.getElementById('filterLokasi')?.value);
        setOrDelete('filter_tahun',          document.getElementById('filterTahun')?.value);
        setOrDelete('filter_nilai_min',      document.getElementById('filterNilaiMin')?.value);
        setOrDelete('filter_nilai_max',      document.getElementById('filterNilaiMax')?.value);
        setOrDelete('filter_user',           document.getElementById('filterUser')?.value);
        setOrDelete('filter_produsen',       document.getElementById('filterProdusen')?.value);
        setOrDelete('filter_tanggal_dari',   document.getElementById('filterTanggalDari')?.value);
        setOrDelete('filter_tanggal_sampai', document.getElementById('filterTanggalSampai')?.value);

        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function resetFilters() {
        const url = new URL(window.location.href);
        ['metadata_id','filter_lokasi','filter_tahun','filter_nilai_min','filter_nilai_max',
         'filter_user','filter_tanggal_dari','filter_tanggal_sampai','page']
            .forEach(k => url.searchParams.delete(k));
        window.location.href = url.toString();
    }

    // Trigger applyFilters() saat pilihan TomSelect metadata berubah
    document.getElementById('metadataSelect')?.addEventListener('change', applyFilters);

    initTomSelect('.tom-select');
</script>

{{-- Bulk approve form (hidden) — ikut semua filter kolom yang aktif --}}
<form id="bulkApproveForm" action="{{ route('data.bulk_approve') }}" method="POST" class="hidden">
    @csrf
    @foreach(request()->only(['metadata_id','filter_lokasi','filter_tahun','filter_nilai_min','filter_nilai_max','filter_user','filter_tanggal_dari','filter_tanggal_sampai']) as $key => $val)
        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
    @endforeach
</form>

@endsection