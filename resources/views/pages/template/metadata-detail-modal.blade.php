{{-- ── MODAL OVERLAY ─────────────────────────────────────── --}}
<div id="metadataDetailModal"
     class="fixed inset-0 z-50 hidden"
     aria-modal="true" role="dialog" aria-labelledby="mdModalTitle">

    {{-- Backdrop --}}
    <div id="mdModalBackdrop"
         class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"
         onclick="closeMetadataModal()"></div>

    {{-- Panel --}}
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-2xl bg-white rounded-2xl shadow-2xl
                    flex flex-col max-h-[90vh] overflow-hidden
                    transform transition-all duration-200"
             id="mdModalPanel">

            {{-- ── Header ──────────────────────────────────── --}}
            <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-100">
                <div class="flex-1 min-w-0">
                    {{-- Badge status --}}
                    <div class="flex items-center gap-2 mb-2">
                        <span id="mdBadgeStatus"
                              class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5
                                     rounded-full bg-emerald-100 text-emerald-700 uppercase tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                            Active
                        </span>
                        <span id="mdBadgeKlasifikasi"
                              class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5
                                     rounded-full bg-sky-100 text-sky-700">
                            —
                        </span>
                    </div>
                    <h2 id="mdModalTitle"
                        class="text-base font-bold text-gray-900 leading-snug line-clamp-2">
                        Memuat…
                    </h2>
                    <p id="mdAlias" class="text-xs text-gray-400 mt-0.5 truncate"></p>
                </div>
                <button type="button" onclick="closeMetadataModal()"
                        class="flex-shrink-0 w-8 h-8 flex items-center justify-center
                               rounded-full text-gray-400 hover:text-gray-600
                               hover:bg-gray-100 transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- ── Pill info bar ────────────────────────────── --}}
            <div class="flex flex-wrap gap-2 px-6 py-3 bg-gray-50 border-b border-gray-100">
                <span class="md-pill" id="mdPillSatuan">
                    <i class="fas fa-ruler-combined text-sky-400"></i>
                    <span id="mdSatuan">—</span>
                </span>
                <span class="md-pill" id="mdPillFrekuensi">
                    <i class="fas fa-calendar-alt text-sky-400"></i>
                    <span id="mdFrekuensi">—</span>
                </span>
                <span class="md-pill" id="mdPillTipe">
                    <i class="fas fa-database text-sky-400"></i>
                    <span id="mdTipe">—</span>
                </span>
            </div>
 
            <div id="mdSubNamaWrap" class="hidden px-6 py-4 bg-slate-50 border-t border-gray-100">
                <p class="md-section-label">Sub Nama Metadata</p>
                <div id="mdSubNamaList" class="space-y-2 text-sm text-slate-700"></div>
            </div>
 
            {{-- ── Body (scrollable) ───────────────────────── --}}
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                {{-- Skeleton loading --}}
                <div id="mdSkeleton" class="space-y-4 animate-pulse">
                    <div class="h-3 bg-gray-100 rounded w-1/3"></div>
                    <div class="h-12 bg-gray-100 rounded w-full"></div>
                    <div class="h-3 bg-gray-100 rounded w-1/3 mt-4"></div>
                    <div class="h-8 bg-gray-100 rounded w-full"></div>
                    <div class="h-3 bg-gray-100 rounded w-1/4 mt-4"></div>
                    <div class="h-6 bg-gray-100 rounded w-2/3"></div>
                </div>

                {{-- Content (hidden while loading) --}}
                <div id="mdContent" class="hidden space-y-5">

                    <div>
                        <p class="md-section-label">Informasi Metadata</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach([
                                'Nama Data' => 'mdNamaData',
                                'Konsep' => 'mdKonsep',
                                'Definisi' => 'mdDefinisi',
                                'Metodologi' => 'mdMetodologi',
                                'Penjelasan Metode' => 'mdPenjelasanMetode',
                                'Produsen Data' => 'mdProdusenData',
                                'Tipe Data' => 'mdTipeData',
                                'Frekuensi Penerbitan' => 'mdFrekuensiPenerbitan',
                                'Wilayah (Lokasi Data)' => 'mdWilayahData',
                                'Satuan' => 'mdSatuanData',
                                'Klasifikasi' => 'mdKlasifikasiData',
                                'Asumsi' => 'mdAsumsi',
                                'Rujukan' => 'mdRujukan',
                            ] as $label => $fieldId)
                                <div class="md-info-card">
                                    <p class="md-info-label">{{ $label }}</p>
                                    <p id="{{ $fieldId }}" class="md-info-value whitespace-pre-line">—</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Grid info --}}
                    <div class="grid grid-cols-2 gap-3 pt-1">

                        <div class="md-info-card">
                            <p class="md-info-label">
                                <i class="fas fa-clock text-sky-400 mr-1"></i>
                                Tahun Mulai Data
                            </p>
                            <p id="mdTahunMulai" class="md-info-value">—</p>
                        </div>

                        <div class="md-info-card">
                            <p class="md-info-label">
                                <i class="fas fa-layer-group text-sky-400 mr-1"></i>
                                Tahun Data Tersedia
                            </p>
                            <p id="mdTahunTersedia" class="md-info-value">—</p>
                        </div>

                        <div class="md-info-card">
                            <p class="md-info-label">
                                <i class="fas fa-paper-plane text-sky-400 mr-1"></i>
                                Waktu Rilis
                            </p>
                            <p id="mdWaktuRilis" class="md-info-value">—</p>
                        </div>

                    </div>

                    {{-- Tag --}}
                    <div id="mdTagWrap" class="hidden">
                        <p class="md-section-label">Tag</p>
                        <div id="mdTags" class="flex flex-wrap gap-1.5 mt-1"></div>
                    </div>

                </div>

                {{-- Error state --}}
                <div id="mdError"
                     class="hidden flex flex-col items-center gap-3 py-10 text-gray-400">
                    <i class="fas fa-exclamation-circle text-3xl text-red-300"></i>
                    <p class="text-sm text-gray-500">Gagal memuat detail metadata.</p>
                    <p class="text-xs">Coba tutup dan buka kembali.</p>
                </div>
            </div>

            {{-- ── Footer ──────────────────────────────────── --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
                <p class="text-[10px] text-gray-400" id="mdFooterInfo"></p>
                <div class="flex gap-2">
                    <button type="button" onclick="closeMetadataModal()"
                            class="px-4 py-2 text-xs font-semibold border border-gray-200
                                   text-gray-500 rounded-lg hover:bg-gray-50 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     STYLES untuk modal
══════════════════════════════════════════════════════════ --}}
<style>
    /* ── Pill info ── */
    .md-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.625rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 500;
        color: #374151;
        white-space: nowrap;
    }

    /* ── Section label ── */
    .md-section-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #9ca3af;
        margin-bottom: 0.375rem;
    }

    /* ── Section body text ── */
    .md-section-body {
        font-size: 0.8125rem;
        color: #374151;
        line-height: 1.65;
    }

    /* ── Info card ── */
    .md-info-card {
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
    }
    .md-info-label {
        font-size: 0.68rem;
        font-weight: 600;
        color: #9ca3af;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .md-info-value {
        font-size: 0.875rem;
        font-weight: 700;
        color: #1f2937;
    }

    /* ── Modal enter animation ── */
    #metadataDetailModal:not(.hidden) #mdModalPanel {
        animation: mdSlideIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    @keyframes mdSlideIn {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to   { opacity: 1; transform: scale(1)    translateY(0); }
    }

    /* ── Tag chip ── */
    .md-tag-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.625rem;
        background: #ede9fe;
        color: #6d28d9;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
    }
</style>

{{-- ══════════════════════════════════════════════════════════
     JAVASCRIPT untuk modal
══════════════════════════════════════════════════════════ --}}
<script>
// ─── URL endpoint detail metadata ─────────────────────────────
// Pastikan route 'metadata.detail_api' sudah didaftarkan di routes/web.php:
//   Route::get('/metadata/{id}/detail-api', [MetadataController::class, 'detailApi'])
//       ->name('metadata.detail_api');
const MD_DETAIL_API_BASE = '{{ url("metadata") }}';
const MD_DETAIL_PAGE_BASE = '{{ url("metadata") }}';

// ─── Buka modal ───────────────────────────────────────────────
async function openMetadataModal(metadataId) {
    const modal = document.getElementById('metadataDetailModal');

    // Reset state
    document.getElementById('mdSkeleton').classList.remove('hidden');
    document.getElementById('mdContent').classList.add('hidden');
    document.getElementById('mdError').classList.add('hidden');
    document.getElementById('mdModalTitle').textContent = 'Memuat…';
    document.getElementById('mdAlias').textContent = '';
    document.getElementById('mdFooterInfo').textContent = '';

    // Tampilkan modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    try {
        const res  = await fetch(`${MD_DETAIL_API_BASE}/${metadataId}/detail-api`);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const d = await res.json();

        _populateModal(d);

    } catch (e) {
        console.error('Gagal memuat detail metadata:', e);
        document.getElementById('mdSkeleton').classList.add('hidden');
        document.getElementById('mdError').classList.remove('hidden');
    }
}

// ─── Isi modal dengan data ─────────────────────────────────────
function _populateModal(d) {
    // Header
    document.getElementById('mdModalTitle').textContent = d.nama ?? '—';
    document.getElementById('mdAlias').textContent      = d.alias ?? '';

    // Badge status
    const statusBadge = document.getElementById('mdBadgeStatus');
    if (d.status === 2) {
        statusBadge.className = statusBadge.className.replace(/bg-\w+-100 text-\w+-700/g, 'bg-emerald-100 text-emerald-700');
        statusBadge.querySelector('span').className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block';
    } else {
        statusBadge.className = statusBadge.className.replace(/bg-\w+-100 text-\w+-700/g, 'bg-gray-100 text-gray-500');
        statusBadge.querySelector('span').className = 'w-1.5 h-1.5 rounded-full bg-gray-400 inline-block';
        statusBadge.childNodes[1].textContent = ' Nonaktif';
    }

    // Badge klasifikasi
    document.getElementById('mdBadgeKlasifikasi').textContent =
        d.klasifikasi ?? '—';

    // Pill bar
    document.getElementById('mdSatuan').textContent    = d.satuan_data    ?? '—';
    document.getElementById('mdFrekuensi').textContent = d.frekuensi_penerbitan ?? '—';
    document.getElementById('mdTipe').textContent      = d.tipe_data      ?? '—';
 
    const subNamaWrap = document.getElementById('mdSubNamaWrap');
    const subNamaList = document.getElementById('mdSubNamaList');
    if (d.sub_nama_metadata && typeof d.sub_nama_metadata === 'object' && Object.keys(d.sub_nama_metadata).length) {
        subNamaList.innerHTML = Object.entries(d.sub_nama_metadata)
            .map(([satuanId, label]) =>
                `<div class="rounded-2xl bg-white border border-slate-200 px-3 py-2">
                    <span class="font-semibold text-slate-800">Satuan ${_mdEsc(satuanId)}:</span>
                    <span class="text-slate-700"> ${_mdEsc(label)}</span>
                 </div>`
            ).join('');
        subNamaWrap.classList.remove('hidden');
    } else {
        subNamaWrap.classList.add('hidden');
    }
 
    const metadataValues = {
        mdNamaData: d.nama,
        mdKonsep: d.konsep,
        mdDefinisi: d.definisi,
        mdMetodologi: d.metodologi,
        mdPenjelasanMetode: d.penjelasan_metodologi,
        mdProdusenData: d.produsen,
        mdTipeData: d.tipe_data,
        mdFrekuensiPenerbitan: d.frekuensi_penerbitan,
        mdWilayahData: d.wilayah_data,
        mdSatuanData: d.satuan_data,
        mdKlasifikasiData: d.klasifikasi,
        mdAsumsi: d.asumsi,
        mdRujukan: d.rujukan,
    };
    Object.entries(metadataValues).forEach(([elementId, value]) => {
        const displayValue = Array.isArray(value) ? value.join('\n') : value;
        document.getElementById(elementId).textContent =
            displayValue && String(displayValue).trim() ? displayValue : '—';
    });

    // Grid info
    document.getElementById('mdTahunMulai').textContent    = d.tahun_mulai_data     ?? '—';
    document.getElementById('mdTahunTersedia').textContent = d.tahun_data_tersedia  ?? '—';

    // Waktu rilis: bulan + tahun_pertama_rilis
    const bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni',
                       'Juli','Agustus','September','Oktober','November','Desember'];
    const bln  = d.bulan_pertama_rilis ? (bulanNama[d.bulan_pertama_rilis] ?? '—') : null;
    const thn  = d.tahun_pertama_rilis ?? null;
    document.getElementById('mdWaktuRilis').textContent =
        [bln, thn].filter(Boolean).join(' ') || '—';

    // Tags
    const tagWrap = document.getElementById('mdTagWrap');
    const tagContainer = document.getElementById('mdTags');
    if (d.tag) {
        const tags = String(d.tag).split(/[,;|]+/).map(t => t.trim()).filter(Boolean);
        if (tags.length) {
            tagContainer.innerHTML = tags.map(t =>
                `<span class="md-tag-chip">${_mdEsc(t)}</span>`
            ).join('');
            tagWrap.classList.remove('hidden');
        } else {
            tagWrap.classList.add('hidden');
        }
    } else {
        tagWrap.classList.add('hidden');
    }

    // Footer info & link detail
    const inputedAt = d.date_inputed
        ? new Date(d.date_inputed).toLocaleDateString('id-ID', { dateStyle: 'medium' })
        : null;

    // Tampilkan konten
    document.getElementById('mdSkeleton').classList.add('hidden');
    document.getElementById('mdContent').classList.remove('hidden');
}

// ─── Tutup modal ──────────────────────────────────────────────
function closeMetadataModal() {
    document.getElementById('metadataDetailModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Tutup dengan Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMetadataModal();
});

// ─── Escape helper ────────────────────────────────────────────
function _mdEsc(str) {
    const d = document.createElement('div');
    d.innerText = str ?? '';
    return d.innerHTML;
}
</script>