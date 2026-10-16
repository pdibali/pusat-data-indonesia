@extends('layouts.main')

@section('title', 'Log Aktivitas Pengguna')

@section('content')
<div class="page-layout">
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Log Aktivitas Pengguna</h1>
            <p class="mt-0.5 text-sm text-gray-500">Kunjungan halaman oleh pengguna yang login.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach([14, 30] as $retentionDays)
                <form method="POST" action="{{ route('admin.activity_logs.destroy_older_than') }}"
                      onsubmit="return confirm('Hapus permanen semua log yang lebih lama dari {{ $retentionDays }} hari? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="days" value="{{ $retentionDays }}">
                    <button type="submit"
                            class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                        <i class="fas fa-trash-can mr-1"></i> Hapus &gt;{{ $retentionDays }} hari
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    <div class="card-panel">
        <form id="activity-log-filters" method="GET" action="{{ route('admin.activity_logs.index') }}"
              class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-end">
            <div class="w-full sm:max-w-sm">
                <label for="activity-search" class="mb-1 block text-[11px] font-medium text-gray-500">Pengguna atau halaman</label>
                <input id="activity-search" type="search" name="search" value="{{ request('search') }}"
                       placeholder="Nama, route, atau path"
                       class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="activity-days" class="mb-1 block text-[11px] font-medium text-gray-500">Rentang waktu</label>
                <select id="activity-days" name="days"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1" {{ $days === 1 ? 'selected' : '' }}>Hari ini</option>
                    <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 hari terakhir</option>
                    <option value="14" {{ $days === 14 ? 'selected' : '' }}>14 hari terakhir</option>
                    <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 hari terakhir</option>
                </select>
            </div>
            <a id="activity-reset" href="{{ route('admin.activity_logs.index') }}"
               class="{{ request()->filled('search') || $days !== 30 ? '' : 'hidden' }} px-2 py-2 text-sm text-gray-500 hover:text-gray-800">
                Reset
            </a>
        </form>

        <div class="overflow-x-auto">
            <table id="activity-log-table"
                   data-current-page="{{ $activityLogs->currentPage() }}"
                   data-last-page="{{ $activityLogs->lastPage() }}"
                   data-total="{{ $activityLogs->total() }}"
                   class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Halaman</th>
                        <th class="px-4 py-3">Route</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Durasi</th>
                    </tr>
                </thead>
                <tbody id="activity-log-rows" class="divide-y divide-gray-100">
                    @forelse($activityLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->created_at?->format('d-m-Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $log->user_name ?? 'Akun dihapus' }}</td>
                            <td class="max-w-md break-all px-4 py-3 font-mono text-xs text-gray-700">{{ $log->request_path }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $log->route_name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $log->method }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold
                                    {{ $log->status_code >= 400 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $log->status_code }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->duration_ms }} ms</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">Tidak ada aktivitas dalam rentang waktu yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="activity-log-pagination"
             class="{{ $activityLogs->hasPages() ? '' : 'hidden' }} flex items-center justify-between border-t border-gray-100 p-4 text-sm">
            <span id="activity-log-page-label" class="text-gray-500">
                Halaman {{ $activityLogs->currentPage() }} dari {{ $activityLogs->lastPage() }} · {{ $activityLogs->total() }} log
            </span>
            <div class="flex gap-2">
                <button id="activity-log-previous" type="button"
                        class="rounded border border-gray-200 px-3 py-1.5 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                    Sebelumnya
                </button>
                <button id="activity-log-next" type="button"
                        class="rounded border border-gray-200 px-3 py-1.5 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                    Berikutnya
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('activity-log-filters');
    const searchInput = document.getElementById('activity-search');
    const daysSelect = document.getElementById('activity-days');
    const table = document.getElementById('activity-log-table');
    const rows = document.getElementById('activity-log-rows');
    const pagination = document.getElementById('activity-log-pagination');
    const pageLabel = document.getElementById('activity-log-page-label');
    const previousButton = document.getElementById('activity-log-previous');
    const nextButton = document.getElementById('activity-log-next');
    const resetLink = document.getElementById('activity-reset');
    let currentPage = Number(table.dataset.currentPage);
    let lastPage = Number(table.dataset.lastPage);
    let debounceTimer;
    let activeRequest;

    const makeCell = (value, className = '') => {
        const cell = document.createElement('td');
        cell.className = className;
        cell.textContent = value ?? '';
        return cell;
    };

    const updatePager = (page, pages, total) => {
        currentPage = page;
        lastPage = pages;
        table.dataset.currentPage = String(page);
        table.dataset.lastPage = String(pages);
        table.dataset.total = String(total);
        pagination.classList.toggle('hidden', pages <= 1);
        pageLabel.textContent = `Halaman ${page} dari ${pages} · ${total} log`;
        previousButton.disabled = page <= 1;
        nextButton.disabled = page >= pages;
    };

    const renderRows = (items) => {
        rows.replaceChildren();
        if (!items.length) {
            const row = document.createElement('tr');
            const cell = makeCell('Tidak ada aktivitas dalam rentang waktu yang dipilih.', 'px-4 py-10 text-center text-gray-400');
            cell.colSpan = 7;
            row.append(cell);
            rows.append(row);
            return;
        }

        items.forEach((item) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.append(
                makeCell(item.created_at, 'whitespace-nowrap px-4 py-3 text-gray-600'),
                makeCell(item.user_name, 'px-4 py-3 font-medium text-gray-800'),
                makeCell(item.request_path, 'max-w-md break-all px-4 py-3 font-mono text-xs text-gray-700'),
                makeCell(item.route_name, 'px-4 py-3 text-xs text-gray-500'),
                makeCell(item.method, 'px-4 py-3'),
            );

            const statusCell = document.createElement('td');
            statusCell.className = 'px-4 py-3';
            const badge = document.createElement('span');
            badge.className = `rounded-full px-2 py-1 text-xs font-semibold ${
                item.status_code >= 400 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'
            }`;
            badge.textContent = String(item.status_code);
            statusCell.append(badge);
            row.append(statusCell, makeCell(`${item.duration_ms} ms`, 'whitespace-nowrap px-4 py-3 text-gray-600'));
            rows.append(row);
        });
    };

    const loadActivityLogs = async (page = 1, updateHistory = true) => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const params = new URLSearchParams({
            days: daysSelect.value,
            search: searchInput.value.trim(),
            page: String(page),
        });

        try {
            const response = await fetch(`${form.action}?${params}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: activeRequest.signal,
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();
            renderRows(result.rows);
            updatePager(result.pagination.current_page, result.pagination.last_page, result.pagination.total);
            if (updateHistory) {
                history.replaceState(null, '', `${form.action}?${params}`);
            }
            if (resetLink) resetLink.classList.toggle('hidden', !searchInput.value.trim() && daysSelect.value === '30');
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Gagal memuat log aktivitas:', error);
                window.alert('Log aktivitas gagal dimuat. Silakan coba kembali.');
            }
        }
    };

    daysSelect.addEventListener('change', () => loadActivityLogs());
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadActivityLogs(), 300);
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadActivityLogs();
    });
    previousButton.addEventListener('click', () => {
        if (currentPage > 1) loadActivityLogs(currentPage - 1);
    });
    nextButton.addEventListener('click', () => {
        if (currentPage < lastPage) loadActivityLogs(currentPage + 1);
    });
    resetLink?.addEventListener('click', (event) => {
        event.preventDefault();
        searchInput.value = '';
        daysSelect.value = '30';
        loadActivityLogs();
    });
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(location.search);
        daysSelect.value = ['1', '7', '14', '30'].includes(params.get('days')) ? params.get('days') : '30';
        searchInput.value = params.get('search') ?? '';
        loadActivityLogs(Number(params.get('page') ?? 1), false);
    });

    updatePager(currentPage, lastPage, Number(table.dataset.total));
})();
</script>
@endsection
