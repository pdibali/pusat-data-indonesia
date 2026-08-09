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
    <div class="max-w-xl mx-auto px-4 py-30">
        <div class="rounded-lg border bg-white p-8 shadow-sm">
            <h1 class="text-xl font-black">Transfer Manual</h1>
            <p class="text-sm text-gray-500 mb-6">Order ID: {{ $transaksi->order_id }}</p>
            <div class="bg-white border p-5 mb-6">
                <p class="text-sm text-gray-500">Total Pembayaran</p>
                <p class="text-2xl font-black mb-4">{{ $transaksi->layanan->harga_format }}</p>
                <p class="text-sm font-semibold mb-1">Transfer ke:</p>
                <p class="text-sm">Bank BCA — 6110205111 a.n. WIDYA DHARMA SHANTI PT</p>
            </div>
            @if(session('error'))
                <p class="text-red-600 text-sm mb-3">{{ session('error') }}</p>
            @endif
            <form method="POST" action="{{ route('transaksi.upload_bukti', $transaksi) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-medium text-gray-500">Nama Pengirim</label>
                    <input type="text" name="nama_pengirim" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500">Bank Pengirim</label>
                    <input type="text" name="bank_pengirim" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500">Bukti Transfer (JPG/PNG/PDF, maks 2MB)</label>
                    <input type="file" name="bukti_transfer" required accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="w-full py-3 bg-stikom-accent font-black">Kirim Bukti Transfer</button>
            </form>
        </div>
    </div>
</body>
</html>
