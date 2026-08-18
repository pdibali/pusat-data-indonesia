    @extends('layouts.main')

    @section('content')

    <style>
        /* --- Base Tom Select Overrides --- */
        .ts-wrapper { font-size: 0.75rem; }
        .ts-wrapper.form-control, 
        .ts-wrapper.form-select { padding: 0; border: none; background: transparent; }

        .ts-control {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem 0.5rem !important;
            min-height: 2rem !important;
            background: #fff !important;
            box-shadow: none !important;
            transition: border-color .15s, box-shadow .15s;
            cursor: pointer;
        }

        .ts-control input {
            font-size: 0.75rem !important;
            color: #374151 !important;
            line-height: 1.4 !important;
        }

        .ts-control input::placeholder { color: #9ca3af !important; }

        .ts-wrapper.focus .ts-control {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, .25) !important;
        }

        /* --- State: Disabled --- */
        .ts-wrapper.disabled .ts-control {
            background: #f9fafb !important;
            border-color: #e5e7eb !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
        }
        .ts-wrapper.disabled .ts-control input { color: #9ca3af !important; cursor: not-allowed !important; }
        .ts-wrapper.disabled .ts-control .item { color: #9ca3af !important; }

        /* --- Dropdown Style --- */
        .ts-dropdown {
            border: 1px solid #e5e7eb !important;
            border-radius: .5rem !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08) !important;
            margin-top: 3px !important;
            overflow: hidden;
            z-index: 9999 !important;
        }

        .ts-dropdown .ts-dropdown-content { max-height: 220px; }
        
        .ts-dropdown [data-selectable] {
            font-size: .75rem;
            padding: .45rem .875rem;
            color: #374151;
            transition: background .1s;
        }

        .ts-dropdown .option:hover,
        .ts-dropdown [data-selectable].highlight { background: #f0f9ff !important; color: #0369a1 !important; }
        
        .ts-dropdown .option.selected,
        .ts-dropdown .option.active { background: #f5f5f5 !important; color: #282828 !important; font-weight: 600; }

        /* --- Utils --- */
        .ts-dropdown .highlight { background: #fef9c3; border-radius: 2px; font-weight: 600; }
        .ts-wrapper.single .ts-control::after { border-color: #9ca3af transparent transparent !important; }
        .ts-wrapper.auto-field .ts-control { background: #f0f9ff !important; border-color: #bae6fd !important; }
    </style>


    <div class="py-6">

        <a href="{{ route('data.index') }}"
        class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
            <i class="fas fa-angle-left"></i> Kembali
        </a>

        <div class="mt-2 bg-white rounded-xl shadow p-6">

            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-bold text-gray-800 mb-1">Input Data</h1>
                    <p class="text-sm text-gray-400 mb-6">Data akan menunggu verifikasi admin sebelum ditampilkan</p>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <p id="current-date"></p>
                    <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
                </div>
            </div>

            {{-- DUPLICATE WARNING --}}
            @if(session('duplicate_warning'))
                <div class="mb-5 bg-amber-50 border border-amber-300 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 shrink-0"></i>
                        <div>
                            <p class="font-semibold text-amber-800 text-sm">Data Duplikat Terdeteksi</p>
                            <p class="text-amber-700 text-sm mt-1">{{ session('duplicate_warning.message') }}</p>
                            <p class="text-amber-600 text-xs mt-1">
                                Data existing ID #{{ session('duplicate_warning.existing_id') }} —
                                Status: <strong>{{ session('duplicate_warning.existing_status') }}</strong>
                            </p>
                            <div class="flex gap-2 mt-3">
                                <a href="{{ route('data.show', ['datum' => session('duplicate_warning.existing_id'), 'from' => 'create']) }}"
                                class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1.5
                                        rounded-md font-medium transition-colors">
                                    <i class="fas fa-eye mr-1"></i> Lihat Data Existing
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB SWITCHER --}}
            <div class="flex border-b border-gray-200 mb-6">
                <button onclick="switchTab('manual')" id="tab-manual"
                    class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors
                        border-sky-500 text-sky-600">
                    <i class="fas fa-keyboard mr-2"></i>Input Manual
                </button>
                <button onclick="switchTab('excel')" id="tab-excel"
                    class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors
                        border-transparent text-gray-400 hover:text-gray-600">
                    <i class="fas fa-file-excel mr-2"></i>Upload Excel
                </button>
            </div>

            {{-- TAB 1: INPUT MANUAL --}}
            <div id="panel-manual">
                <form action="{{ route('data.store') }}" method="POST" class="space-y-5">
                    @csrf
    
                    {{-- METADATA --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Metadata <span class="text-red-500">*</span>
                        </label>
                        <select name="metadata_id" id="metadataSelect" required>
                            <option value="">Cari atau pilih metadata…</option>
                            @foreach($metadataList as $meta)
                                <option value="{{ $meta->metadata_id }}"
                                    data-tipe="{{ $meta->tipe_data }}"
                                    data-satuan="{{ $meta->satuan_data }}"
                                    data-frekuensi="{{ $meta->frekuensi_penerbitan }}"
                                    data-flag-desimal="{{ $meta->flag_desimal }}"
                                    {{ old('metadata_id') == $meta->metadata_id ? 'selected' : '' }}>
                                    {{ $meta->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('metadata_id')
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                        <div id="metadataInfo" class="hidden mt-2 px-3 py-2 bg-sky-50 border border-sky-100 rounded-md text-xs text-sky-700">
                            <span id="metadataTipe"></span> •
                            Satuan: <span id="metadataSatuan"></span> •
                            Frekuensi: <span id="metadataFrekuensi"></span>
                        </div>
                    </div>
    
                    {{-- LOKASI --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Lokasi <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal text-xs ml-1">— Pilih hingga level yang diperlukan</span>
                        </label>
                        <div class="flex flex-col md:flex-row md:gap-3 gap-3">
    
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Provinsi <span class="text-red-500">*</span></label>
                                <select name="provinsi_id" id="selProvinsi"  required>
                                    <option value="">Cari provinsi…</option>
                                    @foreach($provinsiList as $loc)
                                        <option value="{{ $loc->location_id }}"
                                            {{ old('provinsi_id') == $loc->location_id ? 'selected' : '' }}>
                                            {{ $loc->nama_wilayah }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
    
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Kabupaten / Kota</label>
                                <select name="kabupaten_id" id="selKabupaten">
                                    <option value="">— Pilih Provinsi dulu —</option>
                                </select>
                            </div>
    
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan</label>
                                <select name="kecamatan_id" id="selKecamatan">
                                    <option value="">— Pilih Kab/Kota dulu —</option>
                                </select>
                            </div>
    
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Desa / Kelurahan</label>
                                <select name="desa_id" id="selDesa">
                                    <option value="">— Pilih Kecamatan dulu —</option>
                                </select>
                            </div>
    
                        </div>
                        <div id="lokasiInfo" class="hidden mt-2 px-3 py-2 bg-sky-50 border border-sky-100 rounded-md text-xs text-sky-700 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt"></i>
                            <span id="lokasiInfoText"></span>
                        </div>
                        @error('location_id')
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
    
                    {{-- WAKTU --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Waktu <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="time_id" id="selectedTimeId" value="{{ old('time_id') }}">
    
                        <div class="flex gap-2 flex-wrap md:flex-nowrap">
    
                            <div class="w-full md:w-1/5">
                                <label class="block text-xs text-gray-500 mb-1">Dekade</label>
                                <select id="filterDekade">
                                    <option value="">—</option>
                                    @foreach($timeList->pluck('decade')->unique()->sortDesc() as $decade)
                                        <option value="{{ $decade }}">{{ $decade }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs italic text-gray-400" id="hintDekade"></p>
                            </div>
    
                            <div class="w-full md:w-1/5">
                                <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                                <select id="filterTahun">
                                    <option value="0">—</option>
                                    @foreach($timeList->pluck('year')->filter()->unique()->sortDesc() as $yr)
                                        <option value="{{ $yr }}">{{ $yr }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs italic text-gray-400" id="hintTahun"></p>
                            </div>
    
                            <div class="w-full md:w-1/5">
                                <label class="block text-xs text-gray-500 mb-1">Semester</label>
                                <select id="filterSemester">
                                    <option value="0">—</option>
                                    <option value="1">Semester 1</option>
                                    <option value="2">Semester 2</option>
                                </select>
                                <p class="mt-1 text-xs italic text-gray-400" id="hintSemester"></p>
                            </div>
    
                            <div class="w-full md:w-1/5">
                                <label class="block text-xs text-gray-500 mb-1">Kuartal</label>
                                <select id="filterKuartal">
                                    <option value="0">—</option>
                                    <option value="1">Q1 (Jan–Mar)</option>
                                    <option value="2">Q2 (Apr–Jun)</option>
                                    <option value="3">Q3 (Jul–Sep)</option>
                                    <option value="4">Q4 (Okt–Des)</option>
                                </select>
                                <p class="mt-1 text-xs italic text-gray-400" id="hintKuartal"></p>
                            </div>
    
                            <div class="w-full md:w-1/5">
                                <label class="block text-xs text-gray-500 mb-1">Bulan</label>
                                <select id="filterBulan">
                                    <option value="0">—</option>
                                    @foreach(['Januari','Februari','Maret','April','Mei','Juni',
                                            'Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                                        <option value="{{ $i + 1 }}">{{ $bulan }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs italic text-gray-400" id="hintBulan"></p>
                            </div>
    
                        </div>
    
                        <div id="waktuInfo" class="hidden mt-2 px-3 py-2 bg-green-50 border border-green-200 rounded-md text-xs text-green-700 flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span id="waktuInfoText"></span>
                        </div>
                        <div id="waktuHint" class="mt-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-md text-xs text-amber-700 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            Pilih Metadata terlebih dahulu untuk mengaktifkan pilihan waktu.
                        </div>
    
                        @error('time_id')
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
    
                    {{-- NILAI ANGKA --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Nilai Angka
                                <span id="satuanLabel" class="text-gray-400 font-normal text-xs ml-1"></span>
                            </label>
                            <input type="hidden" name="number_value" id="hiddenNumberValue" value="{{ old('number_value') }}">
                            <div class="relative">
                                <input type="text" id="displayNumberValue"
                                    placeholder="Masukkan nilai angka"
                                    inputmode="decimal"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                        focus:outline-none focus:ring-2 focus:ring-sky-400 transition-colors"
                                    oninput="onNumberInput(this)"
                                    onblur="onNumberBlur(this)"
                                    onfocus="onNumberFocus(this)">
                                <span id="desimalBadge"
                                    class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-xs
                                            px-2 py-0.5 rounded-full bg-sky-100 text-sky-600 font-medium
                                            pointer-events-none select-none">desimal</span>
                            </div>
                            <div id="flagDesimalInfo" class="hidden mt-1.5">
                                <p id="flagDesimalText" class="text-xs"></p>
                            </div>
                            @error('number_value')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Rujukan
                            <span id="satuanLabel" class="text-gray-400 font-normal text-xs ml-1"></span>
                        </label>
                        <p class="mt-1 text-xs text-gray-400">Produsen akan terisi otomatis berdasarkan pilihan Rujukan jika tidak diisi manual.</p>
                        <select name="rujukan_id"  placeholder="Pilih rujukan..." autocomplete="off"
                                class="tom-select w-full focus:outline-none focus:ring-2
                                    focus:ring-sky-400 text-xs">
                            <option value="">-- Pilih Rujukan --</option>
                            @foreach($rujukanList as $rujukan)
                                <option value="{{ $rujukan->rujukan_id }}"
                                    {{ old('rujukan_id') == $rujukan->rujukan_id ? 'selected' : '' }}>
                                    {{ $rujukan->nama_rujukan }}
                                </option>
                            @endforeach
                        </select>
                        {{-- Hidden: produsen_id otomatis dari rujukan --}}
                        <input type="hidden" name="produsen_id" id="hiddenProdusenId">
        
                        {{-- Info produsen otomatis --}}
                        <div id="produsenInfo" class="hidden mt-2 px-3 py-2 bg-emerald-50 border border-emerald-100 rounded-md text-xs text-emerald-700 flex items-center gap-2">
                            <i class="fa-solid fa-industry text-emerald-500"></i>
                            Produsen: <span id="produsenInfoText" class="font-semibold ml-1"></span>
                            <span class="text-emerald-400 ml-1">(otomatis dari rujukan)</span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-md shadow
                                text-sm font-semibold flex items-center gap-2 transition-colors">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </div>
                </form>
            </div>

            {{-- TAB 2: UPLOAD EXCEL --}}
            <div id="panel-excel" class="hidden">

                {{-- Info Format Template --}}
                <div class="mb-5 rounded-lg border p-4 text-sm"
                    style="background:#f0f9ff; border-color:#bae6fd;">
                    <p class="font-semibold flex items-center gap-2 mb-2" style="color:#0369a1;">
                        <i class="fas fa-info-circle"></i>
                        Format Excel Template Metadata
                    </p>
                    <p class="text-xs text-gray-600 mb-3">
                        Gunakan file template yang di-generate dari halaman
                        <strong>Metadata</strong>, dengan klik <strong>Export Template</strong>
                        untuk menggunakan template input data.
                    </p>
                    {{-- <div class="flex flex-wrap gap-1.5 mb-3">
                        @foreach(['metadata_id','nama_metadata','location_id','nama_wilayah', 'rujukan_id'] as $col)
                            <code class="px-2 py-0.5 rounded text-xs font-mono font-bold"
                                style="background:#e0f2fe; color:#0369a1;">{{ $col }}</code>
                        @endforeach
                        <code class="px-2 py-0.5 rounded text-xs font-mono"
                            style="background:#fef3c7; color:#92400e;">{{ date('Y') }}</code>
                        <code class="px-2 py-0.5 rounded text-xs font-mono"
                            style="background:#fef3c7; color:#92400e;">{{ date('Y') + 1}}</code>
                        <code class="px-2 py-0.5 rounded text-xs font-mono"
                            style="background:#fef3c7; color:#92400e;">… dst</code>
                    </div>
                    <p class="text-xs text-gray-500">
                        Format kolom periode yang didukung:
                        <code class="bg-gray-100 px-1 rounded">{{ date('Y') }}</code> (Tahunan) ·
                        <code class="bg-gray-100 px-1 rounded">{{ date('Y') }}_Q1</code> (Quarter) ·
                        <code class="bg-gray-100 px-1 rounded">{{ date('Y') }}_S1</code> (Semester) ·
                        <code class="bg-gray-100 px-1 rounded">Jan_{{ date('Y') }}</code> (Bulanan)
                    </p> --}}
                </div>

                {{-- Drop Zone --}}
                <div id="dropZone"
                    class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center
                            transition-colors cursor-pointer"
                    onclick="document.getElementById('fileExcel').click()"
                    ondragover="event.preventDefault(); this.style.borderColor='#38bdf8'; this.style.background='#f0f9ff';"
                    ondragleave="this.style.borderColor=''; this.style.background='';"
                    ondrop="handleDrop(event)">
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium">Klik atau drag & drop file Excel template di sini</p>
                    <p class="text-gray-400 text-xs mt-1">Format: .xlsx atau .xls • Maksimal 10MB</p>
                    <input type="file" id="fileExcel" accept=".xlsx,.xls" class="hidden"
                        onchange="onFileSelected(this.files[0])">
                </div>

                {{-- File info bar --}}
                <div id="fileInfoBar"
                    class="hidden mt-3 flex items-center gap-3 px-4 py-2.5 bg-gray-50
                            border border-gray-200 rounded-lg text-sm">
                    <i class="fas fa-file-excel text-green-500 text-lg"></i>
                    <div class="flex-1 min-w-0">
                        <p id="fileInfoName" class="text-gray-700 font-medium truncate"></p>
                        <p id="fileInfoSize" class="text-gray-400 text-xs"></p>
                    </div>
                    <button onclick="resetUpload()"
                            class="text-xs text-red-400 hover:text-red-600 transition-colors flex items-center gap-1">
                        <i class="fas fa-times"></i> Ganti File
                    </button>
                </div>

                {{-- Loading state --}}
                <div id="loadingBar" class="hidden mt-4">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm"
                        style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1;">
                        <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="loadingText">Membaca dan memvalidasi file Excel di server…</span>
                    </div>
                    <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full animate-pulse"
                            style="width:100%; background:#38bdf8;"></div>
                    </div>
                </div>

                {{-- PREVIEW RESULT --}}
                <div id="previewSection" class="hidden mt-6 space-y-4">

                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3" id="statsGrid"></div>

                    <div id="invalidMetaSection" class="hidden rounded-xl overflow-hidden"
                        style="border: 1px solid #c084fc;">
                        <div class="flex items-center gap-2.5 px-4 py-3 cursor-pointer select-none"
                            style="background: #faf5ff;"
                            onclick="toggleSection('meta')">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0"
                                style="background:#ede9fe;">
                                <svg class="w-4 h-4" style="color:#7c3aed;" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                        clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold" style="color:#6d28d9;">
                                    Metadata Tidak Valid — Data Tidak Akan Diimport
                                </p>
                                <p class="text-xs mt-0.5" style="color:#7c3aed;" id="invalidMetaSubtitle"></p>
                            </div>
                            <span id="metaBadge"
                                class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                style="background:#ede9fe; color:#6d28d9; border:1px solid #c084fc;"></span>
                            <svg id="metaChevron"
                                class="w-4 h-4 shrink-0 transition-transform duration-200"
                                style="color:#a78bfa;"
                                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 6l4 4 4-4"/>
                            </svg>
                        </div>
                        <div id="metaBody" class="hidden" style="border-top:1px solid #e9d5ff;">
                            <div class="px-4 py-2.5 text-xs flex items-start gap-2"
                                style="background:#fdf4ff; color:#86198f;">
                                <i class="fas fa-lightbulb mt-0.5 shrink-0" style="color:#c026d3;"></i>
                                <span>
                                    Data dari metadata berikut <strong>dilewati sepenuhnya</strong>.
                                    Pastikan metadata sudah terdaftar di sistem dan berstatus
                                    <strong>Active</strong> sebelum mengimpor.
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead style="background:#f3e8ff;">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold w-24" style="color:#7c3aed;">ID</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#7c3aed;">Nama Metadata</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#7c3aed;">Keterangan</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#7c3aed;">Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody id="metaTableBody" class="divide-y" style="divide-color:#f3e8ff;"></tbody>
                                </table>
                            </div>
                            <div id="metaPager"
                                    class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs transition-colors"
                                    style="color:#7c3aed; border-top:1px solid #e9d5ff;"
                                    onclick="showMore('meta')">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                    stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                                <span id="metaShowMoreTxt"></span>
                            </div>
                        </div>
                    </div>

                    <div id="invalidSatuanSection" class="hidden rounded-xl overflow-hidden" style="border: 1px solid #6366f1;">
                        <div class="flex items-center gap-2.5 px-4 py-3 cursor-pointer select-none" style="background: #eef2ff;"
                            onclick="toggleSection('satuan')">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0" style="background:#e0e7ff;">
                                <i class="fas fa-ruler-combined text-xs" style="color:#4f46e5;"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold" style="color:#3730a3;">Satuan Tidak Terdaftar — Baris Dilewati</p>
                                <p class="text-xs mt-0.5" style="color:#4338ca;" id="satuanSubtitle"></p>
                            </div>
                            <span id="satuanBadge" class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                style="background:#e0e7ff; color:#3730a3; border:1px solid #6366f1;"></span>
                            <svg id="satuanChevron" class="w-4 h-4 shrink-0 transition-transform duration-200" style="color:#818cf8;"
                                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                        </div>
                        <div id="satuanBody" class="hidden" style="border-top:1px solid #c7d2fe;">
                            <div class="px-4 py-2.5 text-xs flex items-start gap-2" style="background:#eef2ff; color:#3730a3;">
                                <i class="fas fa-triangle-exclamation mt-0.5 shrink-0" style="color:#6366f1;"></i>
                                <span>Baris berikut TIDAK diimport karena teks satuan-nya belum terdaftar di tabel master satuan. Daftarkan dulu satuannya, lalu upload ulang.</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead style="background:#eef2ff;">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#4338ca;">Metadata</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#4338ca;">Satuan Metadata (Excel)</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#4338ca;">Satuan Rujukan (Excel)</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#4338ca;">Tidak Ketemu</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#4338ca;">Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody id="satuanTableBody" class="divide-y" style="divide-color:#c7d2fe;"></tbody>
                                </table>
                            </div>
                            <div id="satuanPager" class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs"
                                style="color:#4338ca; border-top:1px solid #c7d2fe;" onclick="showMore('satuan')"></div>
                        </div>
                    </div>

                    <div id="timeNotFoundAlert" class="hidden rounded-lg p-4 text-sm"
                        style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">
                        <p class="font-semibold flex items-center gap-2 mb-1">
                            <i class="fas fa-exclamation-circle"></i>
                            Kolom Periode Tidak Ditemukan di Tabel Time
                        </p>
                        <p id="timeNotFoundDetail" class="text-xs"></p>
                        <p class="text-xs mt-1">
                            Pastikan tabel <code>time</code> sudah berisi data untuk tahun/periode tersebut.
                        </p>
                    </div>

                    <div id="errorSection" class="hidden rounded-xl overflow-hidden border border-red-200">
                        <div class="flex items-center gap-2.5 px-4 py-2.5 bg-red-50 cursor-pointer select-none"
                            onclick="toggleSection('err')">
                            <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                            <p class="text-sm font-semibold text-red-700 flex-1">Terdapat baris bermasalah</p>
                            <span id="errBadge"
                                class="text-xs font-medium px-2 py-0.5 rounded-full
                                        bg-red-100 text-red-600 border border-red-200"></span>
                            <svg id="errChevron" class="w-4 h-4 text-red-400 transition-transform duration-200"
                                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 6l4 4 4-4"/>
                            </svg>
                        </div>
                        <div id="errBody" class="hidden border-t border-red-200">
                            <table class="w-full text-xs">
                                <thead class="bg-red-50 text-red-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium w-28">Baris Excel</th>
                                        <th class="px-3 py-2 text-left font-medium">Keterangan masalah</th>
                                    </tr>
                                </thead>
                                <tbody id="errTableBody" class="divide-y divide-red-100"></tbody>
                            </table>
                            <div id="errPager"
                                    class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs
                                        text-red-500 border-t border-red-100 hover:bg-red-50 transition-colors"
                                    onclick="showMore('err')">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                    stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                                <span id="errShowMoreTxt"></span>
                            </div>
                        </div>
                    </div>

                    <div id="dupSection" class="hidden rounded-xl overflow-hidden border border-amber-200">
                        <div class="flex items-center gap-2.5 px-4 py-2.5 bg-amber-50 cursor-pointer select-none"
                            onclick="toggleSection('dup')">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                            <p class="text-sm font-semibold text-amber-700 flex-1">Data sudah ada di database</p>
                            <span id="dupBadge"
                                class="text-xs font-medium px-2 py-0.5 rounded-full
                                        bg-amber-100 text-amber-600 border border-amber-200"></span>
                            <label class="flex items-center gap-1.5 text-xs text-amber-600 cursor-pointer ml-1"
                                onclick="event.stopPropagation()">
                                <input type="checkbox" id="cbSkipDup" checked
                                    class="rounded border-amber-300 text-amber-500 focus:ring-amber-400">
                                Lewati duplikat
                            </label>
                            <svg id="dupChevron" class="w-4 h-4 text-amber-400 transition-transform duration-200 ml-1"
                                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 6l4 4 4-4"/>
                            </svg>
                        </div>
                        <div id="dupBody" class="hidden border-t border-amber-200">
                            <table class="w-full text-xs">
                                <thead class="bg-amber-50 text-amber-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium">Metadata</th>
                                        <th class="px-3 py-2 text-left font-medium">Lokasi</th>
                                        <th class="px-3 py-2 text-left font-medium">Periode</th>
                                        <th class="px-3 py-2 text-right font-medium">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody id="dupTableBody" class="divide-y divide-amber-100"></tbody>
                            </table>
                            <div id="dupPager"
                                    class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs
                                        text-amber-500 border-t border-amber-100 hover:bg-amber-50 transition-colors"
                                    onclick="showMore('dup')">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                    stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                                <span id="dupShowMoreTxt"></span>
                            </div>
                        </div>
                    </div>

                    <div id="outlierSection" class="hidden rounded-xl overflow-hidden"
                        style="border: 1px solid #f97316;">
                    
                        {{-- Header collapsible --}}
                        <div class="flex items-center gap-2.5 px-4 py-3 cursor-pointer select-none"
                            style="background: #fff7ed;"
                            onclick="toggleSection('out')">
                    
                            <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0"
                                style="background:#ffedd5;">
                                <i class="fas fa-chart-line text-orange-500 text-xs"></i>
                            </div>
                    
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold" style="color:#9a3412;">
                                    Data Outlier Terdeteksi (Standard Z-Score vs DB)
                                </p>
                                <p class="text-xs mt-0.5" style="color:#c2410c;" id="outlierSubtitle"></p>
                            </div>
                    
                            <span id="outBadge"
                                class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                style="background:#ffedd5; color:#9a3412; border:1px solid #f97316;"></span>
                    
                            <svg id="outChevron"
                                class="w-4 h-4 shrink-0 transition-transform duration-200"
                                style="color:#fb923c;"
                                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 6l4 4 4-4"/>
                            </svg>
                        </div>
                    
                        {{-- Body --}}
                        <div id="outBody" class="hidden" style="border-top:1px solid #fed7aa;">
                    
                            {{-- Info box --}}
                            <div class="px-4 py-3 text-xs flex items-start gap-2.5"
                                style="background:#fffbeb; color:#92400e; border-bottom:1px solid #fed7aa;">
                                <i class="fas fa-info-circle mt-0.5 shrink-0 text-amber-500"></i>
                                <div class="space-y-1">
                                    <p>
                                        Sistem mendeteksi nilai yang menyimpang jauh dari
                                        <strong>rata-rata historis data di database</strong>
                                        menggunakan <strong>Standard Z-Score</strong>.
                                    </p>
                                    <p>
                                        Apabila nilai <strong>z &gt; 3</strong> dianggap outlier.
                                        Anda bisa memilih untuk <strong>menyertakan</strong> atau
                                        <strong>mengecualikan</strong> data tersebut dari proses import.
                                    </p>
                                    <p class="text-amber-600">
                                        <i class="fas fa-link mr-1"></i>
                                        Nilai z-score dan mean yang ditampilkan di sini
                                        <strong>identik</strong> dengan yang akan tersimpan di
                                        Control Anomali setelah import.
                                    </p>
                                </div>
                            </div>

                            {{-- Legenda level outlier --}}
                            <div class="px-4 py-2.5 flex items-center gap-2 flex-wrap text-xs"
                                style="background:#fffbeb; border-bottom:1px solid #fed7aa;">
                                <span class="font-semibold" style="color:#92400e;">Level outlier:</span>

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full"
                                    style="background:#fefce8; color:#854d0e; border:1px solid #fde047;">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:#ca8a04;"></span>
                                    Low &nbsp;<span class="font-mono">z &lt;10</span>
                                </span>

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full"
                                    style="background:#fff7ed; color:#9a3412; border:1px solid #fb923c;">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:#ea580c;"></span>
                                    Medium &nbsp;<span class="font-mono">z 10–30</span>
                                </span>

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full"
                                    style="background:#fef2f2; color:#b91c1c; border:1px solid #f87171;">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:#dc2626;"></span>
                                    High &nbsp;<span class="font-mono">z 31–40</span>
                                </span>

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full"
                                    style="background:#fdf4ff; color:#86198f; border:1px solid #e879f9;">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:#a21caf;"></span>
                                    Critical &nbsp;<span class="font-mono">z &gt;40</span>
                                </span>
    
                                <span class="ml-auto italic" style="color:#c2410c;">
                                    Semakin tinggi z-score, semakin ekstrem penyimpangan dari rata-rata historis
                                </span>
                            </div>
                    
                            {{-- Tabel outlier --}}
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead style="background:#fff7ed;">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#9a3412;">Metadata</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#9a3412;">Lokasi</th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9a3412;">Periode</th>
                                            <th class="px-3 py-2 text-right font-semibold" style="color:#9a3412;">Nilai</th>
                                            <th class="px-3 py-2 text-right font-semibold" style="color:#9a3412;">
                                                Nilai Referensi
                                            </th>
                                            <th class="px-3 py-2 text-right font-semibold" style="color:#9a3412;">% dari Mean</th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9a3412;">Level</th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9a3412;">
                                                Z-score
                                                <span class="block font-normal text-[10px] opacity-70">(z = |x−μ|/σ)</span>
                                            </th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9a3412;">
                                                <input type="checkbox" id="checkAllInclude"
                                                    class="rounded border-orange-300 text-orange-500 focus:ring-orange-400"
                                                    onchange="toggleAllInclude(this)"
                                                    title="Centang = sertakan semua"> <span>Sertakan?</span>
                                            </th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9a3412;">
                                                <input type="checkbox" id="checkAllMark"
                                                    class="rounded border-orange-300 text-orange-500 focus:ring-orange-400"
                                                    onchange="toggleAllMark(this)"
                                                    title="Centang = catat semua sebagai anomali">
                                                <span>Catat sebagai Outlier</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="outTableBody" class="divide-y" style="divide-color:#fed7aa;"></tbody>
                                </table>
                            </div>
                    
                            {{-- Show more --}}
                            <div id="outPager"
                                    class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs
                                        transition-colors"
                                    style="color:#c2410c; border-top:1px solid #fed7aa;"
                                    onclick="showMore('out')">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M4 6l4 4 4-4"/>
                                </svg>
                                <span id="outShowMoreTxt"></span>
                            </div>
                    
                            {{-- Summary bar --}}
                            <div class="px-4 py-2.5 flex items-center gap-4 text-xs flex-wrap"
                                style="background:#fff7ed; border-top:1px solid #fed7aa;">
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-check text-green-500"></i>
                                    <span style="color:#166534; font-weight:600;" id="outlierIncludedCount"></span>
                                    <span style="color:#166534;">dari <span id="outlierTotalCount"></span> outlier akan diimport</span>
                                </span>
                                <span style="color:#fed7aa;">|</span>
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-flag text-orange-400"></i>
                                    <span style="color:#9a3412; font-weight:600;" id="outlierMarkedCount"></span>
                                    <span style="color:#c2410c;">akan dicatat sebagai anomali</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div id="unitConflictSection" class="hidden rounded-xl overflow-hidden" style="border: 1px solid #ec4899;">
                        <div class="flex items-center gap-2.5 px-4 py-3 cursor-pointer select-none" style="background: #fdf2f8;"
                            onclick="toggleSection('unit')">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0" style="background:#fce7f3;">
                                <i class="fas fa-scale-unbalanced text-xs" style="color:#db2777;"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold" style="color:#9d174d;">Bentrok Satuan (Metadata vs Rujukan)</p>
                                <p class="text-xs mt-0.5" style="color:#be185d;" id="unitSubtitle"></p>
                            </div>
                            <span id="unitBadge" class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                style="background:#fce7f3; color:#9d174d; border:1px solid #ec4899;"></span>
                            <svg id="unitChevron" class="w-4 h-4 shrink-0 transition-transform duration-200" style="color:#f472b6;"
                                viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                        </div>
                        <div id="unitBody" class="hidden" style="border-top:1px solid #fbcfe8;">
                            <div class="px-4 py-3 text-xs flex items-start gap-2.5" style="background:#fdf2f8; color:#9d174d; border-bottom:1px solid #fbcfe8;">
                                <i class="fas fa-info-circle mt-0.5 shrink-0" style="color:#ec4899;"></i>
                                <span>Teks <strong>Satuan Metadata</strong> dan <strong>Satuan Rujukan</strong> berbeda untuk baris ini. Nilai TIDAK dikonversi otomatis — pilih apakah tetap diimport, dan apakah dicatat sebagai anomali untuk direview.</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead style="background:#fdf2f8;">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#9d174d;">Metadata</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#9d174d;">Lokasi</th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9d174d;">Periode</th>
                                            <th class="px-3 py-2 text-right font-semibold" style="color:#9d174d;">Nilai</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#9d174d;">Satuan Metadata</th>
                                            <th class="px-3 py-2 text-left font-semibold" style="color:#9d174d;">Satuan Rujukan</th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9d174d;">
                                                <input type="checkbox" id="checkAllUnitInclude" onchange="toggleAllUnitInclude(this)"
                                                    title="Centang = sertakan semua"> Sertakan?
                                            </th>
                                            <th class="px-3 py-2 text-center font-semibold" style="color:#9d174d;">
                                                <input type="checkbox" id="checkAllUnitMark" onchange="toggleAllUnitMark(this)"
                                                    title="Centang = catat semua sebagai anomali"> Catat sebagai Anomali
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="unitTableBody" class="divide-y" style="divide-color:#fbcfe8;"></tbody>
                                </table>
                            </div>
                            <div id="unitPager" class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs"
                                style="color:#be185d; border-top:1px solid #fbcfe8;" onclick="showMore('unit')"></div>
                            <div class="px-4 py-2.5 flex items-center gap-4 text-xs flex-wrap" style="background:#fdf2f8; border-top:1px solid #fbcfe8;">
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-check text-green-500"></i>
                                    <span style="color:#166534; font-weight:600;" id="unitIncludedCount"></span>
                                    <span style="color:#166534;">dari <span id="unitTotalCount"></span> akan diimport</span>
                                </span>
                                <span style="color:#fbcfe8;">|</span>
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-flag" style="color:#db2777;"></i>
                                    <span style="color:#9d174d; font-weight:600;" id="unitMarkedCount"></span>
                                    <span style="color:#be185d;">akan dicatat sebagai anomali</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div id="validSection" class="hidden">
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                            <p class="text-sm font-semibold text-green-700">Data Valid | Siap Diimport</p>
                        </div>
                        <div class="border border-gray-200 rounded-xl overflow-x-auto">
                            <table class="w-full text-xs" id="validPivotTable">
                                <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                    <tr id="validHeaderTop">
                                        {{-- diisi JS: kolom Nama, span Tahun, Sumber --}}
                                    </tr>
                                    <tr id="validHeaderSub">
                                        {{-- diisi JS: kolom kosong, kolom per-periode, kosong x2 --}}
                                    </tr>
                                </thead>
                                <tbody id="validBody"></tbody>
                            </table>
                        </div>
                        <p id="validMore" class="hidden text-xs text-gray-400 text-right mt-1.5"></p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <button onclick="resetUpload()"
                                class="text-sm text-gray-500 hover:text-gray-700 transition-colors
                                    flex items-center gap-1.5">
                            <i class="fas fa-arrow-left"></i> Ganti File
                        </button>
                        <button id="btnImport" onclick="doImport()" disabled
                            class="flex items-center gap-2 px-6 py-2.5 rounded-md text-sm font-semibold
                                text-white shadow transition-colors
                                bg-sky-600 hover:bg-sky-700
                                disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed">
                            <i class="fas fa-file-import"></i>
                            <span id="btnImportText">Import Data</span>
                        </button>
                    </div>
                </div>

                <div id="importingBar" class="hidden mt-5">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm"
                        style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534;">
                        <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Menyimpan data ke database, mohon tunggu…
                    </div>
                </div>

                <div id="importResult" class="hidden mt-4"></div>

            </div>{{-- end panel-excel --}}

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
        JAVASCRIPT
    ══════════════════════════════════════════════════════════════ --}}
    <script>
    const CSRF        = '{{ csrf_token() }}';
    const PREVIEW_URL = '{{ route("data.preview_excel") }}';
    const IMPORT_URL  = '{{ route("data.import_excel") }}';

    const TIME_LIST     = @json($timeListJs);
    const LOCATION_LIST = @json($locationListJs);

    const PREFIX = {
        provinsi: 2,
        kabupaten: 4,
        kecamatan: 6,
        desa: 7,
    };

    // ══════════════════════════════════════════════════════════════
    // STATE METADATA
    // ══════════════════════════════════════════════════════════════
    let currentFrekuensi = '';
    let currentFlagDesimal = 0;

    /** Registry semua Tom Select instance, key = element id */
    const TS = {};
    
    // ══════════════════════════════════════════════════════════════
    // TOM SELECT — HELPERS
    // ══════════════════════════════════════════════════════════════

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

    initTomSelect('.tom-select'); 

    const PRODUSEN_URL = '{{ route("data.get_produsen_by_rujukan") }}';

    // Hook ke Tom Select rujukan — cari instance yang wrapping select[name="rujukan_id"]
    document.addEventListener('DOMContentLoaded', () => {
        // Tom Select untuk rujukan dibuat via .tom-select class
        // Kita perlu hook onChange setelah TomSelect init
        const rujukanEl = document.querySelector('select[name="rujukan_id"]');
        if (rujukanEl?.tomselect) {
            rujukanEl.tomselect.on('change', onRujukanChange);
        } else {
            // Fallback: observe jika TomSelect belum init saat DOM ready
            const observer = new MutationObserver(() => {
                if (rujukanEl?.tomselect) {
                    rujukanEl.tomselect.on('change', onRujukanChange);
                    observer.disconnect();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    });

    async function onRujukanChange(value) {
        const hidden  = document.getElementById('hiddenProdusenId');
        const infoEl  = document.getElementById('produsenInfo');
        const infoTxt = document.getElementById('produsenInfoText');

        if (!value) {
            hidden.value = '';
            infoEl.classList.add('hidden');
            return;
        }

        try {
            const resp = await fetch(`${PRODUSEN_URL}?rujukan_id=${value}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            });
            const json = await resp.json();

            if (json.success) {
                hidden.value      = json.produsen_id;
                infoTxt.textContent = json.nama_produsen;
                infoEl.classList.remove('hidden');
            } else {
                hidden.value = '';
                infoEl.classList.add('hidden');
            }
        } catch (e) {
            hidden.value = '';
            infoEl.classList.add('hidden');
        }
    }
    
    /** Buat instance Tom Select baru */
    function makeTS(id, extraOpts = {}, onChange = null) {
        const el = document.getElementById(id);
        if (!el) return null;
    
        const ts = new TomSelect(`#${id}`, Object.assign({
            allowEmptyOption : true,
            searchField      : ['text'],
            maxOptions       : 500,
            highlight        : true,
            selectOnTab      : true,
            openOnFocus      : true,
            render: {
                no_results: () => `<div class="no-results">Tidak ditemukan hasil yang cocok</div>`,
            },
        }, extraOpts));
    
        if (onChange) ts.on('change', onChange);
        TS[id] = ts;
        return ts;
    }
    
    /** Ambil nilai string dari Tom Select */
    function tsVal(id) { return TS[id]?.getValue() ?? ''; }
    
    /** Ambil nilai integer dari Tom Select */
    function tsInt(id) { return parseInt(TS[id]?.getValue()) || 0; }
    
    /** Set value (silent — tidak trigger event) */
    function tsSet(id, val) {
        const ts = TS[id];
        if (!ts) return;
        const wasDis = ts.isDisabled;
        if (wasDis) ts.enable();
        ts.setValue(String(val), true);
        if (wasDis) ts.disable();
    }
    
    /** Enable + hapus auto-class */
    function tsEnable(id) {
        const ts = TS[id];
        if (!ts) return;
        ts.enable();
        ts.wrapper.classList.remove('auto-field');
    }
    
    /** Disable + clear + ganti placeholder */
    function tsDisable(id, placeholder) {
        const ts = TS[id];
        if (!ts) return;
        ts.enable(); // enable dulu agar setValue jalan
        ts.setValue('', true);
        ts.disable();
        ts.wrapper.classList.remove('auto-field');
        if (placeholder) {
            ts.settings.placeholder = placeholder;
            ts.inputState();
        }
    }
    
    /** Disable dengan warna sky (field otomatis) */
    function tsAuto(id) {
        const ts = TS[id];
        if (!ts) return;
        ts.disable();
        ts.wrapper.classList.add('auto-field');
    }
    
    /** Clear opsi lama, isi opsi baru, enable */
    function tsRebuild(id, items, emptyLabel) {
        const ts = TS[id];
        if (!ts) return;
        ts.enable();
        ts.clearOptions();
        ts.addOption({ value: '', text: emptyLabel });
        items.forEach(loc =>
            ts.addOption({ value: String(loc.location_id), text: loc.nama_wilayah })
        );
        ts.setValue('', true);
        ts.settings.placeholder = emptyLabel;
        ts.inputState();
        ts.wrapper.classList.remove('auto-field');
    }

    // ══════════════════════════════════════════════════════════════
    // INIT — setelah DOM ready
    // ══════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
    
        // ── METADATA ──────────────────────────────────────────────
        makeTS('metadataSelect', { placeholder: 'Cari atau pilih metadata…' }, () => {
            onMetadataChange(document.getElementById('metadataSelect'));
        });
    
        // ── LOKASI ────────────────────────────────────────────────
        makeTS('selProvinsi',   { placeholder: 'Cari provinsi…' },         v => onProvinsiChange(v));
        makeTS('selKabupaten',  { placeholder: '— Pilih Provinsi dulu —' }, v => onKabupatenChange(v));
        makeTS('selKecamatan',  { placeholder: '— Pilih Kab/Kota dulu —' }, v => onKecamatanChange(v));
        makeTS('selDesa',       { placeholder: '— Pilih Kecamatan dulu —' });
    
        tsDisable('selKabupaten', '— Pilih Provinsi dulu —');
        tsDisable('selKecamatan', '— Pilih Kab/Kota dulu —');
        tsDisable('selDesa',      '— Pilih Kecamatan dulu —');
    
        // Lokasiinfo update tiap perubahan
        ['selProvinsi','selKabupaten','selKecamatan','selDesa'].forEach(id =>
            TS[id]?.on('change', updateLokasiInfo)
        );
    
        // ── WAKTU ─────────────────────────────────────────────────
        makeTS('filterDekade',   { placeholder: '—' }, () => onWaktuChange());
        makeTS('filterTahun',    { placeholder: '—' }, () => onWaktuChange());
        makeTS('filterSemester', { placeholder: '—' }, () => onWaktuChange());
        makeTS('filterKuartal',  { placeholder: '—' }, () => onWaktuChange());
        makeTS('filterBulan',    { placeholder: '—' }, () => onWaktuChange());
        ['filterDekade','filterTahun','filterSemester','filterKuartal','filterBulan']
            .forEach(id => tsDisable(id));
    
        // ── Restore old() values ──────────────────────────────────
        const metaSel = document.getElementById('metadataSelect');
        if (metaSel?.value) {
            TS['metadataSelect']?.setValue(metaSel.value, true);
            onMetadataChange(metaSel);
        }
    
        @if(old('provinsi_id'))
            TS['selProvinsi']?.setValue('{{ old("provinsi_id") }}', true);
            onProvinsiChange('{{ old("provinsi_id") }}');
        @endif
        @if(old('kabupaten_id'))
            setTimeout(() => {
                TS['selKabupaten']?.setValue('{{ old("kabupaten_id") }}', true);
                onKabupatenChange('{{ old("kabupaten_id") }}');
            }, 60);
        @endif
        @if(old('kecamatan_id'))
            setTimeout(() => {
                TS['selKecamatan']?.setValue('{{ old("kecamatan_id") }}', true);
                onKecamatanChange('{{ old("kecamatan_id") }}');
            }, 120);
        @endif
        @if(old('desa_id'))
            setTimeout(() => TS['selDesa']?.setValue('{{ old("desa_id") }}', true), 180);
        @endif
    
        // Restore nilai angka
        const oldNum = document.getElementById('hiddenNumberValue').value;
        if (oldNum) {
            const num = parseFloat(oldNum);
            if (!isNaN(num))
                document.getElementById('displayNumberValue').value =
                    formatRibuan(num, currentFlagDesimal === 1);
        }
    
        @if($errors->any() || session('duplicate_warning'))
            switchTab('manual');
        @endif
    });

    // ══════════════════════════════════════════════════════════════
    // 1. CASCADING LOKASI
    // ══════════════════════════════════════════════════════════════
    // location_id diasumsikan menggunakan kode BPS (prefix-based hierarchy)
    // Fallback: tampilkan semua jika prefix tidak cocok
    const PREFIX_LEN = { provinsi: 2, kabupaten: 4, kecamatan: 6 };
    
    function filterByPrefix(level, parentId, parentLevel) {
        const prefix = String(parentId).substring(0, PREFIX_LEN[parentLevel]);
        const all    = LOCATION_LIST.filter(l => l.level === level);
        const filt   = all.filter(l => String(l.location_id).startsWith(prefix));
        return filt.length > 0 ? filt : all; // fallback
    }
    
    function onProvinsiChange(value) {
        tsDisable('selKabupaten', '— Pilih Provinsi dulu —');
        tsDisable('selKecamatan', '— Pilih Kab/Kota dulu —');
        tsDisable('selDesa',      '— Pilih Kecamatan dulu —');
        if (!value) return;
    
        const items = filterByPrefix('kabupaten', value, 'provinsi');
        tsRebuild('selKabupaten', items, 'Cari kabupaten/kota…');
    }
    
    function onKabupatenChange(value) {
        tsDisable('selKecamatan', '— Pilih Kab/Kota dulu —');
        tsDisable('selDesa',      '— Pilih Kecamatan dulu —');
        if (!value) return;
    
        const items = filterByPrefix('kecamatan', value, 'kabupaten');
        tsRebuild('selKecamatan', items, 'Cari kecamatan…');
    }
    
    function onKecamatanChange(value) {
        tsDisable('selDesa', '— Pilih Kecamatan dulu —');
        if (!value) return;
    
        const items = filterByPrefix('desa', value, 'kecamatan');
        tsRebuild('selDesa', items, 'Cari desa/kelurahan…');
    }
    
    function updateLokasiInfo() {
        const labels = ['selProvinsi','selKabupaten','selKecamatan','selDesa']
            .map(id => {
                const v = tsVal(id);
                if (!v) return null;
                return TS[id]?.options[v]?.text ?? null;
            })
            .filter(Boolean);
    
        const infoEl = document.getElementById('lokasiInfo');
        if (labels.length > 0) {
            document.getElementById('lokasiInfoText').textContent = labels.join(' → ');
            infoEl.classList.remove('hidden');
        } else {
            infoEl.classList.add('hidden');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // 2. WAKTU (Frekuensi-aware)
    // ══════════════════════════════════════════════════════════════
    const FREKUENSI_CONFIG = {
        'dekade'  : { dekade:{editable:true,hint:'Wajib diisi'},   tahun:{hidden:true,hint:'Tidak berlaku'},   semester:{hidden:true,hint:'Tidak berlaku'}, quarter:{hidden:true,hint:'Tidak berlaku'}, bulan:{hidden:true,hint:'Tidak berlaku'} },
        'tahunan' : { dekade:{auto:true,hint:'Otomatis dari tahun'},tahun:{editable:true,hint:'Wajib diisi'},   semester:{hidden:true,hint:'Tidak berlaku'}, quarter:{hidden:true,hint:'Tidak berlaku'}, bulan:{hidden:true,hint:'Tidak berlaku'} },
        'semester': { dekade:{auto:true,hint:'Otomatis dari tahun'},tahun:{editable:true,hint:'Wajib diisi'},   semester:{editable:true,hint:'Wajib diisi'}, quarter:{hidden:true,hint:'Tidak berlaku'}, bulan:{hidden:true,hint:'Tidak berlaku'} },
        'quarter' : { dekade:{auto:true,hint:'Otomatis dari tahun'},tahun:{editable:true,hint:'Wajib diisi'},   semester:{auto:true,hint:'Otomatis dari kuartal'}, quarter:{editable:true,hint:'Wajib diisi'}, bulan:{hidden:true,hint:'Tidak berlaku'} },
        'bulanan' : { dekade:{auto:true,hint:'Otomatis dari tahun'},tahun:{editable:true,hint:'Wajib diisi'},   semester:{auto:true,hint:'Otomatis dari bulan'}, quarter:{auto:true,hint:'Otomatis dari bulan'}, bulan:{editable:true,hint:'Wajib diisi'} },
    };
    
    const FIELD_MAP = { dekade:'filterDekade', tahun:'filterTahun', semester:'filterSemester', quarter:'filterKuartal', bulan:'filterBulan' };
    const HINT_MAP  = { dekade:'hintDekade',   tahun:'hintTahun',   semester:'hintSemester',   quarter:'hintKuartal',   bulan:'hintBulan'  };
    
    function applyWaktuConfig(frekuensi) {
        const cfg = FREKUENSI_CONFIG[frekuensi];
        // Reset semua
        Object.keys(FIELD_MAP).forEach(k => {
            tsDisable(FIELD_MAP[k]);
            tsSet(FIELD_MAP[k], '0');
            document.getElementById(HINT_MAP[k]).textContent = '';
        });
        if (!cfg) return;
        Object.keys(cfg).forEach(k => {
            const c = cfg[k];
            const id = FIELD_MAP[k];
            document.getElementById(HINT_MAP[k]).textContent = c.hint || '';
            if (c.editable)    { tsEnable(id); tsSet(id, '0'); }
            else if (c.auto)   { tsAuto(id); }
            // hidden: tetap disabled
        });
        document.getElementById('waktuHint').classList.add('hidden');
    }
    
    function resetWaktuFields() {
        Object.keys(FIELD_MAP).forEach(k => {
            tsDisable(FIELD_MAP[k]);
            tsSet(FIELD_MAP[k], '0');
            document.getElementById(HINT_MAP[k]).textContent = '';
        });
        document.getElementById('selectedTimeId').value = '';
        document.getElementById('waktuInfo').classList.add('hidden');
        document.getElementById('waktuHint').classList.remove('hidden');
    }
    
    // Kalkulasi otomatis
    const tahunToDekade    = y => Math.floor(y / 10) * 10;
    const bulanToSemester  = b => b <= 6 ? 1 : 2;
    const bulanToKuartal   = b => Math.ceil(b / 3);
    const kuartalToSemester= q => q <= 2 ? 1 : 2;
    
    function onWaktuChange() {
        if (!currentFrekuensi) return;
        const tahun = tsInt('filterTahun');
        const q     = tsInt('filterKuartal');
        const bulan = tsInt('filterBulan');
    
        if (currentFrekuensi === 'tahunan'  && tahun) tsSet('filterDekade',   tahunToDekade(tahun));
        if (currentFrekuensi === 'semester' && tahun) tsSet('filterDekade',   tahunToDekade(tahun));
        if (currentFrekuensi === 'quarter') {
            if (tahun) tsSet('filterDekade',   tahunToDekade(tahun));
            if (q)     tsSet('filterSemester', kuartalToSemester(q));
        }
        if (currentFrekuensi === 'bulanan') {
            if (tahun) tsSet('filterDekade',   tahunToDekade(tahun));
            if (bulan) { tsSet('filterSemester', bulanToSemester(bulan)); tsSet('filterKuartal', bulanToKuartal(bulan)); }
        }
        resolveTimeId();
    }
    
    function resolveTimeId() {
        const dekade   = tsInt('filterDekade');
        const tahun    = tsInt('filterTahun');
        const semester = tsInt('filterSemester');
        const quarter  = tsInt('filterKuartal');
        const bulan    = tsInt('filterBulan');
        let sel = null;
    
        switch (currentFrekuensi) {
            case 'dekade'  : if (dekade)              sel=TIME_LIST.find(t=>t.decade==dekade&&t.year==0&&t.month==0); break;
            case 'tahunan' : if (tahun)               sel=TIME_LIST.find(t=>t.year==tahun&&t.month==0&&t.quarter==0&&t.semester==0); break;
            case 'semester': if (tahun&&semester)     sel=TIME_LIST.find(t=>t.year==tahun&&t.semester==semester&&t.month==0&&t.quarter==0); break;
            case 'quarter' : if (tahun&&quarter)      sel=TIME_LIST.find(t=>t.year==tahun&&t.quarter==quarter&&t.month==0); break;
            case 'bulanan' : if (tahun&&bulan)        sel=TIME_LIST.find(t=>t.year==tahun&&t.month==bulan); break;
        }
    
        const input  = document.getElementById('selectedTimeId');
        const infoEl = document.getElementById('waktuInfo');
        const infoTxt= document.getElementById('waktuInfoText');
        if (sel) { input.value=sel.time_id; infoEl.classList.remove('hidden'); infoTxt.textContent='Waktu dipilih: '+fmtTimeLabel(sel); }
        else     { input.value='';          infoEl.classList.add('hidden'); }
    }
    
    function fmtTimeLabel(t) {
        if (t.month    !=0) return `${t.year} — Bulan ${t.month}`;
        if (t.quarter  !=0) return `${t.year} — Kuartal ${t.quarter} (Semester ${t.semester})`;
        if (t.semester !=0) return `${t.year} — Semester ${t.semester}`;
        if (t.year     !=0) return `Tahun ${t.year} (Dekade ${t.decade})`;
        return `Dekade ${t.decade}`;
    }
    // ══════════════════════════════════════════════════════════════
    // 3. NILAI ANGKA (flag_desimal + format ribuan)
    // ══════════════════════════════════════════════════════════════

    function parseFormattedNumber(str) {
        if (!str) return null;
        // Hapus titik ribuan (id-ID), ganti koma desimal → titik
        const clean = str.replace(/\./g, '').replace(',', '.');
        const val = parseFloat(clean);
        return isNaN(val) ? null : val;
    }

    function formatRibuan(val, allowDecimal) {
        if (val === null || val === undefined || val === '') return '';
        const num = parseFloat(String(val).replace(/\./g, '').replace(',', '.'));
        if (isNaN(num)) return '';
        if (allowDecimal && num % 1 !== 0) {
            return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        return Math.round(num).toLocaleString('id-ID');
    }

    let isTypingNumber = false;

    function onNumberInput(input) {
        isTypingNumber = true;
        const raw = input.value;

        // Hanya izinkan angka, titik (ribuan), koma (desimal jika boleh)
        let clean = raw;
        if (currentFlagDesimal === 0) {
            // Hanya angka dan titik (untuk ribuan sementara user mengetik)
            clean = raw.replace(/[^0-9.]/g, '');
        } else {
            // Boleh koma untuk desimal
            clean = raw.replace(/[^0-9.,]/g, '');
        }
        if (input.value !== clean) input.value = clean;

        // Simpan ke hidden field (nilai mentah sementara)
        const numVal = parseFormattedNumber(clean) ?? clean.replace(/\./g, '').replace(',', '.');
        document.getElementById('hiddenNumberValue').value = numVal;
    }

    function onNumberBlur(input) {
        isTypingNumber = false;
        const raw  = input.value;
        if (!raw) {
            document.getElementById('hiddenNumberValue').value = '';
            return;
        }

        // Parse
        let val = parseFormattedNumber(raw);
        if (val === null) {
            // Coba parse langsung
            val = parseFloat(raw.replace(',', '.'));
        }

        if (isNaN(val) || val === null) {
            document.getElementById('hiddenNumberValue').value = '';
            input.value = '';
            return;
        }

        // Validasi: jika flag_desimal = 0, bulatkan
        if (currentFlagDesimal === 0) {
            val = Math.round(val);
        }

        // Simpan value asli ke hidden
        document.getElementById('hiddenNumberValue').value = val;

        // Format display
        input.value = formatRibuan(val, currentFlagDesimal === 1);
    }

    function onNumberFocus(input) {
        isTypingNumber = true;
        // Saat fokus, tampilkan nilai mentah (tanpa format ribuan) untuk kemudahan edit
        const hiddenVal = document.getElementById('hiddenNumberValue').value;
        if (hiddenVal) {
            // Tampilkan angka bersih dengan koma desimal jika ada
            const num = parseFloat(hiddenVal);
            if (!isNaN(num)) {
                if (currentFlagDesimal === 1 && num % 1 !== 0) {
                    input.value = String(num).replace('.', ',');
                } else {
                    input.value = String(Math.round(num));
                }
            }
        }
    }

    function applyFlagDesimal(flag) {
        currentFlagDesimal = parseInt(flag) || 0;
        const input      = document.getElementById('displayNumberValue');
        const badge      = document.getElementById('desimalBadge');
        const infoEl     = document.getElementById('flagDesimalInfo');
        const infoTxt    = document.getElementById('flagDesimalText');

        if (currentFlagDesimal === 1) {
            input.setAttribute('inputmode', 'decimal');
            badge.classList.remove('hidden');
            infoEl.classList.remove('hidden');
            infoTxt.className = 'text-xs text-sky-600';
            infoTxt.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Metadata ini mendukung nilai desimal (contoh: 1.234,56)';
        } else {
            input.setAttribute('inputmode', 'numeric');
            badge.classList.add('hidden');
            infoEl.classList.remove('hidden');
            infoTxt.className = 'text-xs text-gray-400';
            infoTxt.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Metadata ini hanya menerima bilangan bulat — nilai desimal akan dibulatkan';
        }

        // Reset dan reformat nilai yang sudah ada
        const existingVal = document.getElementById('hiddenNumberValue').value;
        if (existingVal) {
            const num = parseFloat(existingVal);
            if (!isNaN(num)) {
                const rounded = currentFlagDesimal === 0 ? Math.round(num) : num;
                document.getElementById('hiddenNumberValue').value = rounded;
                if (!isTypingNumber) {
                    document.getElementById('displayNumberValue').value =
                        formatRibuan(rounded, currentFlagDesimal === 1);
                }
            }
        }
    }

    // ══════════════════════════════════════════════════════════════
    // METADATA CHANGE
    // ══════════════════════════════════════════════════════════════
    function onMetadataChange(select) {
        const opt = select.options[select.selectedIndex];
        const infoEl = document.getElementById('metadataInfo');
    
        if (opt && (opt.dataset.tipe || opt.dataset.satuan || opt.dataset.frekuensi)) {
            document.getElementById('metadataTipe').textContent      = 'Tipe: '+(opt.dataset.tipe||'-');
            document.getElementById('metadataSatuan').textContent    = opt.dataset.satuan||'-';
            document.getElementById('metadataFrekuensi').textContent = opt.dataset.frekuensi||'-';
            document.getElementById('satuanLabel').textContent       = opt.dataset.satuan?`(${opt.dataset.satuan})`:'';
            infoEl.classList.remove('hidden');
        } else {
            infoEl.classList.add('hidden');
            document.getElementById('satuanLabel').textContent = '';
        }
    
        currentFrekuensi = opt ? (opt.dataset.frekuensi||'').toLowerCase().trim() : '';
        resetWaktuFields();
        if (currentFrekuensi) applyWaktuConfig(currentFrekuensi);
        applyFlagDesimal(opt ? (opt.dataset.flagDesimal ?? '0') : '0');
    }

    // RUJUKAN DATA


    function resetWaktuFields() {
        Object.keys(FIELD_MAP).forEach(key => {
            const sel  = document.getElementById(FIELD_MAP[key]);
            const hint = document.getElementById(HINT_MAP[key]);
            sel.disabled = true;
            sel.value    = '0';
            sel.classList.add('bg-gray-50', 'text-gray-400');
            sel.classList.remove('bg-white', 'text-gray-800', 'bg-sky-50', 'text-sky-700');
            hint.textContent = '';
        });
        document.getElementById('selectedTimeId').value = '';
        document.getElementById('waktuInfo').classList.add('hidden');
        document.getElementById('waktuHint').classList.remove('hidden');
    }

    // ══════════════════════════════════════════════════════════════
    // TAB SWITCHER
    // ══════════════════════════════════════════════════════════════
    function switchTab(tab) {
        document.getElementById('panel-manual').classList.toggle('hidden', tab !== 'manual');
        document.getElementById('panel-excel').classList.toggle('hidden',  tab !== 'excel');
        const active   = 'border-sky-500 text-sky-600';
        const inactive = 'border-transparent text-gray-400 hover:text-gray-600';
        document.getElementById('tab-manual').className =
            `tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ${tab === 'manual' ? active : inactive}`;
        document.getElementById('tab-excel').className =
            `tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ${tab === 'excel' ? active : inactive}`;
    }

    // ══════════════════════════════════════════════════════════════
    // EXCEL UPLOAD (tidak diubah dari versi asli)
    // ══════════════════════════════════════════════════════════════
    let currentFile = null;
    let previewData = null;
    const ROWS_PER_PAGE = 5;
    const sectionState = {
        err:  { data: [], page: 0, checkState: {} },
        dup:  { data: [], page: 0 },
        meta: { data: [], page: 0 },
        out:  { data: [], page: 0, includeState: {}, markState: {} },
        satuan: { data: [], page: 0, resolutions: {} },
        valid: { groups: [], page: 0, periodOrder: [] },
    };

    function handleDrop(e) {
        e.preventDefault();
        const zone = document.getElementById('dropZone');
        zone.style.borderColor = '';
        zone.style.background  = '';
        const file = e.dataTransfer.files[0];
        if (file) onFileSelected(file);
    }

    function onFileSelected(file) {
        if (!file.name.match(/\.(xlsx|xls)$/i)) {
            showImportAlert('error', 'File harus berformat .xlsx atau .xls');
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showImportAlert('error', 'Ukuran file maksimal 10MB');
            return;
        }
        currentFile = file;
        previewData = null;
        document.getElementById('dropZone').classList.add('hidden');
        const bar = document.getElementById('fileInfoBar');
        bar.classList.remove('hidden');
        document.getElementById('fileInfoName').textContent = file.name;
        document.getElementById('fileInfoSize').textContent =
            file.size > 1048576
                ? (file.size / 1048576).toFixed(2) + ' MB'
                : (file.size / 1024).toFixed(1) + ' KB';
        doPreview();
    }

    function resetUpload() {
        currentFile = null;
        previewData = null;
        document.getElementById('fileExcel').value = '';
        document.getElementById('dropZone').classList.remove('hidden');
        document.getElementById('fileInfoBar').classList.add('hidden');
        document.getElementById('loadingBar').classList.add('hidden');
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('importingBar').classList.add('hidden');
        sectionState.err  = { data: [], page: 0 };
        sectionState.dup  = { data: [], page: 0 };
        sectionState.meta = { data: [], page: 0 };
        sectionState.satuan = { data: [], page: 0 };            
        sectionState.unit   = { data: [], page: 0, includeState: {}, markState: {} };
    }

    async function doPreview() {
        document.getElementById('loadingBar').classList.remove('hidden');
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('importResult').classList.add('hidden');
        const form = new FormData();
        form.append('_token', CSRF);
        form.append('file_excel', currentFile);
        try {
            const resp = await fetch(PREVIEW_URL, { method: 'POST', body: form });
            document.getElementById('loadingBar').classList.add('hidden');
            if (!resp.ok) {
                let errMsg = 'File ditolak server (status ' + resp.status + ').';
                try {
                    const errJson = await resp.json();
                    if (errJson.errors?.file_excel) errMsg = errJson.errors.file_excel[0];
                    else if (errJson.message) errMsg = errJson.message;
                } catch (_) {}
                showImportAlertOnly(errMsg);
                return;
            }
            const json = await resp.json();
            if (!json.success) {
                showImportAlertOnly(json.message || 'Gagal membaca file.');
                return;
            }
            previewData = json;
            renderPreview(json);
        } catch (err) {
            document.getElementById('loadingBar').classList.add('hidden');
            showImportAlertOnly('Terjadi kesalahan jaringan: ' + err.message);
        }
    }

    function showImportAlertOnly(msg) {
        const el = document.getElementById('importResult');
        el.innerHTML = `
            <div class="flex items-start gap-3 px-4 py-3 rounded-lg text-sm"
                style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">
                <i class="fas fa-exclamation-circle text-red-400 mt-0.5 shrink-0"></i>
                <span>${esc(msg)}</span>
            </div>`;
        el.classList.remove('hidden');
    }

    function renderPreview(json) {
        document.getElementById('previewSection').classList.remove('hidden');
        const periodLabel = {
            tahunan:'Tahunan', semester:'Semester', quarter:'Quarter', bulanan:'Bulanan', unknown:'?',
        }[json.period_type] ?? json.period_type;
        const invalidMetaCount = (json.invalid_metadata || []).length;
        document.getElementById('statsGrid').innerHTML = `
            <div class="rounded-lg p-3 text-center" style="background:#f0f9ff; border:1px solid #bae6fd;">
                <p class="text-xl font-bold" style="color:#0369a1;">${json.total_rows}</p>
                <p class="text-xs mt-0.5" style="color:#0369a1;">Baris Excel</p>
            </div>
            <div class="rounded-lg p-3 text-center" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                <p class="text-xl font-bold" style="color:#166534;">${json.valid}</p>
                <p class="text-xs mt-0.5" style="color:#166534;">Record Valid</p>
            </div>
            <div class="rounded-lg p-3 text-center" style="background:#fffbeb; border:1px solid #fde68a;">
                <p class="text-xl font-bold" style="color:#92400e;">${json.duplicate}</p>
                <p class="text-xs mt-0.5" style="color:#92400e;">Duplikat</p>
            </div>
            <div class="rounded-lg p-3 text-center" style="background:#fff7ed; border:1px solid #fed7aa;">
                <p class="text-xl font-bold" style="color:#9a3412;">${json.outlier_count || 0}</p>
                <p class="text-xs mt-0.5" style="color:#c2410c;">Outlier Z-Score</p>
            </div>
            <div class="rounded-lg p-3 text-center" style="background:#fef2f2; border:1px solid #fecaca;">
                <p class="text-xl font-bold" style="color:#b91c1c;">${json.error}</p>
                <p class="text-xs mt-0.5" style="color:#b91c1c;">Baris Error</p>
            </div>
            <div class="rounded-lg p-3 text-center" style="background:#faf5ff; border:1px solid #e9d5ff;">
                <p class="text-xl font-bold" style="color:#6d28d9;">${invalidMetaCount}</p>
                <p class="text-xs mt-0.5" style="color:#6d28d9;">Metadata Tidak Valid</p>
            </div>`;

        const timeErrors     = (json.errors || []).filter(e => e.message?.includes('time_id'));
        const timeNotFoundEl = document.getElementById('timeNotFoundAlert');
        if (timeErrors.length > 0) {
            const periods = [...new Set(timeErrors.map(e => e.period))].filter(Boolean);
            document.getElementById('timeNotFoundDetail').textContent =
                `Periode tidak terdaftar: ${periods.join(', ')}. Tipe periode terdeteksi: ${periodLabel}.`;
            timeNotFoundEl.classList.remove('hidden');
        } else {
            timeNotFoundEl.classList.add('hidden');
        }

        initSections(json);

        const validSection = document.getElementById('validSection');
        const validBody    = document.getElementById('validBody');
        const validMore    = document.getElementById('validMore');

        if (json.rows && json.rows.length > 0) {
            validSection.classList.remove('hidden');

            const periodOrder = [];
            const periodSet   = new Set();
            json.rows.forEach(r => {
                const p = String(r.period_label);
                if (!periodSet.has(p)) { periodSet.add(p); periodOrder.push(p); }
            });

            const groups = new Map();
            json.rows.forEach(r => {
                const key = `${r.metadata_id}__${r.location_id}__${r.rujukan_id ?? ''}`;
                if (!groups.has(key)) {
                    groups.set(key, {
                        nama:    r.nama_metadata ?? String(r.metadata_id),
                        wilayah: r.nama_wilayah  ?? String(r.location_id),
                        satuan:  r.satuan_data   ?? '',
                        rujukan: r.nama_rujukan  ?? String(r.rujukan_id),
                        values:  {},
                    });
                }
                groups.get(key).values[String(r.period_label)] = r.number_value;
            });

            sectionState.valid = {
                groups:      [...groups.values()],
                page:        0,
                periodOrder: periodOrder,
            };

            // Build header (tidak berubah)
            const hTop = document.getElementById('validHeaderTop');
            hTop.innerHTML = `
                <th class="px-3 py-2 text-left font-medium" style="width:30%;">Nama</th>
                <th colspan="${periodOrder.length}" class="px-3 py-2 text-center font-medium border-x border-gray-200">
                    ${json.period_type === 'tahunan' ? 'Tahun' : json.period_type === 'bulanan' ? 'Bulan' : json.period_type === 'semester' ? 'Semester' : 'Periode'}
                </th>
                <th class="px-3 py-2 text-left font-medium">Satuan</th>
                <th class="px-3 py-2 text-left font-medium">Sumber Rujukan</th>`;

            const hSub = document.getElementById('validHeaderSub');
            hSub.innerHTML = `<th class="px-3 py-2 border-b border-gray-200"></th>`
                + periodOrder.map(p => `<th class="px-3 py-2 text-center font-medium border-b border-gray-200 whitespace-nowrap">${esc(p)}</th>`).join('')
                + `<th class="px-3 py-2 border-b border-gray-200"></th><th class="px-3 py-2 border-b border-gray-200"></th>`;

            renderValidRows();
        } else {
            validSection.classList.add('hidden');
        }

        const btn  = document.getElementById('btnImport');
        const text = document.getElementById('btnImportText');
        if (json.valid > 0) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-50', 'text-gray-400');
            btn.classList.add('bg-sky-600', 'hover:bg-sky-700', 'text-white');
            text.textContent = `Import ${json.valid.toLocaleString('id-ID')} Record`;
        } else {
            btn.disabled = true;
            btn.classList.remove('bg-sky-600', 'hover:bg-sky-700', 'text-white');
            btn.classList.add('bg-gray-50', 'text-gray-400');
            text.textContent = 'Tidak Ada Data Valid';
        }
    }

    const VALID_ROWS_PER_PAGE = 10;

    function renderValidRows() {
        const sv   = sectionState.valid;
        const page = sv.page;
        const rows = sv.groups.slice(page * VALID_ROWS_PER_PAGE, (page + 1) * VALID_ROWS_PER_PAGE);
        const periodOrder = sv.periodOrder;

        let prevNama = null;
        document.getElementById('validBody').innerHTML = rows.map((g, i) => {
            const showGroupLabel = g.nama !== prevNama;
            prevNama = g.nama;
            const groupRow = showGroupLabel ? `
                <tr><td colspan="${periodOrder.length + 3}"
                    class="px-3 py-1.5 text-xs text-gray-500 bg-gray-50 border-b border-gray-100 font-medium">
                    ${esc(g.nama)}</td></tr>` : '';
            const cells = periodOrder.map(p => {
                const v = g.values[p];
                return v !== undefined
                    ? `<td class="px-3 py-2 text-center font-mono text-gray-800">${formatNum(v)}</td>`
                    : `<td class="px-3 py-2 text-center text-gray-300">—</td>`;
            }).join('');
            return groupRow + `
                <tr class="${i % 2 === 1 ? 'bg-gray-50/40' : ''}">
                    <td class="px-3 py-2 pl-6 text-gray-600">${esc(g.wilayah)}</td>
                    ${cells}
                    <td class="px-3 py-2 text-gray-600">${esc(g.satuan)}</td>
                    <td class="px-3 py-2">
                        <span class="inline-block text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500 whitespace-nowrap">
                            ${esc(g.rujukan)}
                        </span>
                    </td>
                </tr>`;
        }).join('');

        // Render pagination valid
        const total      = sv.groups.length;
        const totalPages = Math.ceil(total / VALID_ROWS_PER_PAGE);
        const cur        = sv.page;
        const start      = cur * VALID_ROWS_PER_PAGE + 1;
        const end        = Math.min((cur + 1) * VALID_ROWS_PER_PAGE, total);
        const validMore  = document.getElementById('validMore');
        validMore.classList.remove('hidden');
        validMore.innerHTML = totalPages > 1 ? `
            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:8px;">
                <span>${start}–${end} dari ${total} record valid</span>
                <div style="display:flex; gap:4px; align-items:center;">
                    <button onclick="gotoValidPage(${cur - 1})" ${cur === 0 ? 'disabled' : ''}
                        style="padding:3px 10px; font-size:12px; border:1px solid #e5e7eb; border-radius:4px; background:#fff; cursor:pointer; ${cur === 0 ? 'opacity:0.4;cursor:not-allowed;' : ''}">‹</button>
                    <span style="font-size:12px; padding:0 4px;">${cur + 1} / ${totalPages}</span>
                    <button onclick="gotoValidPage(${cur + 1})" ${cur >= totalPages - 1 ? 'disabled' : ''}
                        style="padding:3px 10px; font-size:12px; border:1px solid #e5e7eb; border-radius:4px; background:#fff; cursor:pointer; ${cur >= totalPages - 1 ? 'opacity:0.4;cursor:not-allowed;' : ''}">›</button>
                </div>
            </div>` : `<span>Menampilkan ${total} record valid</span>`;
    }

    function gotoValidPage(page) {
        const sv = sectionState.valid;
        const totalPages = Math.ceil(sv.groups.length / VALID_ROWS_PER_PAGE);
        sv.page = Math.max(0, Math.min(page, totalPages - 1));
        renderValidRows();
    }

    function initSections(json) {
        sectionState.err  = { data: json.errors          || [], page: 0 };
        sectionState.dup  = { data: json.duplicates       || [], page: 0 };
        sectionState.meta = { data: json.invalid_metadata || [], page: 0 };
        sectionState.out  = { data: json.outliers         || [], page: 0,
                            includeState: {}, markState: {} };
        sectionState.out.data.forEach(rec => {
            const key = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
            sectionState.out.includeState[key] = true; 
            sectionState.out.markState[key]    = true;  
        });

        // Error section
        const errSection = document.getElementById('errorSection');
        if (sectionState.err.data.length > 0) {
            document.getElementById('errBadge').textContent = sectionState.err.data.length + ' baris';
            errSection.classList.remove('hidden');
            document.getElementById('errBody').classList.add('hidden');
            document.getElementById('errChevron').style.transform = '';
        } else errSection.classList.add('hidden');

        // Dup section
        const dupSection = document.getElementById('dupSection');
        if (sectionState.dup.data.length > 0) {
            document.getElementById('dupBadge').textContent = sectionState.dup.data.length + ' entri';
            dupSection.classList.remove('hidden');
            document.getElementById('dupBody').classList.add('hidden');
            document.getElementById('dupChevron').style.transform = '';
        } else dupSection.classList.add('hidden');

        // Meta section
        const metaSection = document.getElementById('invalidMetaSection');
        const metaData    = sectionState.meta.data;
        if (metaData.length > 0) {
            document.getElementById('metaBadge').textContent = metaData.length + ' metadata';
            const notFound  = metaData.filter(m => m.reason === 'not_found').length;
            const notActive = metaData.filter(m => m.reason === 'not_active').length;
            const mismatch  = metaData.filter(m => m.reason === 'name_mismatch').length;
            const parts = [];
            if (notFound  > 0) parts.push(`${notFound} tidak ditemukan`);
            if (notActive > 0) parts.push(`${notActive} belum aktif`);
            if (mismatch  > 0) parts.push(`${mismatch} nama tidak cocok`);
            document.getElementById('invalidMetaSubtitle').textContent =
                parts.join(' · ') + ' — data dilewati';
            metaSection.classList.remove('hidden');
            document.getElementById('metaBody').classList.add('hidden');
            document.getElementById('metaChevron').style.transform = '';
        } else metaSection.classList.add('hidden');

        // ── Outlier section ────────────────────────────────────────
        const outSection = document.getElementById('outlierSection');
        const outData    = sectionState.out.data;
        if (outData.length > 0) {
            document.getElementById('outBadge').textContent = outData.length + ' titik data';
            const metaNames = [...new Set(outData.map(r => r.nama_metadata))].length;
            document.getElementById('outlierSubtitle').textContent =
                `${outData.length} nilai menyimpang dari pola baris di ${metaNames} metadata, pilih tindakan untuk setiap data`;
            outSection.classList.remove('hidden');
            document.getElementById('outBody').classList.add('hidden');
            document.getElementById('outChevron').style.transform = '';
            // Auto-buka section outlier agar user tidak melewatinya
            toggleSection('out');
        } else {
            outSection.classList.add('hidden');
        }
        sectionState.satuan = { data: json.invalid_satuan || [], page: 0 };
        const invalidSatuanSection = document.getElementById('invalidSatuanSection');
        if (sectionState.satuan.data.length > 0) {
            document.getElementById('satuanBadge').textContent = sectionState.satuan.data.length + ' baris';
            document.getElementById('satuanSubtitle').textContent = sectionState.satuan.data.length + ' baris dilewati karena satuan belum terdaftar';
            invalidSatuanSection.classList.remove('hidden');
            document.getElementById('satuanBody').classList.add('hidden');
            document.getElementById('satuanChevron').style.transform = '';
        } else {
            invalidSatuanSection.classList.add('hidden');
        }

        sectionState.unit = { data: json.unit_conflicts || [], page: 0, includeState: {}, markState: {} };
        sectionState.unit.data.forEach(rec => {
            const key = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
            sectionState.unit.includeState[key] = true;
            sectionState.unit.markState[key]    = true;
        });
        const unitSection = document.getElementById('unitConflictSection');
        if (sectionState.unit.data.length > 0) {
            document.getElementById('unitBadge').textContent = sectionState.unit.data.length + ' titik data';
            document.getElementById('unitSubtitle').textContent = sectionState.unit.data.length + ' data punya satuan metadata & rujukan berbeda';
            unitSection.classList.remove('hidden');
            document.getElementById('unitBody').classList.add('hidden');
            document.getElementById('unitChevron').style.transform = '';
            toggleSection('unit'); // auto-buka, sama seperti outlier
        } else {
            unitSection.classList.add('hidden');
        }
    }

    function toggleSection(type) {
        const ids = {
            err:  { body: 'errBody',  chevron: 'errChevron'  },
            dup:  { body: 'dupBody',  chevron: 'dupChevron'  },
            meta: { body: 'metaBody', chevron: 'metaChevron' },
            out:  { body: 'outBody',  chevron: 'outChevron'  },
            satuan: { body: 'satuanBody', chevron: 'satuanChevron' },
            unit: { body: 'unitBody', chevron: 'unitChevron' },
        };
        const entry = ids[type];
        if (!entry) return;
        const body    = document.getElementById(entry.body);
        const chevron = document.getElementById(entry.chevron);
        const isOpen  = !body.classList.contains('hidden');
        body.classList.toggle('hidden', isOpen);
        chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
        if (!isOpen) renderRows(type);
    }

    // Severity berdasarkan standard z-score (konsisten dengan DB/createAnomaliesForPendingKeys)
            function getSeverityFromZ(z) {
                if (z > 40)  return 'critical';
                if (z >= 31) return 'high';
                if (z >= 10) return 'medium';
                return 'low';
            }

            // Alias agar tidak perlu ubah pemanggil lain (backward compat)
            function getSeverityFromMZ(mz) { return getSeverityFromZ(mz); }
 
            function getLevelBadge(z) {
                const absZ = Math.abs(parseFloat(z));
                if (absZ > 40) return {
                    label: 'Critical',
                    style: 'background:#fdf4ff; color:#86198f; border:1px solid #e879f9;',
                    dot:   'background:#a21caf;'
                };
                if (absZ >= 31) return {
                    label: 'High',
                    style: 'background:#fef2f2; color:#b91c1c; border:1px solid #f87171;',
                    dot:   'background:#dc2626;'
                };
                if (absZ >= 10) return {
                    label: 'Medium',
                    style: 'background:#fff7ed; color:#9a3412; border:1px solid #fb923c;',
                    dot:   'background:#ea580c;'
                };
                return {
                    label: 'Low',
                    style: 'background:#fefce8; color:#854d0e; border:1px solid #fde047;',
                    dot:   'background:#ca8a04;'
                };
            }

            function updateOutlierSummary() {
                const s     = sectionState.out;
                const total = s.data.length;

                const marked   = Object.values(s.markState).filter(Boolean).length;
                const included = Object.values(s.includeState).filter(Boolean).length;

                document.getElementById('outlierIncludedCount').textContent = included;
                document.getElementById('outlierMarkedCount').textContent   = marked;
                document.getElementById('outlierTotalCount').textContent    = total;

                // Sync master checkbox Sertakan
                const masterInclude = document.getElementById('checkAllInclude');
                if (masterInclude) {
                    masterInclude.checked       = included === total && total > 0;
                    masterInclude.indeterminate = included > 0 && included < total;
                }

                // Sync master checkbox Mark — hitung hanya dari yang di-include
                const includedKeys = Object.entries(s.includeState)
                    .filter(([, v]) => v !== false).map(([k]) => k);
                const markedIncluded = includedKeys.filter(k => s.markState[k]).length;

                const masterMark = document.getElementById('checkAllMark');
                if (masterMark) {
                    masterMark.checked       = includedKeys.length > 0 && markedIncluded === includedKeys.length;
                    masterMark.indeterminate = markedIncluded > 0 && markedIncluded < includedKeys.length;
                }
            }

            function setOutlierRowState(cb, enabled) {
                const row  = cb.closest('tr');
                if (!row) return;
                const mark = row.querySelector('.outlier-mark');
                if (!mark) return;
                mark.disabled = !enabled;
                mark.checked  = enabled ? mark.checked : false;
                mark.classList.toggle('opacity-50', !enabled);
                mark.classList.toggle('cursor-not-allowed', !enabled);
            }

            function syncOutlierRow(cb) {
                const key = cb.dataset.key;
                const s   = sectionState.out;
                
                s.includeState[key] = cb.checked;
                setOutlierRowState(cb, cb.checked);
                
                // Jika di-exclude, mark otomatis false
                // Jika di-include kembali, kembalikan mark ke true (default)
                if (!cb.checked) {
                    s.markState[key] = false;
                    // Update checkbox mark di DOM jika ada
                    const row  = cb.closest('tr');
                    const mark = row?.querySelector('.outlier-mark');
                    if (mark) mark.checked = false;
                } else {
                    // Restore mark ke true saat re-include
                    s.markState[key] = true;
                    const row  = cb.closest('tr');
                    const mark = row?.querySelector('.outlier-mark');
                    if (mark) {
                        mark.checked  = true;
                        mark.disabled = false;
                        mark.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }
                
                updateOutlierSummary();
            }

            function onMarkChange(cb) {
                const key = cb.dataset.key;
                const s   = sectionState.out;
                if (s.includeState[key] !== false) {
                    s.markState[key] = cb.checked;
                }
                updateOutlierSummary();
            }

            function toggleAllInclude(master) {
                const s = sectionState.out;
                // Update semua state
                s.data.forEach(rec => {
                    const key = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
                    s.includeState[key] = master.checked;
                    if (!master.checked) s.markState[key] = false;
                    else s.markState[key] = true;
                });
                // Update DOM halaman aktif
                document.querySelectorAll('.outlier-include').forEach(cb => {
                    cb.checked = master.checked;
                    setOutlierRowState(cb, master.checked);
                });
                document.querySelectorAll('.outlier-mark').forEach(cb => {
                    cb.checked = master.checked;
                });
                updateOutlierSummary();
            }

            function toggleAllMark(master) {
                const s = sectionState.out;
                // Hanya update yang di-include
                s.data.forEach(rec => {
                    const key = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
                    if (s.includeState[key] !== false) {
                        s.markState[key] = master.checked;
                    }
                });
                // Update DOM halaman aktif
                document.querySelectorAll('.outlier-mark').forEach(cb => {
                    const key = cb.dataset.key;
                    if (s.includeState[key] !== false) {
                        cb.checked = master.checked;
                    }
                });
                updateOutlierSummary();
            }

            function updateUnitConflictSummary() {
                const s = sectionState.unit, total = s.data.length;
                const included = Object.values(s.includeState).filter(Boolean).length;
                const marked   = Object.entries(s.markState).filter(([k,v]) => v && s.includeState[k] !== false).length;
                document.getElementById('unitIncludedCount').textContent = included;
                document.getElementById('unitMarkedCount').textContent   = marked;
                document.getElementById('unitTotalCount').textContent    = total;
                const mi = document.getElementById('checkAllUnitInclude');
                if (mi) { mi.checked = included === total && total > 0; mi.indeterminate = included > 0 && included < total; }
                const mm = document.getElementById('checkAllUnitMark');
                if (mm) { mm.checked = marked === included && included > 0; mm.indeterminate = marked > 0 && marked < included; }
            }

            function syncUnitConflictRow(cb) {
                const key = cb.dataset.key, s = sectionState.unit;
                s.includeState[key] = cb.checked;
                const row = cb.closest('tr'), mark = row?.querySelector('.unit-mark');
                if (!cb.checked) {
                    s.markState[key] = false;
                    if (mark) { mark.checked = false; mark.disabled = true; mark.classList.add('opacity-50','cursor-not-allowed'); }
                } else {
                    s.markState[key] = true;
                    if (mark) { mark.checked = true; mark.disabled = false; mark.classList.remove('opacity-50','cursor-not-allowed'); }
                }
                updateUnitConflictSummary();
            }

            function onUnitMarkChange(cb) {
                const key = cb.dataset.key, s = sectionState.unit;
                if (s.includeState[key] !== false) s.markState[key] = cb.checked;
                updateUnitConflictSummary();
            }

            function toggleAllUnitInclude(master) {
                const s = sectionState.unit;
                s.data.forEach(rec => {
                    const key = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
                    s.includeState[key] = master.checked;
                    s.markState[key]    = master.checked;
                });
                document.querySelectorAll('.unit-include').forEach(cb => cb.checked = master.checked);
                document.querySelectorAll('.unit-mark').forEach(cb => { cb.checked = master.checked; cb.disabled = !master.checked; });
                updateUnitConflictSummary();
            }

            function toggleAllUnitMark(master) {
                const s = sectionState.unit;
                s.data.forEach(rec => {
                    const key = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
                    if (s.includeState[key] !== false) s.markState[key] = master.checked;
                });
                document.querySelectorAll('.unit-mark').forEach(cb => {
                    if (s.includeState[cb.dataset.key] !== false) cb.checked = master.checked;
                });
                updateUnitConflictSummary();
            }

            function saveUnitConflictState() {
                document.querySelectorAll('.unit-include').forEach(cb => sectionState.unit.includeState[cb.dataset.key] = cb.checked);
                document.querySelectorAll('.unit-mark').forEach(cb => sectionState.unit.markState[cb.dataset.key] = cb.checked);
            }

            function restoreUnitConflictState() {
                document.querySelectorAll('.unit-include').forEach(cb => {
                    if (cb.dataset.key in sectionState.unit.includeState) cb.checked = sectionState.unit.includeState[cb.dataset.key];
                });
                document.querySelectorAll('.unit-mark').forEach(cb => {
                    if (cb.dataset.key in sectionState.unit.markState) cb.checked = sectionState.unit.markState[cb.dataset.key];
                });
            }

    function saveOutlierState() {
        const s = sectionState.out;
        document.querySelectorAll('.outlier-include').forEach(cb => {
            s.includeState[cb.dataset.key] = cb.checked;
        });
        document.querySelectorAll('.outlier-mark').forEach(cb => {
            s.markState[cb.dataset.key] = cb.checked;
        });
    }

    function restoreOutlierState() {
        const s = sectionState.out;
        document.querySelectorAll('.outlier-include').forEach(cb => {
            if (cb.dataset.key in s.includeState) {
                cb.checked = s.includeState[cb.dataset.key];
                setOutlierRowState(cb, cb.checked);
            }
        });
        document.querySelectorAll('.outlier-mark').forEach(cb => {
            if (cb.dataset.key in s.markState) {
                cb.checked = s.markState[cb.dataset.key];
            }
        });
    }

    function renderPagination(type, containerId) {
        const s = sectionState[type];
        const total = s.data.length;
        const totalPages = Math.ceil(total / ROWS_PER_PAGE);
        const container = document.getElementById(containerId);
        if (totalPages <= 1) {
            container.innerHTML = '';
            container.classList.add('hidden');   // ← tambah
            return;
        }
        container.classList.remove('hidden');    // ← tambah
        const cur = s.page;
        const start = cur * ROWS_PER_PAGE + 1;
        const end   = Math.min((cur + 1) * ROWS_PER_PAGE, total);
        container.innerHTML = `
            <div style="display:flex; align-items:center; justify-content:space-between;
                        padding: 8px 12px; font-size:12px; color:#6b7280;
                        border-top:1px solid #e5e7eb;">
                <span>${start}–${end} dari ${total}</span>
                <div style="display:flex; gap:4px; align-items:center;">
                    <button onclick="gotoPage('${type}', ${cur - 1})"
                        ${cur === 0 ? 'disabled' : ''}
                        style="padding:3px 10px; font-size:12px; border:1px solid #e5e7eb; border-radius:4px; background:#fff; cursor:pointer; ${cur === 0 ? 'opacity:0.4;cursor:not-allowed;' : ''}">
                        ‹
                    </button>
                    <span style="padding:3px 8px; font-size:12px;">${cur + 1} / ${totalPages}</span>
                    <button onclick="gotoPage('${type}', ${cur + 1})"
                        ${cur >= totalPages - 1 ? 'disabled' : ''}
                        style="padding:3px 10px; font-size:12px; border:1px solid #e5e7eb; border-radius:4px; background:#fff; cursor:pointer; ${cur >= totalPages - 1 ? 'opacity:0.4;cursor:not-allowed;' : ''}">
                        ›
                    </button>
                </div>
            </div>`;
    }

    function renderRows(type) {
        const s    = sectionState[type];
        const page = s.page;
        const rows = s.data.slice(page * ROWS_PER_PAGE, (page + 1) * ROWS_PER_PAGE);

        if (type === 'out') saveOutlierState();

        if (type === 'err') {
            document.getElementById('errTableBody').innerHTML = rows.map((e, i) => `
                <tr class="${i % 2 !== 0 ? 'bg-red-50' : ''}">
                    <td class="px-3 py-2 font-mono text-red-500">Baris ${esc(String(e.row))}</td>
                    <td class="px-3 py-2 text-red-700">${esc(e.message)}</td>
                </tr>`).join('');
            renderPagination('err', 'errPager');

        } else if (type === 'dup') {
            document.getElementById('dupTableBody').innerHTML = rows.map((r, i) => `
                <tr class="${i % 2 !== 0 ? 'bg-amber-50' : ''}">
                    <td class="px-3 py-2 text-gray-700">${esc(r.nama_metadata ?? String(r.metadata_id))}</td>
                    <td class="px-3 py-2 text-gray-500">${esc(r.nama_wilayah ?? String(r.location_id))}</td>
                    <td class="px-3 py-2 text-gray-500 font-mono">${esc(String(r.period_label))}</td>
                    <td class="px-3 py-2 text-right font-mono text-gray-700">${formatNum(r.number_value)}</td>
                </tr>`).join('');
            renderPagination('dup', 'dupPager');

        } else if (type === 'out') {
            document.getElementById('outTableBody').innerHTML = rows.map((rec, i) => {
                const info = rec.outlier_info;
 
                // ── Nilai yang ditampilkan — sekarang dari algoritma DB ──────────────
                // info.z_score    = standard z-score (identik dengan percentage_change di anomali DB)
                // info.mean       = mean historis DB (identik dengan previous_value di anomali DB)
                // info.pct_from_mean = % deviasi dari mean
                // info.lower/upper   = batas ±3σ
                // info.n             = jumlah data historis
 
                const zScore = info ? Math.abs(parseFloat(info.z_score ?? 0)).toFixed(2) : '-';
                const mean   = info ? formatNum(info.mean) : '-';
 
                // Untuk intra_series: lower/upper dihitung dari median ± 3*stddev
                // Untuk source DB: pakai langsung dari info
                const hasValidBounds = info && info.lower != null && info.source === 'db';
                const batasTooltip = hasValidBounds
                    ? `Batas ±3σ: ${formatNum(info.lower)} – ${formatNum(info.upper)} (n=${info.n ?? '?'})`
                    : '';
 
                // % dari mean
                const pct = info
                    ? (info.pct_from_mean !== null && info.pct_from_mean !== undefined
                        ? (info.pct_from_mean >= 0 ? '+' : '')
                          + parseFloat(info.pct_from_mean).toFixed(1) + '%'
                        : '—')
                    : '-';
 
                const level   = getLevelBadge(zScore);
                const dir     = info?.direction ?? 'high';
                const key     = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
                const rowBg   = i % 2 === 0 ? '#ffffff' : '#fff7ed';
                const severity = info ? getSeverityFromZ(Math.abs(parseFloat(info.z_score ?? 0))) : 'medium';
 
                // Warna badge z-score
                const zStyle = {
                    low:      'background:#fef9c3;color:#a16207;',
                    medium:   'background:#ffedd5;color:#c2410c;',
                    high:     'background:#fee2e2;color:#b91c1c;',
                    critical: 'background:#fecaca;color:#991b1b;',
                }[severity];
 
                // Tampilan khusus jika stddev = 0 (no_stddev flag)
                const isNoStddev = info?.no_stddev === true;
                const zDisplay   = isNoStddev
                    ? `<span title="Semua data historis identik, z tidak dapat dihitung"
                            style="background:#f3f4f6;color:#6b7280;"
                            class="inline-block px-2 py-0.5 rounded-full text-xs">—*</span>`
                    : `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                            style="${zStyle}">${esc(zScore)}</span>`;
 
                return `
                <tr style="background:${rowBg};" class="outlier-row">
                    <td class="px-3 py-2.5">
                        <p class="font-medium text-gray-800">${esc(rec.nama_metadata ?? String(rec.metadata_id))}</p>
                    </td>
                    <td class="px-3 py-2.5 text-gray-500">${esc(rec.nama_wilayah ?? String(rec.location_id))}</td>
                    <td class="px-3 py-2.5 text-center font-mono text-gray-700">${esc(rec.period_label)}</td>
                    <td class="px-3 py-2.5 text-right font-mono font-semibold text-gray-900">
                        ${formatNum(rec.number_value)}
                    </td>
                    <td class="px-3 py-2.5 text-right font-mono text-gray-500" title="${esc(batasTooltip)}">
                        <span>${mean}</span>
                    </td>
                    <td class="px-3 py-2.5 text-right font-mono font-semibold
                               ${dir === 'high' ? 'text-red-600' : 'text-blue-600'}">${pct}</td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
                              style="${level.style}">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="${level.dot}"></span>
                            ${level.label}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-center">${zDisplay}</td>
                    <td class="px-3 py-2.5 text-center">
                        <input type="checkbox" class="outlier-include rounded border-orange-300
                                text-orange-500 focus:ring-orange-400 cursor-pointer"
                            data-key="${esc(key)}" checked onchange="syncOutlierRow(this)">
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <input type="checkbox" class="outlier-mark rounded border-orange-300
                                text-orange-500 focus:ring-orange-400 cursor-pointer"
                            data-key="${esc(key)}" checked onchange="onMarkChange(this)">
                    </td>
                </tr>`;
            }).join('');
 
            restoreOutlierState();
            renderPagination('out', 'outPager');
            updateOutlierSummary();
        } else if (type === 'satuan') {
            document.getElementById('satuanTableBody').innerHTML = rows.map((r, i) => `
                <tr class="${i % 2 !== 0 ? 'bg-indigo-50/40' : ''}">
                    <td class="px-3 py-2 text-gray-700">${esc(r.nama_metadata)}</td>
                    <td class="px-3 py-2 text-gray-500">${esc(r.satuan_metadata_raw ?? '—')}</td>
                    <td class="px-3 py-2 text-gray-500">${esc(r.satuan_rujukan_raw ?? '—')}</td>
                    <td class="px-3 py-2 text-red-500 font-medium">${esc(r.satuan_tidak_ketemu)}</td>
                    <td class="px-3 py-2 font-mono text-gray-400">Baris ${esc(String(r.row))}</td>
                </tr>`).join('');
            renderPagination('satuan', 'satuanPager');

        } else if (type === 'unit') {
            if (type === 'unit') saveUnitConflictState();
            document.getElementById('unitTableBody').innerHTML = rows.map((rec, i) => {
                const info = rec.unit_conflict_info;
                const key  = `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`;
                return `
                <tr style="background:${i % 2 === 0 ? '#ffffff' : '#fdf2f8'};">
                    <td class="px-3 py-2.5 font-medium text-gray-800">${esc(rec.nama_metadata)}</td>
                    <td class="px-3 py-2.5 text-gray-500">${esc(rec.nama_wilayah)}</td>
                    <td class="px-3 py-2.5 text-center font-mono text-gray-700">${esc(rec.period_label)}</td>
                    <td class="px-3 py-2.5 text-right font-mono font-semibold text-gray-900">${formatNum(rec.number_value)}</td>
                    <td class="px-3 py-2.5"><span class="px-2 py-0.5 rounded-full text-xs" style="background:#e0e7ff;color:#3730a3;">${esc(info?.satuan_metadata_nama)}</span></td>
                    <td class="px-3 py-2.5"><span class="px-2 py-0.5 rounded-full text-xs" style="background:#fce7f3;color:#9d174d;">${esc(info?.satuan_rujukan_nama)}</span></td>
                    <td class="px-3 py-2.5 text-center">
                        <input type="checkbox" class="unit-include rounded border-pink-300 text-pink-500 focus:ring-pink-400 cursor-pointer"
                            data-key="${esc(key)}" checked onchange="syncUnitConflictRow(this)">
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <input type="checkbox" class="unit-mark rounded border-pink-300 text-pink-500 focus:ring-pink-400 cursor-pointer"
                            data-key="${esc(key)}" checked onchange="onUnitMarkChange(this)">
                    </td>
                </tr>`;
            }).join('');
            restoreUnitConflictState();
            renderPagination('unit', 'unitPager');
            updateUnitConflictSummary();
        } else if (type === 'meta') {
            document.getElementById('metaTableBody').innerHTML = rows.map((m, i) => {
                let reasonBadge, extraNote = '';
                if (m.reason === 'not_found') {
                    reasonBadge = `<span style="background:#fce7f3; color:#9d174d;" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"><i class="fas fa-times-circle text-xs"></i> Belum terdaftar</span>`;
                } else if (m.reason === 'name_mismatch') {
                    reasonBadge = `<span style="background:#fee2e2; color:#b91c1c;" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"><i class="fas fa-triangle-exclamation text-xs"></i> ID sudah dipakai metadata lain</span>`;
                    extraNote = `<p class="text-[11px] text-red-500 mt-1">metadata_id ${esc(String(m.metadata_id))} telah terdaftar dengan nama <strong>"${esc(m.nama_metadata_db ?? '-')}"</strong>. Pastikan metadata_id dan nama <strong>"${esc(m.nama_metadata)}"</strong> yang Anda input sudah benar &amp; berstatus aktif.</p>`;
                } else {
                    reasonBadge = `<span style="background:#fef3c7; color:#92400e;" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"><i class="fas fa-clock text-xs"></i> Status ${esc(m.status_label ?? 'tidak aktif')}</span>`;
                }

                const namaCell = m.reason === 'name_mismatch'
                    ? `<p class="text-red-600 font-medium">Excel: ${esc(m.nama_metadata ?? '-')}</p>
                    <p class="text-gray-400 text-[11px]">Sistem: ${esc(m.nama_metadata_db ?? '-')}</p>
                    ${extraNote}`
                    : esc(m.nama_metadata ?? '-');

                return `
                <tr style="background:${i % 2 !== 0 ? '#fdf4ff' : '#fff'};">
                    <td class="px-3 py-2.5 font-mono font-semibold" style="color:#7c3aed;">#${esc(String(m.metadata_id))}</td>
                    <td class="px-3 py-2.5 text-gray-700 font-medium">${namaCell}</td>
                    <td class="px-3 py-2.5">${reasonBadge}</td>
                    <td class="px-3 py-2.5 font-mono text-gray-400">Baris ${esc(String(m.row))}</td>
                </tr>`;
            }).join('');
            renderPagination('meta', 'metaPager');
        }
    }

    function gotoPage(type, page) {
        const s = sectionState[type];
        const totalPages = Math.ceil(s.data.length / ROWS_PER_PAGE);
        s.page = Math.max(0, Math.min(page, totalPages - 1));
        renderRows(type);
    }

    async function doImport() {
        if (!currentFile || !previewData) return;

        const skipDup      = document.getElementById('cbSkipDup')?.checked ?? true;
        const btn          = document.getElementById('btnImport');
        const invalidCount = (previewData.invalid_metadata || []).length;
        const outlierCount = (previewData.outliers || []).length;

        
        const excludedKeys = sectionState.out.data
            .map(rec => `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`)
            .filter(key => sectionState.out.includeState[key] === false);

        const anomalyKeys = sectionState.out.data
            .map(rec => `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`)
            .filter(key => sectionState.out.markState[key] === true 
                        && sectionState.out.includeState[key] !== false);

        const unitConflictKeys = sectionState.unit.data
            .map(rec => `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`)
            .filter(key => sectionState.unit.markState[key] === true && sectionState.unit.includeState[key] !== false);

        const unitExcludedKeys = sectionState.unit.data
            .map(rec => `${rec.metadata_id}_${rec.location_id}_${rec.time_id}_${rec.rujukan_id}`)
            .filter(key => sectionState.unit.includeState[key] === false);

        const satuanResolutions = sectionState.satuan?.resolutions || {};

        // Build confirm message
        let confirmMsg = `Import ${previewData.valid} record data?`;
        if (skipDup && previewData.duplicate > 0) {
            confirmMsg += `\n• ${previewData.duplicate} duplikat akan dilewati.`;
        }
        if (invalidCount > 0) {
            confirmMsg += `\n• ${invalidCount} metadata tidak valid — datanya tidak akan diimport.`;
        }
        if (outlierCount > 0) {
            const included = outlierCount - excludedKeys.length;
            const excluded = excludedKeys.length;
            confirmMsg += `\n• ${included} dari ${outlierCount} data outlier akan diimport.`;
            if (excluded > 0) {
                confirmMsg += `\n• ${excluded} data outlier dikecualikan sesuai pilihan Anda.`;
            }
            if (anomalyKeys.length > 0) {
                confirmMsg += `\n• ${anomalyKeys.length} outlier akan dicatat sebagai anomali.`;
            }
        }

        if (!confirm(confirmMsg)) return;

        btn.disabled = true;
        document.getElementById('importingBar').classList.remove('hidden');
        document.getElementById('previewSection').classList.add('hidden');

        const form = new FormData();
        form.append('_token',          CSRF);
        form.append('file_excel',      currentFile);
        form.append('skip_duplicates', skipDup ? '1' : '0');

        // Kirim excluded_keys ke server
        excludedKeys.forEach(key => form.append('excluded_keys[]', key));
        unitExcludedKeys.forEach(key => form.append('excluded_keys[]', key));
        unitConflictKeys.forEach(key => form.append('unit_conflict_keys[]', key));
        anomalyKeys.forEach(key => form.append('anomaly_keys[]', key));
        Object.entries(satuanResolutions).forEach(([key, val]) => {
            if (val) form.append(`satuan_resolutions[${key}]`, val);
        });

        try {
            const resp = await fetch(IMPORT_URL, {
                method:  'POST',
                headers: {
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });

            const json = await resp.json();
            document.getElementById('importingBar').classList.add('hidden');

            if (json.success) {
                showImportAlert(
                    'success',
                    json.message,
                    json.redirect
                        ? `<a href="${json.redirect}" class="underline font-semibold ml-2">
                            ${json.anomaly_count > 0 ? 'Ke Halaman Control →' : 'Ke Halaman Data →'}
                        </a>`
                        : ''
                );
                resetUpload();
            } else {
                // Build extra details (row-level errors) if provided by server
                let extraHtml = '';
                if (json.db_error) {
                    extraHtml += `<div class="mt-2 text-xs text-gray-700">DB: ${esc(json.db_error)}</div>`;
                }
                if (json.error_rows && Array.isArray(json.error_rows) && json.error_rows.length > 0) {
                    extraHtml += '<div class="mt-2 text-xs text-gray-700 font-semibold">Detail Baris Error:</div>';
                    extraHtml += '<ul class="mt-1 text-xs list-disc pl-5 text-gray-700">';
                    json.error_rows.forEach(e => {
                        const rowText = e.row ? `Baris ${esc(e.row)}:` : '';
                        extraHtml += `<li>${rowText} ${esc(e.message)}</li>`;
                    });
                    extraHtml += '</ul>';
                }
                if (json.invalid_metadata_rows && Array.isArray(json.invalid_metadata_rows) && json.invalid_metadata_rows.length > 0) {
                    extraHtml += '<div class="mt-2 text-xs text-gray-700 font-semibold">Metadata Bermasalah:</div>';
                    extraHtml += '<ul class="mt-1 text-xs list-disc pl-5 text-gray-700">';
                    json.invalid_metadata_rows.forEach(m => {
                        extraHtml += `<li>Metadata ID ${esc(m.metadata_id)}: ${esc(m.nama_metadata ?? '-')}</li>`;
                    });
                    extraHtml += '</ul>';
                }

                showImportAlert('error', json.message || 'Import gagal.', extraHtml);
                if (previewData) renderPreview(previewData);
                btn.disabled = false;
            }
        } catch (err) {
            document.getElementById('importingBar').classList.add('hidden');
            showImportAlert('error', 'Terjadi kesalahan jaringan: ' + err.message);
            if (previewData) renderPreview(previewData);
            btn.disabled = false;
        }
    }

    function showImportAlert(type, msg, extra = '') {
        const isErr = type === 'error';
        const el    = document.getElementById('importResult');
        el.innerHTML = `
            <div class="flex items-start gap-3 px-4 py-3 rounded-lg text-sm"
                style="background:${isErr ? '#fef2f2' : '#f0fdf4'};
                        border:1px solid ${isErr ? '#fecaca' : '#bbf7d0'};
                        color:${isErr ? '#b91c1c' : '#166534'};">
                <i class="fas ${isErr ? 'fa-exclamation-circle text-red-400' : 'fa-check-circle text-green-500'} mt-0.5 shrink-0"></i>
                <span>${esc(msg)}${extra}</span>
            </div>`;
        el.classList.remove('hidden');
    }

    function esc(str) {
        if (str == null) return '-';
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function formatNum(val) {
        if (val == null || val === '') return '-';
        const n = parseFloat(val);
        if (isNaN(n)) return esc(String(val));
        // Gunakan format serupa backend (koma untuk ribuan, titik untuk desimal)
        // agar preview dan control index tampil konsisten.
        return n % 1 === 0
            ? n.toLocaleString('en-US')
            : n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ══════════════════════════════════════════════════════════════
    // INIT
    // ══════════════════════════════════════════════════════════════
    @if($errors->any() || session('duplicate_warning'))
        switchTab('manual');
    @endif

    // Inisialisasi jika ada old() value
    (function() {
        const metaSel = document.getElementById('metadataSelect');
        if (metaSel.value) onMetadataChange(metaSel);

        const provSel = document.getElementById('selProvinsi');
        if (provSel.value) {
            onProvinsiChange(provSel);
            // Restore old kabupaten jika ada
            @if(old('kabupaten_id'))
            setTimeout(() => {
                const kabSel = document.getElementById('selKabupaten');
                kabSel.value = '{{ old("kabupaten_id") }}';
                if (kabSel.value) onKabupatenChange(kabSel);
            }, 100);
            @endif
        }
    })();
    </script>
    @endsection