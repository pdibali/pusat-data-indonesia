@extends('layouts.main')

@section('title', 'Riwayat Transaksi')

@section('content')
<div class="page-layout">

    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Riwayat Berlangganan</h1>
            <p class="text-sm text-gray-500 mt-0.5">Semua transaksi dan langganan kamu</p>
        </div>
    </div>

    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Cari transaksi aktif milik user --}}
    @php
        $aktifNow = $transaksis->first(fn($t) => $t->isSuccess() && $t->isAktif());
        if (! $aktifNow) {
            $aktifNow = \App\Models\Transaksi::where('user_id', Auth::id())
                ->where('status', 'success')
                ->where(function ($q) {
                    $q->whereNull('aktif_sampai')
                    ->orWhere('aktif_sampai', '>=', now());
                })
                ->latest('aktif_mulai')
                ->first();
        }
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Paket Aktif --}}
        <div class="card-panel p-4 h-full self-stretch flex flex-col gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                <i class="fas fa-gift text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Paket Aktif Saat Ini</p>
                @if($aktifNow)
                    <p class="text-base font-bold text-gray-800 mt-0.5 leading-tight">{{ $aktifNow->nama_layanan }}</p>
                @else
                    <p class="text-base font-bold text-gray-400 mt-0.5">Tidak ada</p>
                @endif
            </div>
        </div>

        {{-- Masa Berlaku --}}
        <div class="card-panel p-4 h-full self-stretch flex flex-col gap-3">
            <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center">
                <i class="fas fa-calendar-alt text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Berlaku Sampai</p>
                @if($aktifNow)
                    <p class="text-base font-bold text-gray-800 mt-0.5 truncate">
                        {{ $aktifNow->aktif_sampai ? $aktifNow->aktif_sampai->translatedFormat('d M Y') : 'Selamanya' }}
                    </p>
                    <p class="text-[11px] text-gray-400 mt-0.5 leading-tight">
                        Sejak {{ $aktifNow->aktif_mulai?->translatedFormat('d M Y') ?? '—' }}
                    </p>
                @else
                    <p class="text-base font-bold text-gray-400 mt-0.5">—</p>
                @endif
            </div>
        </div>

        {{-- Status Paket --}}
        <div class="card-panel p-4 h-full self-stretch flex flex-col gap-3">
            <div class="w-9 h-9 rounded-full {{ $aktifNow ? 'bg-emerald-50 text-emerald-500' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                <i class="fas fa-shield-alt text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Status Paket</p>
                <p class="text-base font-bold mt-0.5 {{ $aktifNow ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $aktifNow ? 'Aktif' : 'Tidak Aktif' }}
                </p>
            </div>
        </div>

        {{-- Sisa Waktu --}}
        @php
            $sisaHari = ($aktifNow && $aktifNow->aktif_sampai) ? (int) now()->diffInDays($aktifNow->aktif_sampai, false) : null;
            $urgent = $sisaHari !== null && $sisaHari <= 3;
        @endphp
        <div class="card-panel p-4 h-full self-stretch flex flex-col gap-3">
            <div class="w-9 h-9 rounded-full {{ $urgent ? 'bg-red-50 text-red-500' : 'bg-amber-50 text-amber-500' }} flex items-center justify-center">
                <i class="fas fa-clock text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500">Sisa Waktu</p>
                @if($aktifNow && $aktifNow->aktif_sampai)
                    <p class="text-base font-bold mt-0.5 {{ $urgent ? 'text-red-500' : 'text-gray-800' }}">
                        {{ $sisaHari > 0 ? $sisaHari . ' hari lagi' : 'Hari ini berakhir' }}
                    </p>
                @elseif($aktifNow)
                    <p class="text-base font-bold text-gray-800 mt-0.5">Selamanya</p>
                @else
                    <p class="text-base font-bold text-gray-400 mt-0.5">—</p>
                @endif
            </div>
        </div>
    </div>

    @if(!$aktifNow)
    <div class="card-panel bg-yellow-50 border border-yellow-200 px-4 py-3 flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-sm"></i>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-yellow-800">Tidak ada langganan aktif</p>
            <p class="text-xs text-yellow-700">Kamu belum memiliki paket langganan yang aktif saat ini. Yuk berlangganan untuk mengakses fitur lengkap.</p>
        </div>
        <a href="{{ route('langganan') }}"
           class="ml-auto flex-shrink-0 text-xs font-medium text-yellow-800 hover:text-yellow-900 hover:underline whitespace-nowrap">
            Lihat Paket
        </a>
    </div>
    @endif

    <div class="card-panel">
        <div class="card-panel">
        <div class="p-4 border-b border-gray-100">
            <form id="form-riwayat" method="GET" action="{{ route('transaksi.riwayat') }}">
                <div class="flex flex-wrap items-center gap-2">
                    <select name="status" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="success"   {{ request('status') === 'success'   ? 'selected' : '' }}>Berhasil</option>
                        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Menunggu</option>
                        <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Gagal</option>
                        <option value="expired"   {{ request('status') === 'expired'   ? 'selected' : '' }}>Kedaluwarsa</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>

                    @if(request()->filled('status'))
                        <a href="{{ route('transaksi.riwayat') }}"
                        class="inline-flex items-center gap-1 text-xs text-red-400 hover:text-red-600 transition">
                            <i class="fas fa-times-circle"></i> Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            @if($transaksis->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-receipt text-gray-400 text-xl"></i>
                </div>
                <p class="text-gray-500 text-sm font-medium">Belum ada transaksi</p>
                <p class="text-gray-400 text-xs mt-1">Mulai berlangganan untuk mengakses layanan.</p>
                <a href="{{ route('langganan') }}" class="btn-link mt-3 inline-block">
                    <i class="fas fa-store text-xs"></i> Lihat Layanan
                </a>
            </div>
            @else

            {{-- Tabel lengkap — tablet & desktop --}}
            <table class="w-full text-sm hidden sm:table">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-5 py-3 text-left font-medium">Order ID</th>
                        <th class="px-5 py-3 text-left font-medium">Layanan</th>
                        <th class="px-5 py-3 text-left font-medium">Harga</th>
                        <th class="px-5 py-3 text-left font-medium">Status Bayar</th>
                        <th class="px-5 py-3 text-left font-medium">Masa Aktif</th>
                        <th class="px-5 py-3 text-left font-medium">Tanggal</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transaksis as $item)
                    @php
                        // isExpired() true kalau: (a) status sudah literal 'expired' di DB
                        // (sudah disapu command), ATAU (b) masih 'pending' tapi created_at
                        // sudah lewat 24 jam (transisi sebelum sempat disapu). Kedua kasus
                        // sama-sama harus menyembunyikan tombol Bayar.
                        $isExpired = $item->isExpired();
                        $isPayable = $item->isPayable();
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $item->order_id }}</td>
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-800">{{ $item->nama_layanan }}</p>
                            <p class="text-xs text-gray-400">{{ $item->durasi_label }}</p>
                        </td>
                        <td class="px-5 py-3.5 font-medium text-gray-700">{{ $item->harga_format }}</td>
                        <td class="px-5 py-3.5">
                            @if($isPayable)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 inline-block"></span>
                                    Menunggu bayar
                                </span>
                            @elseif($isExpired)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                    Kedaluwarsa
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                    Tidak
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs text-gray-500">
                            @if($item->isSuccess())
                                @if($item->aktif_sampai)
                                    {{ $item->aktif_mulai?->format('d M Y') }} – {{ $item->aktif_sampai->format('d M Y') }}
                                    @if($item->isAktif())
                                        <span class="ml-1 text-green-600 font-medium">Aktif</span>
                                    @else
                                        <span class="ml-1 text-red-500 font-medium">Berakhir</span>
                                    @endif
                                @else
                                    <span class="text-green-600 font-medium">Selamanya</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs text-gray-500">{{ $item->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if($isPayable)
                                    <button type="button"
                                            onclick="openPaymentChoice({{ $item->transaksi_id }})"
                                            class="text-xs text-white bg-stikom-blue px-3 py-1.5 rounded-lg hover:bg-blue-700 font-medium">
                                        Bayar
                                    </button>
                                @endif
                                <button type="button"
                                        onclick="showDetail({{ $item->transaksi_id }})"
                                        class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                    Detail
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Card list — mobile --}}
            <div class="sm:hidden divide-y divide-gray-100">
                @foreach($transaksis as $item)
                @php
                    $isExpired = $item->isExpired();
                    $isPayable = $item->isPayable();
                    $statusDot = match(true) {
                        $item->status === 'success' => 'bg-emerald-500',
                        $isPayable                  => 'bg-yellow-400',
                        $isExpired                   => 'bg-gray-400',
                        $item->status === 'failed'   => 'bg-red-500',
                        default                      => 'bg-gray-400',
                    };
                @endphp
                <button type="button"
                        onclick="showDetail({{ $item->transaksi_id }})"
                        class="w-full flex items-center gap-3 px-4 py-3.5 text-left hover:bg-gray-50 active:bg-gray-100 transition">
                    <span class="w-2 h-2 rounded-full {{ $statusDot }} flex-shrink-0"></span>

                    <span class="min-w-0 flex-1">
                        <span class="block font-mono text-[11px] text-gray-400 truncate">{{ $item->order_id }}</span>
                        <span class="block font-medium text-gray-800 text-sm truncate">{{ $item->nama_layanan }}</span>
                        <span class="block text-sm font-semibold text-gray-700 mt-0.5">{{ $item->harga_format }}</span>
                        @if($isExpired)
                            <span class="block text-[11px] text-gray-400 mt-0.5">Kedaluwarsa</span>
                        @endif
                    </span>

                    @if($isPayable)
                        <span onclick="event.stopPropagation(); openPaymentChoice({{ $item->transaksi_id }})"
                            class="flex-shrink-0 text-xs text-white bg-stikom-blue px-2.5 py-1.5 rounded-lg font-medium mr-1">
                            Bayar
                        </span>
                    @endif

                    <span class="flex items-center gap-1 text-xs text-blue-600 flex-shrink-0">
                        Detail <i class="fas fa-chevron-right text-[10px]"></i>
                    </span>
                </button>
                @endforeach
            </div>

            @endif
        </div>

        @if($transaksis->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-sm text-gray-500">
            <span class="hidden sm:inline text-xs sm:text-sm">
                Menampilkan {{ $transaksis->firstItem() }}–{{ $transaksis->lastItem() }} dari {{ $transaksis->total() }} data
            </span>

            <nav class="flex items-center justify-center sm:justify-end gap-1.5">
                @if($transaksis->onFirstPage())
                    <span class="w-9 h-9 rounded-full border border-gray-200 text-gray-300 flex items-center justify-center cursor-not-allowed">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </span>
                @else
                    <a href="{{ $transaksis->previousPageUrl() }}"
                    class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                @endif

                @php
                    $current = $transaksis->currentPage();
                    $last    = $transaksis->lastPage();
                @endphp

                <a href="{{ $transaksis->url(1) }}"
                class="w-9 h-9 rounded-full flex items-center justify-center text-sm
                {{ $current == 1 ? 'bg-gray-900 text-white font-semibold' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    1
                </a>

                @if($current > 3)
                    <span class="w-9 h-9 flex items-center justify-center text-gray-400">…</span>
                @endif

                @for($i = max(2, $current - 1); $i <= min($last - 1, $current + 1); $i++)
                    <a href="{{ $transaksis->url($i) }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-sm
                    {{ $current == $i ? 'bg-gray-900 text-white font-semibold' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        {{ $i }}
                    </a>
                @endfor

                @if($current < $last - 2)
                    <span class="w-9 h-9 flex items-center justify-center text-gray-400">…</span>
                @endif

                @if($last > 1)
                    <a href="{{ $transaksis->url($last) }}"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-sm
                    {{ $current == $last ? 'bg-gray-900 text-white font-semibold' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        {{ $last }}
                    </a>
                @endif

                @if($transaksis->hasMorePages())
                    <a href="{{ $transaksis->nextPageUrl() }}"
                    class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                @else
                    <span class="w-9 h-9 rounded-full border border-gray-200 text-gray-300 flex items-center justify-center cursor-not-allowed">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </span>
                @endif
            </nav>
        </div>
        @endif
    </div>
</div>

{{-- MODAL PILIH METODE BAYAR --}}
<div id="modal-payment-choice"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
     onclick="closePaymentChoice(event)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" onclick="event.stopPropagation()">
        <h3 class="text-center text-gray-800 font-semibold text-base mb-1">Pilih Metode Pembayaran</h3>
        <p class="text-center text-gray-400 text-xs mb-5">
            Order ID: <span id="payment-choice-order" class="font-mono"></span>
        </p>

        <div class="space-y-2.5">
            @if(\App\Support\Setting::get('midtrans_enabled', true))
            <button type="button" id="btn-choose-midtrans" onclick="chooseMidtrans()"
                    class="w-full py-3.5 bg-stikom-blue hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition flex items-center justify-center gap-2">
                <i class="fas fa-credit-card"></i> Kartu / E-Wallet / VA (Midtrans)
            </button>
            @else
            <div class="w-full py-3.5 bg-gray-100 text-gray-400 text-sm font-semibold rounded-xl text-center">
                Fitur Pembayaran ini akan Segera hadir
            </div>
            @endif

            <button type="button" onclick="chooseManual()"
                    class="w-full py-3.5 border border-stikom-blue text-stikom-blue text-sm font-semibold rounded-xl hover:bg-blue-50 transition flex items-center justify-center gap-2">
                <i class="fas fa-university"></i> Transfer Bank Manual
            </button>
        </div>

        <button type="button" onclick="closePaymentChoice()"
                class="w-full mt-4 py-2 text-xs text-gray-400 hover:text-gray-600">
            Batal
        </button>
    </div>
</div>

{{-- Form tersembunyi untuk submit pilihan manual --}}
<form id="form-manual-choice" method="POST" action="">
    @csrf
</form>

{{-- MODAL DETAIL TRANSAKSI --}}
<div id="modal-detail"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
     onclick="closeDetail(event)">

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden"
         id="modal-card"
         onclick="event.stopPropagation()">

        <div id="modal-loading" class="flex flex-col items-center justify-center py-16">
            <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            <p class="text-sm text-gray-500 mt-3">Memuat detail...</p>
        </div>

        <div id="modal-content" class="hidden"></div>
    </div>
</div>

<script>
const transaksiData = {
    @foreach($transaksis as $item)
    @php
        $isExpiredJson = $item->isExpired();
        $isPayableJson = $item->isPayable();
        // 'expired' di sini murni label tampilan JS, tidak pernah jadi nilai kolom status di DB.
        $statusForJs = $isExpiredJson ? 'expired' : $item->status;
    @endphp
    {{ $item->transaksi_id }}: {
        id:           {{ $item->transaksi_id }},
        order_id:     "{{ $item->order_id }}",
        nama_layanan: "{{ e($item->nama_layanan) }}",
        harga_format: "{{ $item->harga_format }}",
        durasi_label: "{{ $item->durasi_label }}",
        status:       "{{ $statusForJs }}",
        is_payable:   {{ $isPayableJson ? 'true' : 'false' }},
        status_label: "{{ match($statusForJs) { 'success'=>'Berhasil','pending'=>'Menunggu','expired'=>'Kedaluwarsa','failed'=>'Gagal', default=>$statusForJs } }}",
        payment_type: "{{ $item->payment_type ? strtoupper(str_replace('_',' ',$item->payment_type)) : '—' }}",
        midtrans_id:  "{{ $item->midtrans_transaction_id ?? '—' }}",
        aktif_mulai:  "{{ $item->aktif_mulai ? $item->aktif_mulai->format('d M Y') : '—' }}",
        aktif_sampai: "{{ $item->aktif_sampai ? $item->aktif_sampai->format('d M Y') : ($item->isSuccess() ? 'Selamanya' : '—') }}",
        is_aktif:     {{ $item->isAktif() ? 'true' : 'false' }},
        is_success:   {{ $item->isSuccess() ? 'true' : 'false' }},
        created_at:   "{{ $item->created_at->format('d M Y, H:i') }}",
        updated_at:   "{{ $item->updated_at->format('d M Y, H:i') }}",
        snap_token:   "{{ $item->snap_token ? 'Ada' : '—' }}",
    },
    @endforeach
};
</script>

@push('scripts')
<script src="{{ config('midtrans.snap_url') }}"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>

    let currentPaymentTransaksiId = null;

    function openPaymentChoice(id) {
        currentPaymentTransaksiId = id;
        const d = transaksiData[id];
        document.getElementById('payment-choice-order').textContent = d ? d.order_id : '';
        document.getElementById('modal-payment-choice').classList.remove('hidden');
    }

    function closePaymentChoice(e) {
        if (e && e.target !== document.getElementById('modal-payment-choice')) return;
        document.getElementById('modal-payment-choice').classList.add('hidden');
    }

    function chooseMidtrans() {
        const id = currentPaymentTransaksiId;
        const btn = document.getElementById('btn-choose-midtrans');
        closePaymentChoice();
        bayarSekarang(id, btn);
    }

    function chooseManual() {
        const id = currentPaymentTransaksiId;
        if (!id) return;
        const form = document.getElementById('form-manual-choice');
        form.action = `/transaksi/${id}/pilih-manual`;
        form.submit();
    }

    function bayarSekarang(transaksiId, btnEl) {
        const btn = btnEl;
        const originalHtml = btn ? btn.innerHTML : null;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        fetch(`/transaksi/${transaksiId}/bayar-ulang`)
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    alert(data.message || 'Gagal memuat pembayaran.');
                    if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
                    if (data.message && data.message.includes('kedaluwarsa')) {
                        window.location.reload();
                    }
                    return;
                }

                snap.pay(data.snap_token, {
                    onSuccess: function () { window.location.reload(); },
                    onPending: function () { window.location.reload(); },
                    onError: function () {
                        alert('Pembayaran gagal. Silakan coba lagi.');
                        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
                    },
                    onClose: function () {
                        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
                    }
                });
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan.');
                if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            });
    }

    const statusCfg = {
        success:   { bg: 'bg-emerald-50',  text: 'text-emerald-700', dot: 'bg-emerald-500', label: 'Berhasil'   },
        pending:   { bg: 'bg-yellow-50',   text: 'text-yellow-700',  dot: 'bg-yellow-400',  label: 'Menunggu'   },
        expired:   { bg: 'bg-gray-100',    text: 'text-gray-600',    dot: 'bg-gray-400',     label: 'Kedaluwarsa' },
        failed:    { bg: 'bg-red-50',      text: 'text-red-700',     dot: 'bg-red-500',      label: 'Gagal'      },
        cancelled: { bg: 'bg-gray-100',    text: 'text-gray-600',    dot: 'bg-gray-400',     label: 'Dibatalkan' },
    };

    function showDetail(id) {
        const modal   = document.getElementById('modal-detail');
        const loading = document.getElementById('modal-loading');
        const content = document.getElementById('modal-content');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        content.innerHTML = '';
        modal.classList.remove('hidden');

        setTimeout(() => {
            const d = transaksiData[id];
            if (!d) {
                loading.innerHTML = '<p class="text-red-500 text-sm py-8 text-center px-6">Data tidak ditemukan.</p>';
                return;
            }

            const cfg = statusCfg[d.status] || statusCfg.pending;

            const headerBg = d.status === 'success'   ? 'from-emerald-500 to-teal-600'
                        : d.status === 'pending'   ? 'from-amber-400 to-yellow-500'
                        : d.status === 'expired'   ? 'from-gray-400 to-gray-500'
                        : d.status === 'failed'    ? 'from-red-500 to-rose-600'
                        : 'from-gray-400 to-gray-500';

            const headerIcon = d.status === 'success'
                ? '<i class="fas fa-check-circle text-white text-3xl"></i>'
                : d.status === 'pending'
                ? '<i class="fas fa-clock text-white text-3xl"></i>'
                : d.status === 'expired'
                ? '<i class="fas fa-hourglass-end text-white text-3xl"></i>'
                : '<i class="fas fa-times-circle text-white text-3xl"></i>';

            const footerHtml = d.status === 'success'
            ? `<div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-center">
                <p class="text-xs text-emerald-700 font-medium">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Langganan ${d.is_aktif ? 'sedang aktif' : 'sudah berakhir'}
                </p>
            </div>`
            : (d.status === 'pending' && d.is_payable)
            ? `<div class="space-y-2">
                <div class="bg-yellow-50 border border-yellow-100 rounded-xl px-4 py-3 text-center">
                    <p class="text-xs text-yellow-700 font-medium">
                        <i class="fas fa-clock mr-1"></i>
                        Menunggu konfirmasi pembayaran
                    </p>
                </div>
                <button onclick="openPaymentChoice(${d.id})"
                        class="w-full py-2.5 bg-stikom-blue hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition">
                    Lanjutkan Pembayaran
                </button>
            </div>`
            : d.status === 'expired'
            ? `<div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-hourglass-end mr-1"></i>
                    Waktu pembayaran sudah habis. Silakan buat pesanan baru.
                </p>
            </div>`
            : `<div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Transaksi ini tidak aktif
                </p>
            </div>`;

            content.innerHTML = `
                <div>
                    <div class="bg-gradient-to-br ${headerBg} px-6 pt-6 pb-8 relative">
                        <button onclick="closeModal()"
                                class="absolute top-4 right-4 w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition">
                            <i class="fas fa-times text-white text-xs"></i>
                        </button>
                        <div class="flex flex-col items-center text-center">
                            ${headerIcon}
                            <p class="text-white font-bold text-lg mt-2">${cfg.label}</p>
                            <p class="text-white/80 text-xs mt-0.5 font-mono">${d.order_id}</p>
                            <p class="text-white font-bold text-2xl mt-3">${d.harga_format}</p>
                        </div>
                    </div>

                    <div class="-mt-4 relative z-10">
                        <svg viewBox="0 0 400 24" class="w-full" preserveAspectRatio="none" style="height:24px">
                            <path d="M0,0 Q200,24 400,0 L400,24 L0,24 Z" fill="white"/>
                        </svg>
                    </div>

                    <div class="px-5 -mt-2 pb-2 space-y-0 divide-y divide-gray-100">
                        ${row('Layanan',       d.nama_layanan)}
                        ${row('Durasi',        d.durasi_label)}
                        ${row('Metode Bayar',  d.payment_type)}
                        ${row('ID Midtrans',   d.midtrans_id, 'mono')}
                        ${row('Mulai Aktif',   d.aktif_mulai)}
                        ${row('Aktif Hingga',  d.aktif_sampai, d.is_aktif ? 'green' : '')}
                        ${row('Tgl Transaksi', d.created_at)}
                        ${row('Tgl Update',    d.updated_at)}
                    </div>

                    <div class="px-5 py-4 mt-1">
                        ${footerHtml}
                    </div>

                    <div class="px-5 pb-5">
                        <button onclick="closeModal()"
                                class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition">
                            Tutup
                        </button>
                    </div>
                </div>`;

            loading.classList.add('hidden');
            content.classList.remove('hidden');
        }, 200);
    }

    function row(label, val, type = '') {
        const valClass = type === 'mono'  ? 'font-mono text-xs text-gray-600 break-all'
                    : type === 'green' ? 'font-semibold text-emerald-600'
                    : 'text-gray-700';
        return `
            <div class="flex justify-between items-center py-2.5 gap-4">
                <span class="text-xs text-gray-500 flex-shrink-0">${label}</span>
                <span class="text-xs ${valClass} text-right">${val || '—'}</span>
            </div>`;
    }

    function closeModal() {
        document.getElementById('modal-detail').classList.add('hidden');
    }
    function closeDetail(e) {
        if (e.target === document.getElementById('modal-detail')) closeModal();
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
@endpush

@endsection