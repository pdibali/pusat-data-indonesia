@extends('layouts.main')

@section('title', 'Detail Transaksi #' . $transaksi->order_id)

@section('content')
<div class="page-layout max-w-4xl">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Detail Transaksi</h1>
            <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ $transaksi->order_id }}</p>
        </div>
        <a href="{{ route('admin.transaksi.index') }}"
           class="px-4 py-2 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 transition flex items-center gap-2">
            <i class="fas fa-arrow-left text-xs"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Kolom Kiri (2/3) ─────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Status Banner --}}
            @php
                $bannerCfg = match($transaksi->status) {
                    'success'   => ['bg' => 'bg-emerald-50 border-emerald-200', 'icon' => 'fa-check-circle text-emerald-500', 'text' => 'text-emerald-800', 'sub' => 'text-emerald-600', 'label' => 'Pembayaran Berhasil'],
                    'pending'   => ['bg' => 'bg-yellow-50 border-yellow-200',   'icon' => 'fa-clock text-yellow-500',          'text' => 'text-yellow-800',   'sub' => 'text-yellow-600',   'label' => 'Menunggu Pembayaran'],
                    'failed'    => ['bg' => 'bg-red-50 border-red-200',         'icon' => 'fa-times-circle text-red-500',      'text' => 'text-red-800',      'sub' => 'text-red-600',      'label' => 'Pembayaran Gagal'],
                    'cancelled' => ['bg' => 'bg-gray-100 border-gray-200',      'icon' => 'fa-ban text-gray-400',              'text' => 'text-gray-700',     'sub' => 'text-gray-500',     'label' => 'Dibatalkan'],
                    default     => ['bg' => 'bg-gray-50 border-gray-200',       'icon' => 'fa-circle text-gray-400',           'text' => 'text-gray-700',     'sub' => 'text-gray-500',     'label' => ucfirst($transaksi->status)],
                };
            @endphp
            <div class="flex items-center gap-4 border rounded-2xl px-5 py-4 {{ $bannerCfg['bg'] }}">
                <i class="fas {{ $bannerCfg['icon'] }} text-3xl"></i>
                <div>
                    <p class="font-bold text-base {{ $bannerCfg['text'] }}">{{ $bannerCfg['label'] }}</p>
                    <p class="text-xs {{ $bannerCfg['sub'] }} mt-0.5">
                        Terakhir diperbarui: {{ $transaksi->updated_at->format('d M Y, H:i') }} WITA
                    </p>
                </div>
                <div class="ml-auto text-right">
                    <p class="text-xl font-bold {{ $bannerCfg['text'] }}">{{ $transaksi->harga_format }}</p>
                </div>
            </div>

            {{-- Info Transaksi --}}
            <div class="card-panel">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-receipt text-gray-400 text-sm"></i>
                    <h2 class="text-sm font-semibold text-gray-700">Informasi Transaksi</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @php
                    $rows = [
                        ['Order ID',        '<span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">' . e($transaksi->order_id) . '</span>'],
                        ['ID Midtrans',     $transaksi->midtrans_transaction_id ?? '<span class="text-gray-400">—</span>'],
                        ['Metode Bayar',    $transaksi->payment_type ? '<span class="uppercase font-medium">' . strtoupper(str_replace('_', ' ', $transaksi->payment_type)) . '</span>' : '<span class="text-gray-400">—</span>'],
                        ['Layanan',         e($transaksi->nama_layanan)],
                        ['Durasi',          $transaksi->durasi_label],
                        ['Harga',           '<span class="font-semibold">' . $transaksi->harga_format . '</span>'],
                        ['Tgl. Transaksi',  $transaksi->created_at->format('d M Y, H:i') . ' WITA'],
                    ];
                    @endphp
                    @foreach($rows as [$label, $val])
                    <div class="flex items-center justify-between px-6 py-3 text-sm">
                        <span class="text-gray-500 w-36 flex-shrink-0">{{ $label }}</span>
                        <span class="text-gray-800 text-right">{!! $val !!}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($transaksi->metode_pembayaran === 'manual')
                <div class="card-panel">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <i class="fas fa-money-bill-transfer text-gray-400 text-sm"></i>
                        <h2 class="text-sm font-semibold text-gray-700">Payment Proof</h2>
                    </div>

                    <div class="px-6 py-4">
                        @if($transaksi->bukti_transfer)
                            @php $ext = strtolower(pathinfo($transaksi->bukti_transfer, PATHINFO_EXTENSION)); @endphp
                            @if(in_array($ext, ['jpg', 'jpeg', 'png']))
                                <div class="rounded-xl overflow-hidden border border-gray-200 mb-2 bg-gray-50">
                                    <img src="{{ route('admin.transaksi.bukti', $transaksi) }}"
                                        alt="Bukti Transfer"
                                        class="w-full max-h-80 object-contain">
                                </div>
                            @else
                                <div class="flex items-center gap-3 p-4 border border-gray-200 rounded-xl mb-2 bg-gray-50">
                                    <i class="fas fa-file-pdf text-red-400 text-2xl"></i>
                                    <span class="text-sm text-gray-600">Bukti transfer (PDF)</span>
                                </div>
                            @endif
                            <a href="{{ route('admin.transaksi.bukti', $transaksi) }}" target="_blank"
                            class="text-xs text-blue-600 hover:underline font-medium">
                                View Full Image →
                            </a>
                        @else
                            <p class="text-xs text-gray-400">Bukti transfer belum diupload.</p>
                        @endif

                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Nama Pengirim</span>
                                <span class="text-gray-800 font-medium">{{ $transaksi->nama_pengirim ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Bank Pengirim</span>
                                <span class="text-gray-800 font-medium">{{ $transaksi->bank_pengirim ?? '—' }}</span>
                            </div>
                        </div>

                        @if($transaksi->status === 'menunggu_verifikasi')
                            {{-- Dua form kosong (cuma csrf), input & tombol dihubungkan via atribut form="" --}}
                            <form id="form-verify" method="POST" action="{{ route('admin.transaksi.verify', $transaksi) }}">
                                @csrf
                            </form>
                            <form id="form-reject" method="POST" action="{{ route('admin.transaksi.reject', $transaksi) }}">
                                @csrf
                            </form>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <label class="text-xs font-medium text-gray-500 block mb-1">Admin Notes (Optional)</label>
                                <textarea form="form-reject" name="catatan_admin" rows="2"
                                        placeholder="Add notes about this review..."
                                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>

                                <div class="flex gap-2">
                                    <button type="submit" form="form-verify"
                                            class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition">
                                        Confirm Approve
                                    </button>
                                    <button type="submit" form="form-reject"
                                            class="flex-1 py-2.5 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-lg text-sm font-semibold transition">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @elseif($transaksi->catatan_admin)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs text-gray-500 mb-1">Catatan Admin</p>
                                <p class="text-sm text-gray-700">{{ $transaksi->catatan_admin }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

            {{-- Masa Aktif Langganan --}}
            @if($transaksi->isSuccess())
            <div class="card-panel">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-calendar-check text-gray-400 text-sm"></i>
                    <h2 class="text-sm font-semibold text-gray-700">Masa Aktif Langganan</h2>
                </div>
                <div class="px-6 py-5">
                    @if($transaksi->aktif_sampai)
                    <div class="flex items-center gap-4">
                        <div class="flex-1 text-center bg-blue-50 rounded-xl py-3 px-4">
                            <p class="text-xs text-blue-500 mb-1">Mulai</p>
                            <p class="font-bold text-blue-700">{{ $transaksi->aktif_mulai?->format('d M Y') }}</p>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400 text-sm"></i>
                        <div class="flex-1 text-center bg-blue-50 rounded-xl py-3 px-4">
                            <p class="text-xs text-blue-500 mb-1">Berakhir</p>
                            <p class="font-bold text-blue-700">{{ $transaksi->aktif_sampai->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        @if($transaksi->isAktif())
                        <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            Langganan Aktif — Sisa {{ now()->diffInDays($transaksi->aktif_sampai) }} hari
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-600 text-xs font-semibold px-3 py-1.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                            Langganan Berakhir
                        </span>
                        @endif
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-infinity text-emerald-500 text-3xl mb-2"></i>
                        <p class="font-bold text-emerald-700">Langganan Selamanya</p>
                        <p class="text-xs text-gray-400 mt-1">Tidak ada batas waktu</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Raw Payload Midtrans (collapsible) --}}
            @if($transaksi->midtrans_payload)
            <div class="card-panel">
                <button type="button"
                        onclick="togglePayload()"
                        class="w-full px-6 py-4 flex items-center justify-between text-left border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-code text-gray-400 text-sm"></i>
                        <h2 class="text-sm font-semibold text-gray-700">Raw Payload Midtrans</h2>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">Debug / Audit</span>
                    </div>
                    <i id="payload-chevron" class="fas fa-chevron-down text-xs text-gray-400 transition-transform"></i>
                </button>
                <div id="payload-content" class="hidden px-6 py-4">
                    <pre class="text-xs text-gray-600 bg-gray-50 rounded-xl p-4 overflow-x-auto leading-relaxed">{{ json_encode($transaksi->midtrans_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Kolom Kanan (1/3) ────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Info Pengguna --}}
            <div class="card-panel">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-user text-gray-400 text-sm"></i>
                    <h2 class="text-sm font-semibold text-gray-700">Pengguna</h2>
                </div>
                <div class="px-6 py-5">
                    @if($transaksi->user)
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-blue-600 font-bold text-sm">
                                {{ strtoupper(substr($transaksi->user->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">{{ $transaksi->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $transaksi->user->email }}</p>
                        </div>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Username</span>
                            <span class="text-gray-700">{{ $transaksi->user->username }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Daftar</span>
                            <span class="text-gray-700">{{ $transaksi->user->registerdate?->format('d M Y') ?? '—' }}</span>
                        </div>
                    </div>
                    @else
                    <p class="text-xs text-gray-400 text-center py-2">Data pengguna tidak ditemukan</p>
                    @endif
                </div>
            </div>

            {{-- Info Layanan --}}
            <div class="card-panel">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-box text-gray-400 text-sm"></i>
                    <h2 class="text-sm font-semibold text-gray-700">Layanan</h2>
                </div>
                <div class="px-6 py-5">
                    @if($transaksi->layanan)
                    @if($transaksi->layanan->thumbnail)
                    <img src="{{ asset('storage/' . $transaksi->layanan->thumbnail) }}"
                         alt="{{ $transaksi->layanan->nama_layanan }}"
                         class="w-full h-24 object-cover rounded-xl mb-3 border border-gray-100">
                    @endif
                    <p class="font-semibold text-gray-800 text-sm">{{ $transaksi->layanan->nama_layanan }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $transaksi->layanan->durasi_label }}</p>
                    <div class="mt-3">
                        @if($transaksi->layanan->is_popular)
                        <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-xs px-2 py-0.5 rounded-full">
                            <i class="fas fa-star text-xs"></i> Populer
                        </span>
                        @endif
                        @php $s = $transaksi->layanan->status; @endphp
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full ml-1
                            {{ $s === 'publish' ? 'bg-green-50 text-green-700' : ($s === 'pending' ? 'bg-yellow-50 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ ucfirst($s) }}
                        </span>
                    </div>
                    <a href="{{ route('admin.layanan.show', $transaksi->layanan) }}"
                       class="mt-3 text-xs text-blue-600 hover:underline block">
                        Lihat detail layanan →
                    </a>
                    @else
                    <p class="text-xs text-gray-400 text-center py-2">Layanan sudah dihapus</p>
                    <p class="text-xs text-gray-500 text-center font-medium">{{ $transaksi->nama_layanan }}</p>
                    @endif
                </div>
            </div>

            {{-- Snap Token --}}
            <div class="card-panel">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <i class="fas fa-key text-gray-400 text-sm"></i>
                    <h2 class="text-sm font-semibold text-gray-700">Snap Token</h2>
                </div>
                <div class="px-6 py-4">
                    @if($transaksi->snap_token)
                    <p class="font-mono text-xs text-gray-500 break-all bg-gray-50 rounded-lg p-3">
                        {{ Str::limit($transaksi->snap_token, 60) }}
                    </p>
                    @else
                    <p class="text-xs text-gray-400 text-center py-2">—</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePayload() {
    const content  = document.getElementById('payload-content');
    const chevron  = document.getElementById('payload-chevron');
    const isHidden = content.classList.contains('hidden');
    content.classList.toggle('hidden', !isHidden);
    chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
}
</script>
@endpush

@endsection