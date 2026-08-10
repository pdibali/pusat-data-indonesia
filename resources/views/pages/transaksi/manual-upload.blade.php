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
                    <label class="text-xs font-medium text-gray-500 block mb-1">Bukti Transfer (JPG/PNG/PDF, maks 2MB)</label>

                    <div id="dropzone"
                        class="border-2 border-dashed border-gray-300 rounded-lg py-8 px-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50/40 transition">
                        <i class="fas fa-cloud-upload-alt text-gray-300 text-3xl mb-2"></i>
                        <p class="text-sm text-gray-500">
                            Drag and drop or <span class="text-blue-600 underline font-medium">browse</span>
                        </p>
                    </div>

                    <input type="file" id="bukti-input" name="bukti_transfer"
                        accept=".jpg,.jpeg,.png,.pdf" required class="hidden">

                    <div id="file-preview" class="hidden mt-3 flex items-center gap-3 border border-gray-200 rounded-lg p-3">
                        <div id="file-icon-box" class="w-10 h-10 rounded flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0 bg-blue-500">
                            <span id="file-icon-label"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p id="file-name" class="text-sm text-gray-800 truncate"></p>
                            <p id="file-size" class="text-xs text-gray-400"></p>
                        </div>
                        <button type="button" id="file-remove" class="text-gray-400 hover:text-red-500 transition p-1">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 bg-stikom-accent font-black">Kirim Bukti Transfer</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const dropzone  = document.getElementById('dropzone');
        const input     = document.getElementById('bukti-input');
        const preview   = document.getElementById('file-preview');
        const fileName  = document.getElementById('file-name');
        const fileSize  = document.getElementById('file-size');
        const iconLabel = document.getElementById('file-icon-label');
        const iconBox   = document.getElementById('file-icon-box');
        const removeBtn = document.getElementById('file-remove');

        const iconColor = { PDF: 'bg-red-500', JPG: 'bg-blue-500', JPEG: 'bg-blue-500', PNG: 'bg-emerald-500' };

        dropzone.addEventListener('click', () => input.click());

        ['dragover', 'dragenter'].forEach(evt =>
            dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                dropzone.classList.add('border-blue-400', 'bg-blue-50');
            })
        );
        ['dragleave', 'dragend'].forEach(evt =>
            dropzone.addEventListener(evt, () => dropzone.classList.remove('border-blue-400', 'bg-blue-50'))
        );
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-blue-400', 'bg-blue-50');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                showPreview(e.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', () => {
            if (input.files.length) showPreview(input.files[0]);
        });

        function showPreview(file) {
            const ext = file.name.split('.').pop().toUpperCase();
            iconLabel.textContent = ext;
            iconBox.className = 'w-10 h-10 rounded flex items-center justify-center text-[9px] font-bold text-white flex-shrink-0 ' + (iconColor[ext] || 'bg-gray-400');
            fileName.textContent = file.name;
            fileSize.textContent = Math.max(1, Math.round(file.size / 1024)) + 'KB';
            preview.classList.remove('hidden');
            dropzone.classList.add('hidden');
        }

        removeBtn.addEventListener('click', () => {
            input.value = '';
            preview.classList.add('hidden');
            dropzone.classList.remove('hidden');
        });
    })();
    </script>
</body>
</html>
