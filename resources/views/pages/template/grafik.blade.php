@extends('layouts.main')

@section('content')

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

{{-- ═══════════════════════════════════════════════════
     STYLES
════════════════════════════════════════════════════ --}}
<style>
    /* ── Variabel Warna & Token ── */
    :root {
        --clr-bg:             #f8fafc;
        --clr-surface:        #ffffff;
        --clr-border:         #e5e7eb;
        --clr-border-hover:   #9ca3af;

        --clr-text-primary:   #111827;
        --clr-text-secondary: #374151;
        --clr-text-muted:     #9ca3af;
        --clr-text-subtle:    #6b7280;

        --clr-accent:         #0ea5e9;
        --clr-accent-dark:    #0284c7;

        --radius-card:        16px;
        --radius-btn:         8px;

        --shadow-card:        0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.04);
        --shadow-dropdown:    0 6px 20px rgba(0,0,0,.10);
    }

    /* ══════════════════════════════════
       CARD GRAFIK
    ══════════════════════════════════ */
    .chart-card {
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--radius-card);
        padding: 28px 28px 24px;
        box-shadow: var(--shadow-card);
        position: relative;
    }


    /* ══════════════════════════════════
       DROPDOWN JENIS GRAFIK
    ══════════════════════════════════ */
    .chart-type-wrap {
        position: absolute;
        top: 24px;
        right: 24px;
    }

    .chart-type-trigger {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: var(--radius-btn);
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 600;
        color: var(--clr-text-secondary);
        cursor: pointer;
        user-select: none;
        transition: border-color .15s, box-shadow .15s;
        white-space: nowrap;
    }
    .chart-type-trigger:hover {
        border-color: var(--clr-border-hover);
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
    }
    .chart-type-trigger .chevron {
        font-size: 9px;
        color: var(--clr-text-muted);
        transition: transform .2s;
    }
    .chart-type-trigger.open .chevron { transform: rotate(180deg); }

    .chart-type-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        background: var(--clr-surface);
        border: 1px solid var(--clr-border);
        border-radius: 10px;
        box-shadow: var(--shadow-dropdown);
        overflow: hidden;
        min-width: 130px;
        z-index: 30;
    }
    .chart-type-dropdown.open { display: block; }

    .chart-type-option {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 500;
        color: var(--clr-text-secondary);
        background: transparent;
        border: none;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background .12s;
        text-align: left;
    }
    .chart-type-option:last-child { border-bottom: none; }
    .chart-type-option:hover      { background: #f9fafb; }
    .chart-type-option.active     {
        background: #f0f9ff;
        color: var(--clr-accent-dark);
        font-weight: 700;
    }
    .chart-type-option .opt-icon { font-size: 11px; width: 14px; }

    /* ══════════════════════════════════
       AREA CANVAS
    ══════════════════════════════════ */
    .chart-area {
        display: flex;
        align-items: stretch;
        margin-top: 24px;
    }

    .y-label-col {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        flex-shrink: 0;
    }
    .y-label-text {
        font-size: 10.5px;
        color: var(--clr-text-muted);
        font-weight: 500;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        white-space: nowrap;
        letter-spacing: .03em;
    }

    .canvas-col {
        flex: 1;
        position: relative;
        min-height: 260px;
        height: 260px;      /* ← tambahkan height eksplisit */
        max-width: 100%;    /* ← cegah overflow horizontal */
        overflow: hidden;   /* ← tambahkan ini */
    }

    .x-axis-row {
        display: flex;
        justify-content: flex-end;
        padding-left: 36px;
        margin-top: 4px;
    }
    .x-label-text {
        font-size: 10.5px;
        color: var(--clr-text-muted);
        font-weight: 500;
        letter-spacing: .03em;
    }

    /* ══════════════════════════════════
       SKELETON & EMPTY STATE
    ══════════════════════════════════ */
    @keyframes shimmer {
        0%   { background-position: -700px 0; }
        100% { background-position:  700px 0; }
    }

    .skeleton-bar {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 700px 100%;
        animation: shimmer 1.4s infinite;
        border-radius: 6px;
    }

    .skeleton-chart {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        padding: 20px 12px 0;
        height: 280px;
    }
    .skeleton-chart .sk-col { flex: 1; border-radius: 6px 6px 0 0; }

    .chart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 300px;
        gap: 12px;
    }
    .chart-empty svg { opacity: .35; }
    .chart-empty p {
        font-size: 13px;
        color: var(--clr-text-muted);
        margin: 0;
        text-align: center;
        line-height: 1.6;
    }

    .chart-type-option-btn {
        border-color: #e5e7eb;
        color: #6b7280;
        background: #fff;
    }
    .chart-type-option-btn:hover {
        border-color: #9ca3af;
        color: #374151;
    }
    .chart-type-option-btn.active {
        border-color: #0ea5e9;
        background: #f0f9ff;
        color: #0284c7;
    }

    /* ══════════════════════════════════
       FOOTER CARD
    ══════════════════════════════════ */
    .chart-footer {
        margin-top: 22px;
        padding-top: 16px;
        border-top: 1px solid #f3f4f6;
        text-align: center;
    }
    .chart-footer-klasifikasi {
        font-size: 11px;
        color: var(--clr-text-muted);
        margin-bottom: 2px;
    }
    .chart-footer-produsen {
        font-size: 13px;
        font-weight: 700;
        color: var(--clr-text-secondary);
        margin-top: 3px;
    }

    @media (max-width: 640px) {
        .chart-card { padding: 16px 14px; }

        /* Header stack vertikal di mobile */
        .chart-header-inner {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        /* Dropdown tidak absolute di mobile */
        .chart-type-wrap {
            position: static;
            margin-top: 0;
            z-index: 50;
        }

        /* Y-label lebih kecil */
        .y-label-col { width: 24px; }
        .y-label-text { font-size: 9px; }

        /* Canvas lebih pendek di mobile */
        .canvas-col { min-height: 220px; }
    }
</style>

{{-- ══════════════════════════════════════════════════
     MARKUP
══════════════════════════════════════════════════ --}}
<div class="grafik-page px-2 py-6">

    <div class="px-2 pb-2 font-medium text-md text-gray-500 hover:text-gray-800 flex items-center gap-2">
        <button onclick="goBack()"
                class="pe-2 pb-3 font-medium text-md text-sky-500 hover:text-sky-600 flex items-center">
            <i class="fas fa-chevron-left"></i> Kembali
        </button>
    </div>

    <div class="chart-card">


        {{-- ── Header: Nama Metadata — Nama Wilayah — Frekuensi ── --}}
        <div class="chart-header">
            <h1 class="text-xl font-bold pb-3">Grafik</h1>

            <div class="chart-header-inner flex items-start justify-between gap-3">
                {{-- Kiri: judul & badge --}}
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-800 leading-snug">
                        {{ $metadata->nama ?? '—' }} di
                        {{ $location->nama_wilayah ?? 'Semua Wilayah' }}
                        <span class="font-medium text-gray-500">—</span>
                        <span class="font-medium text-gray-500">{{ ucfirst($metadata->frekuensi_penerbitan ?? '—') }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-medium text-gray-300 mt-2">
                        <span><i class="fas fa-tag"></i> {{ $metadata->klasifikasi?->nama_klasifikasi ?? '—' }}</span>
                        <span><i class="fas fa-map-marker-alt"></i> {{ $location->nama_wilayah ?? 'Semua Wilayah' }}</span>
                        <span><i class="fas fa-ruler-horizontal"></i> {{ $metadata->satuan_data ?? '—' }}</span>
                    </div>
                </div>

                {{-- Kanan: toggle Garis / Batang --}}
                <div class="flex gap-1 shrink-0">
                    <button type="button" id="btnGaris"
                            onclick="ChartTypeMenu.select('line')"
                            class="chart-type-option-btn active inline-flex items-center gap-1.5
                                px-3 py-1.5 text-xs font-semibold border rounded-lg transition-all">
                        <i class="fas fa-chart-line text-[11px]"></i> Garis
                    </button>
                    <button type="button" id="btnBatang"
                            onclick="ChartTypeMenu.select('bar')"
                            class="chart-type-option-btn inline-flex items-center gap-1.5
                                px-3 py-1.5 text-xs font-semibold border rounded-lg transition-all">
                        <i class="fas fa-chart-bar text-[11px]"></i> Batang
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Dropdown Jenis Grafik ── --}}
        

        {{-- ── Area Grafik ── --}}
        <div class="chart-area">
            <div class="y-label-col">
                <span class="y-label-text">Satuan {{ $metadata->satuan_data ?? 'Nilai' }}</span>
            </div>
            <div class="canvas-col">

                {{-- Skeleton (ditampilkan saat loading) --}}
                <div id="skeletonEl">
                    <div class="skeleton-chart">
                        <div class="sk-col skeleton-bar" style="height:38%;"></div>
                        <div class="sk-col skeleton-bar" style="height:54%;"></div>
                        <div class="sk-col skeleton-bar" style="height:67%;"></div>
                        <div class="sk-col skeleton-bar" style="height:78%;"></div>
                        <div class="sk-col skeleton-bar" style="height:91%;"></div>
                    </div>
                </div>

                {{-- Empty State --}}
                <div class="chart-empty" id="emptyEl" style="display:none;">
                    <svg width="64" height="56" viewBox="0 0 64 56" fill="none">
                        <rect x="2"  y="32" width="12" height="22" rx="3" fill="#cbd5e1"/>
                        <rect x="18" y="20" width="12" height="34" rx="3" fill="#cbd5e1"/>
                        <rect x="34" y="26" width="12" height="28" rx="3" fill="#e2e8f0"/>
                        <rect x="50" y="10" width="12" height="44" rx="3" fill="#e2e8f0"/>
                        <line x1="0" y1="54" x2="64" y2="54" stroke="#e2e8f0" stroke-width="1.5"/>
                    </svg>
                    <p>Belum ada data tersedia<br>untuk metadata dan wilayah ini.</p>
                </div>

                {{-- Canvas Chart --}}
                <canvas id="mainChart" style="display:none; width:100%; height:100%;"></canvas>

            </div>
        </div>

        {{-- Label Sumbu X --}}
        <div class="x-axis-row">
            <span class="x-label-text">Waktu</span>
        </div>

        {{-- ── Footer: Klasifikasi + Nama Produsen ── --}}
        <div class="chart-footer">
            <div class="font-medium text-sm text-gray-300">
                {{ $metadata->klasifikasi?->nama_klasifikasi ?? '—' }}
            </div>
            <div class="font-medium text-sm text-gray-500">
                {{ $rujukan->nama_produsen ?? '—' }}
            </div>
        </div>

    </div>{{-- /chart-card --}}

</div>{{-- /grafik-page --}}

{{-- ══════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════ --}}
<script>
'use strict';

/* ════════════════════════════════════════════════════
   CONFIG — injeksi nilai dari PHP/Blade
════════════════════════════════════════════════════ */
const APP_CONFIG = {
    metaId:    {{ $metadata->metadata_id ?? 'null' }},
    locId:     {{ $location->location_id ?? 'null' }},
    frekuensi: '{{ strtolower($metadata->frekuensi_penerbitan ?? "tahunan") }}',
    satuan:    '{{ addslashes($metadata->satuan_data ?? "") }}',
    metaNama:  '{{ addslashes($metadata->nama ?? "") }}',
    fetchUrl:  '{{ route("template.fetch_data") }}',
    csrf:      '{{ csrf_token() }}',
};

/* ════════════════════════════════════════════════════
   UI — manipulasi state DOM
════════════════════════════════════════════════════ */
const UI = {
    setSkeleton(visible) {
        document.getElementById('skeletonEl').style.display = visible ? 'block' : 'none';
        if (visible) { this.setEmpty(false); this.setCanvas(false); }
    },
    setEmpty(visible) {
        document.getElementById('emptyEl').style.display = visible ? 'flex' : 'none';
        if (visible) {
            this.setCanvas(false);
            document.getElementById('skeletonEl').style.display = 'none';
        }
    },
    setCanvas(visible) {
        document.getElementById('mainChart').style.display = visible ? 'block' : 'none';
    },
};

/* ════════════════════════════════════════════════════
   CHART TYPE MENU — dropdown pilih jenis grafik
════════════════════════════════════════════════════ */
const ChartTypeMenu = {
    toggle(e) {
        e.stopPropagation();
        const menu    = document.getElementById('typeMenu');
        const trigger = document.getElementById('typeTrigger');
        const isOpen  = menu.classList.toggle('open');
        trigger.classList.toggle('open', isOpen);
        trigger.setAttribute('aria-expanded', isOpen);
    },

    close() {
        document.getElementById('typeMenu').classList.remove('open');
        const trigger = document.getElementById('typeTrigger');
        trigger.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    },

    select(type) {
        const isBar = type === 'bar';
        document.getElementById('btnGaris').classList.toggle('active', !isBar);
        document.getElementById('btnBatang').classList.toggle('active', isBar);
        DataChart.setType(type);
    },
};

/* ════════════════════════════════════════════════════
   TIME HELPER — transformasi data ke format Chart.js
════════════════════════════════════════════════════ */
const TimeHelper = {
    /**
     * Hasilkan sort-key numerik agar data bisa diurutkan ascending.
     */
    sortKey(pt) {
        const t = pt.time || {};
        switch (APP_CONFIG.frekuensi) {
            case 'dekade':   return t.decade   || 0;
            case 'tahunan':  return t.year     || 0;
            case 'semester': return (t.year || 0) * 10  + (t.semester || 0);
            case 'kuartal':  return (t.year || 0) * 100 + (t.quarter  || 0);
            case 'bulanan':  return (t.year || 0) * 100 + (t.month    || 0);
            default:         return 0;
        }
    },

    /**
     * Bangun label sumbu X dari objek time.
     */
    makeLabel(t) {
        switch (APP_CONFIG.frekuensi) {
            case 'dekade':
            case 'tahunan':  return String(t.year || t.decade || '—');
            case 'semester': return `${t.year} S${t.semester}`;
            case 'kuartal':  return `${t.year} Q${t.quarter}`;
            case 'bulanan':  return `${String(t.month).padStart(2, '0')}/${t.year}`;
            default:         return '—';
        }
    },

    /**
     * Ubah array data dari server → { labels[], values[] }.
     * - Diurutkan ascending berdasarkan waktu
     * - Field nilai: `number_value` (sesuai kolom tabel `data`)
     */
    transform(data) {
        const sorted = [...data].sort((a, b) => this.sortKey(a) - this.sortKey(b));
        return {
            labels: sorted.map(pt => this.makeLabel(pt.time || {})),
            values: sorted.map(pt => {
                const v = pt.number_value;
                return (v !== null && v !== undefined) ? parseFloat(v) : null;
            }),
        };
    },
};

/* ════════════════════════════════════════════════════
   DATA CHART — fetch + render Chart.js
════════════════════════════════════════════════════ */
const DataChart = {
    _type: 'line',
    _inst: null,   // instance Chart.js aktif
    _data: [],     // cache data dari server

    /** Ganti jenis grafik; re-render otomatis jika data sudah ada */
    setType(type) {
        this._type = type;
        if (this._inst && this._data.length) this._render();
    },

    /**
     * Ambil SEMUA data dari server (tanpa filter waktu),
     * lalu render langsung ke canvas.
     */
    async load() {
        if (APP_CONFIG.metaId === null || APP_CONFIG.metaId === undefined ||
            APP_CONFIG.locId  === null || APP_CONFIG.locId  === undefined) {
            UI.setSkeleton(false);
            UI.setEmpty(true);
            return;
        }

        UI.setSkeleton(true);

        try {
            this._data = await this._fetch();
            UI.setSkeleton(false);

            if (!this._data.length) {
                UI.setEmpty(true);
            } else {
                this._render();
            }
        } catch (err) {
            console.error('[DataChart] Load error:', err);
            UI.setSkeleton(false);
            UI.setEmpty(true);
        }
    },

    /**
     * HTTP GET ke endpoint fetch_data.
     * per_page=9999 untuk mengambil semua data yang tersedia.
     */
    async _fetch() {
        const params = new URLSearchParams({
            metadata_id: APP_CONFIG.metaId,
            location_id: APP_CONFIG.locId,
            frekuensi:   APP_CONFIG.frekuensi,
            per_page:    9999,
        });

        const res = await fetch(`${APP_CONFIG.fetchUrl}?${params.toString()}`, {
            headers: {
                'X-CSRF-TOKEN': APP_CONFIG.csrf,
                'Accept':       'application/json',
            },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Respons tidak valid dari server.');

        return json.data || [];
    },

    /** Render / re-render instance Chart.js */
    _render() {
        const { labels, values } = TimeHelper.transform(this._data);

        if (!labels.length || values.every(v => v === null)) {
            UI.setEmpty(true);
            return;
        }

        UI.setEmpty(false);
        UI.setCanvas(true);

        // Destroy instance lama sebelum membuat yang baru
        if (this._inst) { this._inst.destroy(); this._inst = null; }

        const isBar = this._type === 'bar';

        this._inst = new Chart(
            document.getElementById('mainChart').getContext('2d'),
            {
                type:    isBar ? 'bar' : 'line',
                data:    { labels, datasets: [this._buildDataset(values, isBar)] },
                options: this._buildOptions(isBar),
            }
        );
    },

    /** Dataset Chart.js */
    _buildDataset(values, isBar) {
        return {
            label:                APP_CONFIG.metaNama,
            data:                 values,

            // Bar
            backgroundColor:      isBar ? 'rgba(203,213,225,0.9)' : 'rgba(55,65,81,0.07)',
            borderColor:          isBar ? 'transparent'            : '#374151',
            borderWidth:          isBar ? 0   : 2.2,
            borderRadius:         isBar ? 4   : 0,
            borderSkipped:        false,

            // Line
            pointBackgroundColor: '#374151',
            pointBorderColor:     '#ffffff',
            pointBorderWidth:     2,
            pointRadius:          isBar ? 0 : 4,
            pointHoverRadius:     isBar ? 0 : 6,
            tension:              0.28,
            fill:                 !isBar,
            spanGaps:             false,
        };
    },

    /** Opsi Chart.js */
    _buildOptions(isBar) {
        const satuan = APP_CONFIG.satuan;
        return {
            responsive:          true,
            maintainAspectRatio: false,
            interaction:         { mode: 'index', intersect: false },

            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor:      '#94a3b8',
                    bodyColor:       '#f1f5f9',
                    padding:         10,
                    cornerRadius:    8,
                    displayColors:   false,
                    callbacks: {
                        label: ctx => {
                            const v = ctx.parsed.y;
                            if (v === null || v === undefined) return '  Belum ada data';
                            return `  ${v.toLocaleString('id-ID')}${satuan ? ' ' + satuan : ''}`;
                        },
                    },
                },
            },

            scales: {
                x: {
                    grid:   { display: false },
                    border: { color: '#e5e7eb' },
                    ticks: {
                        color:       '#9ca3af',
                        font:        { size: 11, weight: '500' },
                        maxRotation: 45,
                        minRotation: 0,
                    },
                },
                y: {
                    grid:        { color: '#f3f4f6', lineWidth: 1 },
                    border:      { dash: [3, 3], color: 'transparent' },
                    beginAtZero: true,   // ← diubah dari false ke true
                    ticks: {
                        color: '#9ca3af',
                        font:  { size: 11 },
                        callback: v => v !== null ? v.toLocaleString('id-ID') : '',
                    },
                },
            },

            // Label nilai di atas setiap bar
            animation: {
                onComplete() {
                    if (!isBar) return;
                    const { ctx: c, data: cd } = this;
                    const meta = this.getDatasetMeta(0);
                    c.save();
                    c.font         = 'bold 11px sans-serif';
                    c.fillStyle    = '#374151';
                    c.textAlign    = 'center';
                    c.textBaseline = 'bottom';
                    meta.data.forEach((bar, i) => {
                        const v = cd.datasets[0].data[i];
                        if (v === null || v === undefined) return;
                        c.fillText(v.toLocaleString('id-ID'), bar.x, bar.y - 4);
                    });
                    c.restore();
                },
            },
        };
    },
};

/* ════════════════════════════════════════════════════
   INIT — load data otomatis saat halaman dibuka
════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {

    DataChart.load();

    // Tutup dropdown saat klik di luar area
    document.addEventListener('click', e => {
        if (!document.querySelector('.chart-type-wrap')?.contains(e.target)) {
            ChartTypeMenu.close();
        }
    });

    // Tutup dropdown dengan Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') ChartTypeMenu.close();
    });
});

// Tambahkan fungsi ini di akhir script sebelum DOMContentLoaded
function goBack() {
    // Simpan state jika masih diperlukan
    sessionStorage.setItem('grafikState', JSON.stringify({
        metaId: APP_CONFIG.metaId,
        locId: APP_CONFIG.locId,
        metaNama: APP_CONFIG.metaNama,
        locNama: '{{ addslashes($location->nama_wilayah ?? "") }}'
    }));

    // Jika ada halaman sebelumnya, kembali ke sana
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // fallback jika tidak ada history
        window.location.href = `{{ route("template.create") }}`;
    }
}
</script>

@endsection