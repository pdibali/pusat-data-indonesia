<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Checkout — Pusat Data Indonesia Bali</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="bg-slate-50 text-gray-900 antialiased">

    @include('pages.landing.components.navbar')
    <main class="min-h-screen py-30">
        <div class="max-w-xl mx-auto px-4 bg-white border border-gray-200 rounded-lg shadow-sm py-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-0.5 h-7 bg-stikom-blue shrink-0"></div>
                <div>
                    <h1 class="text-xl font-black text-stikom font-display">Pilih Metode Pembayaran</h1>
                    <p class="text-xs text-gray-400 font-body mt-0.5">
                        {{ $transaksi->layanan->nama_layanan }} — {{ $transaksi->layanan->harga_format }}
                    </p>
                </div>
            </div>

            @php $midtransOn = \App\Support\Setting::get('midtrans_enabled', true); @endphp

            <form method="POST" action="{{ route('transaksi.bayar_midtrans', $transaksi) }}" class="mb-3">
                @csrf
                @if($midtransOn)
                    <button type="submit" class="w-full py-4 bg-stikom-blue text-white font-black text-sm">
                        Kartu / E-Wallet / VA
                    </button>
                @else
                    <div class="w-full py-4 bg-gray-100 text-gray-400 font-semibold text-xs text-center rounded">
                        <h1 class="text-sm font-black text-gray-400 mb-1">Bayar dengan Kartu / E-Wallet / VA</h1>
                        Fitur Pembayaran ini akan Segera hadir
                    </div>
                @endif
            </form>

            <form method="POST" action="{{ route('transaksi.pilih_manual', $transaksi) }}">
                @csrf
                <button type="submit" class="w-full py-4 border border-stikom-blue text-stikom-blue font-black text-sm">
                    Transfer Bank Manual
                </button>
            </form>
        </div>
    </main>
</body>
</html>