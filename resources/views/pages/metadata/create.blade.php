@extends('layouts.main')

@section('content')

<div class="py-4">

    <a href="{{ route('metadata.index') }}"
       class="flex items-center text-sm font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900">
        <i class="fas fa-angle-left mr-1"></i> Kembali
    </a>

    <div class="mt-2 bg-white rounded-md shadow p-6">

        {{-- HEADER --}}
        <div class="flex justify-between mb-6 items-start">
            <div>
                <h1 class="text-lg font-bold text-gray-800">Tambah Metadata</h1>
                <p class="text-sm text-gray-400 mt-0.5">
                    Data akan berstatus <strong>Pending</strong> hingga disetujui admin
                </p>
            </div>
            <div class="text-right text-sm text-gray-500">
                <p id="current-date"></p>
                <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             TAB SWITCHER
        ══════════════════════════════════════════════════ --}}
        <div class="flex border-b border-gray-200 mb-6 gap-1">
            <button type="button" id="tabManualBtn"
                onclick="switchTab('manual')"
                class="tab-btn px-5 py-2.5 text-sm font-semibold rounded-t-md border border-b-0
                       flex items-center gap-2 transition-colors">
                <i class="fas fa-edit"></i> Input Manual
            </button>
            <button type="button" id="tabExcelBtn"
                onclick="switchTab('excel')"
                class="tab-btn px-5 py-2.5 text-sm font-semibold rounded-t-md border border-b-0
                       flex items-center gap-2 transition-colors">
                <i class="fas fa-file-excel"></i> Import Excel
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════
             TAB 1: INPUT MANUAL
        ══════════════════════════════════════════════════ --}}
        <div id="tabManual">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm">
                    <p class="font-semibold mb-1 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i> Terdapat kesalahan input:
                    </p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('metadata.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-2 gap-6">

                    {{-- NAMA --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Nama <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nama" id="nama" value="{{ old('nama') }}"
                            class="w-full border @error('nama') border-red-400 @else border-gray-300 @enderror
                                   rounded-sm focus:outline-none focus:ring-2 focus:ring-sky-400 px-2 py-2 text-xs"
                            autocomplete="off">
                        <div id="namaStatus" class="mt-1 text-xs hidden"></div>
                        @error('nama')
                            <p class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- ALIAS --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">Alias</label>
                        <input type="text" name="alias" value="{{ old('alias') }}"
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 px-2 py-2 text-xs">
                    </div>

                    {{-- KONSEP --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Konsep <span class="text-red-500">*</span>
                        </label>
                        <textarea name="konsep" rows="4"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">{{ old('konsep') }}</textarea>
                    </div>

                    {{-- DEFINISI --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Definisi <span class="text-red-500">*</span>
                        </label>
                        <textarea name="definisi" rows="4"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">{{ old('definisi') }}</textarea>
                    </div>

                    {{-- KLASIFIKASI --}}
                    <div class="col-span-2">
                        <label class="block font-medium text-sm mb-1">
                            Klasifikasi <span class="text-red-500">*</span>
                        </label>
                        <select name="klasifikasi_id" placeholder="Pilih klasifikasi..." autocomplete="off"
                            class="tom-select w-full border @error('klasifikasi_id') border-red-400 @else border-gray-300 @enderror
                                rounded-sm focus:outline-none focus:ring-2 focus:ring-sky-400 text-xs">

                            <option value="">-- Pilih Klasifikasi --</option>

                            @foreach($klasifikasiList as $k)
                                <option value="{{ $k->klasifikasi_id }}"
                                    {{ old('klasifikasi_id') == $k->klasifikasi_id ? 'selected' : '' }}>
                                    {{ $k->nama_klasifikasi }}
                                </option>
                            @endforeach
                        </select>
                        @error('klasifikasi')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ASUMSI --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">Asumsi</label>
                        <textarea name="asumsi" rows="4"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">{{ old('asumsi') }}</textarea>
                    </div>

                    {{-- METODOLOGI --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Metodologi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="metodologi" value="{{ old('metodologi') }}"
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 px-2 py-2 text-xs">
                    </div>

                    {{-- PENJELASAN METODOLOGI --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Penjelasan Metodologi <span class="text-red-500">*</span>
                        </label>
                        <textarea name="penjelasan_metodologi" rows="3"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">{{ old('penjelasan_metodologi') }}</textarea>
                    </div>

                    {{-- TIPE DATA --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Tipe Data <span class="text-red-500">*</span>
                        </label>
                        <select name="tipe_data"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">
                            <option value="Angka Numerik" {{ old('tipe_data','Angka Numerik') == 'Angka Numerik' ? 'selected' : '' }}>Numerik</option>
                            <option value="Teks"    {{ old('tipe_data') == 'Teks' ? 'selected' : '' }}>Teks</option>
                        </select>
                    </div>

                    {{-- SATUAN DATA --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Satuan Data <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="satuan_data" value="{{ old('satuan_data') }}"
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 px-2 py-2 text-xs">
                    </div>

                    {{-- TAHUN MULAI DATA --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Tahun Mulai Data <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="tahun_mulai_data" value="{{ old('tahun_mulai_data') }}"
                            class="w-full border @error('tahun_mulai_data') border-red-400 @else border-gray-300 @enderror
                                rounded-sm focus:outline-none focus:ring-2 focus:ring-sky-400 px-2 py-2 text-xs"
                            placeholder="cth: 2020">
                        @error('tahun_mulai_data')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- TAHUN METADATA --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Tahun Metadata
                        </label>
                        <input type="number" name="tahun_metadata" value="{{ old('tahun_metadata', 2026) }}"
                            min="1900" max="2100"
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2 focus:ring-sky-400 px-2 py-2 text-xs"
                            placeholder="cth: 2026">
                        @error('tahun_metadata')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- FREKUENSI PENERBITAN --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Frekuensi Penerbitan <span class="text-red-500">*</span>
                        </label>
                        <select name="frekuensi_penerbitan" id="frekuensi_penerbitan"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">
                            <option value="">-- Pilih Frekuensi --</option>
                            @foreach(['Dekade','Tahunan','Semester','Kuartal','Bulanan','Statis'] as $frek)
                                <option value="{{ $frek }}" {{ old('frekuensi_penerbitan') == $frek ? 'selected' : '' }}>
                                    {{ $frek }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- TAHUN PERTAMA RILIS --}}
                    <div id="tahun_pertama_rilis_wrap">
                        <label class="block font-medium text-sm mb-1">Tahun Pertama Rilis</label>
                        <input type="number"
                            name="tahun_pertama_rilis"
                            id="tahun_pertama_rilis"
                            value="{{ old('tahun_pertama_rilis') }}"
                            min="1900" max="2100"
                            disabled
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                    focus:ring-sky-400 px-2 py-2 text-xs
                                    disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                        <p class="mt-1 text-xs text-gray-400">
                            Hanya aktif jika frekuensi penerbitan adalah <strong>Dekade</strong>.
                        </p>
                        @error('tahun_pertama_rilis')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- BULAN PERTAMA RILIS --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">Bulan Pertama Rilis</label>
                        <select name="bulan_pertama_rilis" id="bulan_pertama_rilis"
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 px-2 py-2 text-xs
                                   disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            disabled>
                            <option value="">-- Pilih Bulan --</option>
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bln)
                                <option value="{{ $i + 1 }}" {{ old('bulan_pertama_rilis') == ($i + 1) ? 'selected' : '' }}>
                                    {{ $bln }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- TANGGAL RILIS --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">Tanggal Rilis</label>
                        <select name="tanggal_rilis" id="tanggal_rilis"
                            class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 px-2 py-2 text-xs
                                   disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            disabled>
                            <option value="">-- Pilih Tanggal --</option>
                        </select>
                    </div>

                    {{-- TIPE GROUP --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Tipe Group
                            <span class="text-gray-400 font-normal text-xs ml-1">
                                (metadata ini bagian dari group?)
                            </span>
                        </label>
                        <select name="tipe_group" id="tipe_group"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">
                            <option value="0" {{ old('tipe_group','0') == '0' ? 'selected' : '' }}>Tidak</option>
                            <option value="1" {{ old('tipe_group') == '1' ? 'selected' : '' }}>Ya</option>
                        </select>
                    </div>

                    {{-- GROUP BY --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Group By
                            <span class="text-gray-400 font-normal text-xs ml-1">(pilih metadata induk)</span>
                        </label>
                        <select name="group_by" id="group_by" placeholder="Pilih group..." autocomplete="off"
                            class="tom-select w-full border @error('group_by') border-red-400 @else border-gray-300 @enderror
                                   rounded-sm focus:outline-none focus:ring-2 focus:ring-sky-400 text-xs
                                   disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                            disabled>
                        </select>
                        @error('group_by')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">
                            Hanya menampilkan metadata berstatus <strong>Active</strong>
                        </p>
                    </div>

                    {{-- FLAG DESIMAL --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">Flag Desimal</label>
                        <select name="flag_desimal"
                                class="w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs">
                            <option value="1" {{ old('flag_desimal') == '1' ? 'selected' : '' }}>Ya</option>
                            <option value="0" {{ old('flag_desimal','0') == '0' ? 'selected' : '' }}>Tidak</option>
                        </select>
                    </div>

                    {{-- PRODUSEN DATA --}}
                    <div>
                        <label class="block font-medium text-sm mb-1">
                            Sumber Metadata Pertama <span class="text-red-500">*</span>
                        </label>
                        <select name="produsen_id"  placeholder="Pilih produsen..." autocomplete="off"
                                class="tom-select w-full border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 text-xs">
                            <option value="">-- Pilih Sumber Metadata Pertama --</option>
                            @foreach($produsen as $item)
                                <option value="{{ $item->produsen_id }}"
                                    {{ old('produsen_id') == $item->produsen_id ? 'selected' : '' }}>
                                    {{ $item->nama_produsen }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- TAG --}}
                    <div class="col-span-2">
                        <label class="block font-medium text-sm mb-1">
                            Tag <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="text" id="tag-input"
                                class="flex-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 px-2 py-2 text-xs"
                                placeholder="Ketik tag lalu tekan Enter atau klik Add">
                            <button type="button" id="add-tag"
                                class="bg-teal-500 text-white px-4 py-2 text-xs rounded-md hover:bg-teal-600
                                       transition-colors">
                                Add
                            </button>
                        </div>
                        <div id="tag-list" class="flex flex-wrap gap-2 mt-3"></div>
                        <button type="button" id="remove-all-tags"
                            class="hidden mt-2 text-xs text-blue-500 hover:underline">
                            Remove all tags
                        </button>
                        <input type="hidden" name="tag" id="tag-hidden" value="{{ old('tag') }}">
                        @error('tag')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SUBMIT --}}
                    <div class="col-span-2 flex justify-end pt-2 border-t">
                        <button type="submit" id="submitBtn"
                            class="bg-sky-600 text-white font-medium px-6 py-2 rounded-md hover:bg-sky-700
                                   transition-colors flex items-center gap-2
                                   disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <i class="fas fa-save"></i> Simpan Metadata
                        </button>
                    </div>

                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════
             TAB 2: IMPORT EXCEL
        ══════════════════════════════════════════════════ --}}
        <div id="tabExcel" class="hidden">

            {{-- PANDUAN --}}
            <div class="mb-5 p-4 bg-sky-50 border border-sky-200 rounded-lg text-sm">
                <p class="font-semibold mb-1 flex items-center text-sky-500 gap-2">
                    <i class="fas fa-info-circle"></i> Panduan Import Excel
                </p>
                <ul class="list-disc list-inside space-y-1 text-xsmt-1 text-gray-700">
                    <li>Format file yang didukung: <strong>.xlsx</strong> atau <strong>.xls</strong></li>
                    <li>Baris pertama diasumsikan sebagai header dan akan diabaikan</li>
                    <li>Nama metadata yang mengandung nama wilayah akan dinormalisasi otomatis</li>
                    <li>Metadata dengan nama yang sama (dalam file atau database) hanya diinsert sekali</li>
                </ul>
            </div>
            <div class="mb-5 p-4 bg-sky-50 border border-sky-200 rounded-lg ">
                <p class="text-sm text-gray-700">
                    Berikut adalah template excel yang dapat Anda gunakan untuk mengimport metadata:
                </p>

                <a href="https://docs.google.com/uc?export=download&id=1tTtCDmfc5wNd4NCy0WG_vidcHv-QRbjS"
                class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-sky-500
                        hover:text-sky-800 underline">
                    <i class="fas fa-download text-xs"></i> Download Template Excel
                </a>
            </div>

            {{-- UPLOAD AREA --}}
            <div class="py-5">
                <div>
                    <label class="block font-medium text-sm mb-2">
                        Upload File Excel <span class="text-red-500">*</span>
                    </label>

                    {{-- Drop zone --}}
                    <div id="dropZone"
                         class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center
                                cursor-pointer hover:border-sky-400 hover:bg-sky-50 transition-colors"
                         onclick="document.getElementById('excelFile').click()"
                         ondragover="event.preventDefault(); this.classList.add('border-sky-400','bg-sky-50')"
                         ondragleave="this.classList.remove('border-sky-400','bg-sky-50')"
                         ondrop="handleDrop(event)">
                        <i class="fas fa-file-excel text-4xl text-gray-300 mb-3"></i>
                        <p class="text-sm font-medium text-gray-600">
                            Klik atau seret file Excel ke sini
                        </p>
                        <p class="text-xs text-gray-400 mt-1">.xlsx atau .xls, maksimal 20 MB</p>
                        <input type="file" id="excelFile" accept=".xlsx,.xls" class="hidden"
                               onchange="onFileSelected(this)">
                    </div>

                    {{-- File info --}}
                    <div id="fileInfo" class="hidden mt-3 flex items-center gap-3 p-3 bg-green-50
                                              border border-green-200 rounded-lg text-sm text-green-700">
                        <i class="fas fa-file-excel text-green-500 text-lg shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p id="fileName" class="font-semibold truncate"></p>
                            <p id="fileSize" class="text-xs text-green-500"></p>
                        </div>
                        <button type="button" onclick="clearFile()"
                                class="text-green-400 hover:text-red-500 transition-colors shrink-0">
                            <i class="fas fa-times-circle text-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- OPSI IMPORT --}}
                <div class="flex flex-wrap gap-4 py-5 items-center">
                    <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                        <input type="checkbox" id="skipExisting" checked
                               class="rounded border-gray-300 text-sky-600 focus:ring-sky-400">
                        <span>Lewati metadata yang sudah ada di database</span>
                    </label>
                </div>

                {{-- TOMBOL PREVIEW --}}
                <div class="flex gap-3">
                    <button type="button" id="btnPreview" onclick="doPreview()"
                        class="bg-sky-600 hover:bg-sky-700 text-white px-5 py-2.5 rounded-md text-sm
                               font-semibold flex items-center gap-2 transition-colors
                               disabled:bg-gray-300 disabled:cursor-not-allowed"
                        disabled>
                        <i class="fas fa-eye"></i> Preview Metadata
                    </button>
                    <button type="button" id="btnImport" onclick="doImport()"
                        class="hidden bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-md
                               text-sm font-semibold flex items-center gap-2 transition-colors">
                        <i class="fas fa-database"></i> Import ke Database
                        <span id="importCount" class="bg-white text-emerald-600 text-xs font-bold
                                                      px-1.5 py-0.5 rounded-full">0</span>
                    </button>
                </div>
            </div>

            {{-- ── LOADING ── --}}
            <div id="previewLoading" class="hidden mt-6 flex flex-col items-center gap-3 py-10">
                <div class="w-10 h-10 border-4 border-sky-200 border-t-sky-600 rounded-full animate-spin"></div>
                <p class="text-sm text-gray-500 font-medium">Membaca dan memproses file Excel...</p>
            </div>

            {{-- ── HASIL PREVIEW ── --}}
            <div id="previewResult" class="hidden mt-6 space-y-4">

                {{-- Stats bar --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="statsBar"></div>

                {{-- Tabel preview --}}
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <i class="fas fa-table text-sky-500"></i> Data Preview
                        </h3>
                        <div class="flex gap-2 items-center">
                            <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer">
                                <input type="checkbox" id="hideExisting" onchange="applyTableFilter()"
                                       class="rounded border-gray-300 text-sky-600 text-xs">
                                Sembunyikan yang sudah ada di DB
                            </label>
                        </div>
                    </div>
                    <div class="border rounded-lg overflow-hidden">
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider
                                              text-xs border-b sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2.5 font-semibold w-12">Row</th>
                                        <th class="px-3 py-2.5 font-semibold min-w-48">Nama (Setelah Normalisasi)</th>
                                        <th class="px-3 py-2.5 font-semibold min-w-36 text-gray-400">Alias</th>
                                        <th class="px-3 py-2.5 font-semibold">Klasifikasi</th>
                                        <th class="px-3 py-2.5 font-semibold">Tipe Data</th>
                                        <th class="px-3 py-2.5 font-semibold">Satuan</th>
                                        <th class="px-3 py-2.5 font-semibold">Tahun</th>
                                        <th class="px-3 py-2.5 font-semibold">Produsen</th>
                                        <th class="px-3 py-2.5 font-semibold text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="previewBody" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Baris yang dilewati --}}
                <div id="skippedSection" class="hidden">
                    <button type="button" onclick="toggleSkipped()"
                        class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 mb-2">
                        <i class="fas fa-chevron-down" id="skippedChevron"></i>
                        <span id="skippedLabel">Lihat baris yang dilewati</span>
                    </button>
                    <div id="skippedTable" class="hidden border rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-green-50 border-b text-green-700 text-left sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 font-semibold w-12">Row</th>
                                    <th class="px-3 py-2 font-semibold">Nama</th>
                                    <th class="px-3 py-2 font-semibold">Alasan</th>
                                </tr>
                            </thead>
                            <tbody id="skippedBody" class="divide-y divide-gray-100 bg-green-50/30"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- ── RESULT IMPORT ── --}}
            <div id="importResult" class="hidden mt-4"></div>
            <div id="importErrors" class="hidden mt-4"></div>

        </div>

    </div>
</div>

<style>
.ts-wrapper {
    width: 100%;
    font-size: 0.75rem; /* text-xs */
}

.ts-control {
    border: 1px solid #bdbfc3; /* gray-300 */
    border-radius: 0.125rem; /* rounded-sm */
    padding: 0.5rem; /* py-2 px-2 */
}

.ts-wrapper.disabled .ts-control {
    background-color: #eaedf0; /* bg-gray-100 */
    color: #15419a;           /* text-gray-400 */
    cursor: not-allowed;
}

.ts-control:focus-within {
    outline: none;
    box-shadow: 0 0 0 2px #38bdf8; /* sky-400 */
    border-color: #38bdf8;
}

/* Tab active/inactive styles */
.tab-btn-active {
    background: #fff;
    color: #0284c7;
    border-color: #e5e7eb;
    border-bottom-color: #fff;
    margin-bottom: -1px;
}
.tab-btn-inactive {
    background: #f9fafb;
    color: #6b7280;
    border-color: transparent;
}
.tab-btn-inactive:hover {
    color: #374151;
    background: #f3f4f6;
}
</style>


<script>
/* ============================================================
   CONSTANTS
   ============================================================ */
const PREVIEW_URL = '{{ route("metadata.import.preview") }}';
const IMPORT_URL  = '{{ route("metadata.import.store") }}';
const CSRF        = '{{ csrf_token() }}';

/* ============================================================
   GLOBAL STATE
   ============================================================ */
let currentFile = null;
let previewData = [];
let skippedData = [];
let skippedOpen = false;

function initTomSelect(selector) {
    document.querySelectorAll(selector).forEach(el => {
        if (el.tomselect) return;

        if (el.id === 'group_by') {
            new TomSelect(el, {
                valueField: 'metadata_id',
                labelField: 'nama',
                searchField: 'nama',
                create: false,
                load: function (query, callback) {
                    fetch(`{{ route('metadata.search_for_group') }}?q=${encodeURIComponent(query)}`)
                        .then(r => r.json())
                        .then(data => callback(data))
                        .catch(() => callback());
                },
                render: {
                    option: function (item, escape) {
                        return `<div>${escape(item.nama)}${item.klasifikasi ? ' — ' + escape(item.klasifikasi) : ''}</div>`;
                    },
                    item: function (item, escape) {
                        return `<div>${escape(item.nama)}</div>`;
                    }
                }
            });
        } else {
            new TomSelect(el, { create: true, sortField: { field: "text", direction: "asc" } });
        }
    });
}


/* ============================================================
   UTILITY
   ============================================================ */
function escHtml(str) {
    if (str === null || str === undefined) return '-';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

function formatFileSize(bytes) {
    if (bytes < 1024)        return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

/* Safe DOM helpers to avoid exceptions when an element is missing */
function safeSetText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function safeGet(id) {
    return document.getElementById(id);
}

/* ============================================================
   TAB SWITCHER
   ============================================================ */
function switchTab(tab) {
    const isManual = tab === 'manual';

    document.getElementById('tabManual').classList.toggle('hidden', !isManual);
    document.getElementById('tabExcel').classList.toggle('hidden',  isManual);

    const base = 'tab-btn px-5 py-2.5 text-sm font-semibold rounded-t-md border border-b-0 flex items-center gap-2 transition-colors ';
    document.getElementById('tabManualBtn').className = base + (isManual  ? 'tab-btn-active' : 'tab-btn-inactive');
    document.getElementById('tabExcelBtn').className  = base + (!isManual ? 'tab-btn-active' : 'tab-btn-inactive');
}

/* ============================================================
   LIVE CLOCK
   ============================================================ */
function updateDateTime() {
    const now = new Date();
    const dateEl = document.getElementById('current-date');
    const timeEl = document.getElementById('current-time');
    if (dateEl) dateEl.textContent = now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    if (timeEl) timeEl.textContent = now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
}

/* ============================================================
   TAG SYSTEM
   ============================================================ */
function initTagSystem() {
    const tagInput  = document.getElementById('tag-input');
    const addBtn    = document.getElementById('add-tag');
    const tagList   = document.getElementById('tag-list');
    const hidden    = document.getElementById('tag-hidden');
    const removeAll = document.getElementById('remove-all-tags');

    if (!tagInput) return;

    let tags = hidden.value
        ? hidden.value.split(',').map(t => t.trim()).filter(Boolean)
        : [];

    tags.forEach(createTag);
    updateHidden();

    function updateHidden() {
        hidden.value = tags.join(', ');
        removeAll.classList.toggle('hidden', tags.length <= 1);
    }

    function createTag(text) {
        const el = document.createElement('span');
        el.className = 'flex items-center bg-teal-400 text-white px-3 py-1.5 rounded-full text-xs gap-1.5';
        el.innerHTML = `${escHtml(text)} <button type="button" class="font-bold leading-none hover:text-teal-200">×</button>`;
        el.querySelector('button').onclick = () => {
            tags = tags.filter(t => t !== text);
            el.remove();
            updateHidden();
        };
        tagList.appendChild(el);
    }

    function addTag() {
        const value = tagInput.value.trim();
        if (!value || tags.includes(value)) { tagInput.value = ''; return; }
        tags.push(value);
        createTag(value);
        updateHidden();
        tagInput.value = '';
    }

    addBtn.addEventListener('click', addTag);
    tagInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addTag(); } });
    removeAll.addEventListener('click', () => { tags = []; tagList.innerHTML = ''; updateHidden(); });
}

/* ============================================================
   FREKUENSI SYSTEM
   ============================================================ */
function initFrekuensiSystem() {
    const frek       = document.getElementById('frekuensi_penerbitan');
    const bulan      = document.getElementById('bulan_pertama_rilis');
    const tanggal    = document.getElementById('tanggal_rilis');
    const tahunRilis = document.getElementById('tahun_pertama_rilis');

    if (!frek) return;

    const hariPerBulan = {1:31,2:28,3:31,4:30,5:31,6:30,7:31,8:31,9:30,10:31,11:30,12:31};

    function genTanggal(max) {
        tanggal.innerHTML = '<option value="">-- Pilih Tanggal --</option>';
        for (let i = 1; i <= max; i++) {
            const o = document.createElement('option');
            o.value = i; o.textContent = i;
            tanggal.appendChild(o);
        }
    }

    function handleFrek() {
        bulan.disabled   = true;
        tanggal.disabled = true;
        bulan.value      = '';
        tanggal.value    = '';
        tanggal.innerHTML = '<option value="">-- Pilih Tanggal --</option>';

        const val      = frek.value;
        const isDekade = val === 'Dekade';

        tahunRilis.disabled = !isDekade;
        if (!isDekade) tahunRilis.value = '';

        if (val === 'Tahunan' || val === 'Dekade') {
            bulan.disabled   = false;
            tanggal.disabled = false;
            genTanggal(31);
        } else if (val) {
            tanggal.disabled = false;
            genTanggal(31);
        }
    }

    bulan.addEventListener('change', function () {
        if (frek.value === 'Tahunan' || frek.value === 'Dekade') {
            genTanggal(hariPerBulan[parseInt(this.value)] || 31);
        }
    });

    frek.addEventListener('change', handleFrek);
    handleFrek();
}

/* ============================================================
   TIPE GROUP → GROUP BY
   ============================================================ */
function initTipeGroupSystem() {
    const tipeGroup = document.getElementById('tipe_group');
    const groupBy   = document.getElementById('group_by');

    if (!tipeGroup || !groupBy) return;

    function handleTipeGroup() {
        const isActive = tipeGroup.value === '1';

        if (groupBy.tomselect) {
            if (isActive) {
                groupBy.tomselect.enable();
            } else {
                groupBy.tomselect.disable();
                groupBy.tomselect.clear();
            }
        } else {
            groupBy.disabled = !isActive;
            groupBy.required = isActive;
            if (!isActive) groupBy.value = '';
        }
    }

    tipeGroup.addEventListener('change', handleTipeGroup);
    handleTipeGroup();
}

/* ============================================================
   NAMA DUPLICATE CHECK
   ============================================================ */
function initNamaChecker() {
    const namaInput = document.getElementById('nama');
    const status    = document.getElementById('namaStatus');
    const submitBtn = document.getElementById('submitBtn');

    if (!namaInput) return;

    let timeout = null;

    namaInput.addEventListener('input', function () {
        clearTimeout(timeout);
        const val = this.value.trim();

        if (val.length < 3) { status.classList.add('hidden'); submitBtn.disabled = false; return; }

        status.innerHTML   = '<i class="fas fa-spinner fa-spin mr-1"></i>Mengecek nama...';
        status.className   = 'mt-1 text-xs text-gray-400';
        status.classList.remove('hidden');

        timeout = setTimeout(() => {
            fetch(`{{ route('metadata.check_nama') }}?nama=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(d => {
                    if (d.exists) {
                        status.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Nama sudah digunakan';
                        status.className = 'mt-1 text-xs text-red-500';
                        submitBtn.disabled = true;
                    } else {
                        status.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Nama tersedia';
                        status.className = 'mt-1 text-xs text-green-600';
                        submitBtn.disabled = false;
                    }
                })
                .catch(() => { status.classList.add('hidden'); submitBtn.disabled = false; });
        }, 500);
    });
}

/* ============================================================
   GAMBAR RUJUKAN PREVIEW
   ============================================================ */
function handleGambarRujukan(input) {
    const wrap    = document.getElementById('gambarPreviewWrap');
    const preview = document.getElementById('gambarPreview');
    const label   = document.getElementById('gambarLabel');
    const nameEl  = document.getElementById('gambarPreviewName');

    if (!input.files || !input.files[0]) {
        wrap.classList.add('hidden');
        label.textContent = 'Pilih gambar (JPG / PNG / SVG / WEBP, maks 500 KB)';
        return;
    }

    const file    = input.files[0];
    const maxSize = 500 * 1024;

    if (file.size > maxSize) {
        alert(`Ukuran file terlalu besar (${(file.size / 1024).toFixed(1)} KB). Maksimal 500 KB.`);
        input.value = '';
        wrap.classList.add('hidden');
        label.textContent = 'Pilih gambar (JPG / PNG / SVG / WEBP, maks 500 KB)';
        return;
    }

    label.textContent  = file.name;
    nameEl.textContent = `${file.name} · ${(file.size / 1024).toFixed(1)} KB`;
    wrap.classList.remove('hidden');

    if (file.type !== 'image/svg+xml') {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.alt = 'SVG file';
    }
}

/* ============================================================
   FILE HANDLING — EXCEL
   ============================================================ */
function onFileSelected(input) {
    if (input.files && input.files[0]) setFile(input.files[0]);
}

function handleDrop(event) {
    event.preventDefault();
    document.getElementById('dropZone').classList.remove('border-sky-400', 'bg-sky-50');
    const file = event.dataTransfer.files[0];
    if (file) setFile(file);
}

function setFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls'].includes(ext)) {
        showImportAlert('error', 'Format file tidak didukung. Gunakan .xlsx atau .xls.');
        return;
    }

    currentFile = file;
    safeSetText('fileName', file.name);
    safeSetText('fileSize', formatFileSize(file.size));
    document.getElementById('fileInfo').classList.remove('hidden');
    document.getElementById('dropZone').classList.add('hidden');
    document.getElementById('btnPreview').disabled = false;

    resetPreview();
}

function clearFile() {
    currentFile = null;
    document.getElementById('excelFile').value = '';
    document.getElementById('fileInfo').classList.add('hidden');
    document.getElementById('dropZone').classList.remove('hidden');
    document.getElementById('btnPreview').disabled = true;
    resetPreview();
}

function resetPreview() {
    document.getElementById('previewResult').classList.add('hidden');
    document.getElementById('importResult').classList.add('hidden');
    document.getElementById('btnImport').classList.add('hidden');
    previewData = [];
    skippedData = [];
}

/* ============================================================
   PREVIEW — AJAX ke /metadata/import/preview
   ============================================================ */
async function doPreview() {
    if (!currentFile) return;

    const previewLoadingEl = safeGet('previewLoading');
    const previewResultEl  = safeGet('previewResult');
    const importResultEl   = safeGet('importResult');
    const btnImportEl      = safeGet('btnImport');
    const btnPreviewEl     = safeGet('btnPreview');

    if (previewLoadingEl) previewLoadingEl.classList.remove('hidden');
    if (previewResultEl) previewResultEl.classList.add('hidden');
    if (importResultEl) importResultEl.classList.add('hidden');
    if (btnImportEl) btnImportEl.classList.add('hidden');
    if (btnPreviewEl) btnPreviewEl.disabled = true;

    const formData = new FormData();
    formData.append('file',   currentFile);
    formData.append('_token', CSRF);

    try {
        const resp = await fetch(PREVIEW_URL, { method: 'POST', body: formData });
        const json = await resp.json();

        if (previewLoadingEl) previewLoadingEl.classList.add('hidden');
        if (btnPreviewEl) btnPreviewEl.disabled = false;

        if (!json.success) {
            showImportAlert('error', json.message || 'Gagal memproses file.');
            return;
        }

        previewData = json.rows;
        skippedData = json.skipped_rows || [];

        renderStats(json);
        renderPreviewTable(previewData);
        renderSkipped(skippedData);

        if (previewResultEl) previewResultEl.classList.remove('hidden');

        if (json.new > 0) {
            safeSetText('importCount', json.new);
            if (btnImportEl) btnImportEl.classList.remove('hidden');
        }

    } catch (err) {
        if (previewLoadingEl) previewLoadingEl.classList.add('hidden');
        if (btnPreviewEl) btnPreviewEl.disabled = false;
        showImportAlert('error', 'Terjadi kesalahan jaringan: ' + err.message);
    }
}

/* ============================================================
   IMPORT — AJAX ke /metadata/import/store
   ============================================================ */
async function doImport() {
    if (!currentFile) return;

    const countEl    = document.getElementById('importCount');
    const countValue = countEl ? countEl.textContent.trim() : '0';

    if (!confirm(`Import ${countValue} metadata ke database?`)) return;

    const btnImport = safeGet('btnImport');
    if (btnImport) {
        btnImport.disabled = true;
        btnImport.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengimport...';
    }

    const formData = new FormData();
    formData.append('file',   currentFile);
    formData.append('_token', CSRF);
    formData.append('skip_existing', document.getElementById('skipExisting').checked ? '1' : '0');

    const dpEl = document.getElementById('defaultProdusen');
    const dp = dpEl ? dpEl.value : null;

    if (dp) formData.append('produsen_default_id', dp);

    try {
        const resp = await fetch(IMPORT_URL, { method: 'POST', body: formData });
        const json = await resp.json();

        if (btnImport) { btnImport.disabled = false; }
        if (btnImport) btnImport.innerHTML =
            `<i class="fas fa-database mr-2"></i> Import ke Database
             <span id="importCount" class="bg-white text-emerald-600 text-xs font-bold px-1.5 py-0.5 rounded-full">${countValue}</span>`;

        if (json.success) {
            const importResultEl = safeGet('importResult');
            if (importResultEl) importResultEl.innerHTML =
                `<div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    <i class="fas fa-check-circle text-green-500 text-lg shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Import Berhasil!</p>
                        <p class="mt-1">${escHtml(json.message)}</p>
                        <a href="${escHtml(json.redirect)}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-green-600 hover:text-green-800 underline">
                            <i class="fas fa-arrow-right"></i> Ke Halaman Approval →
                        </a>
                    </div>
                </div>`;
            if (importResultEl) importResultEl.classList.remove('hidden');
            if (btnImport) btnImport.classList.add('hidden');
            // show any row-level errors returned by server
            if (json.error_count && Array.isArray(json.errors) && json.errors.length > 0) {
                const importErrorsEl = safeGet('importErrors');
                if (importErrorsEl) {
                    importErrorsEl.innerHTML = `
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                            <p class="font-semibold">Beberapa baris tidak diimport</p>
                            <p class="text-xs mt-1">${json.error_count} baris gagal karena produsen tidak ditemukan.</p>
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead class="bg-yellow-100 text-yellow-800 text-left"><tr>
                                        <th class="px-3 py-2">Row</th>
                                        <th class="px-3 py-2">Nama</th>
                                        <th class="px-3 py-2">Produsen Input</th>
                                        <th class="px-3 py-2">Alasan</th>
                                    </tr></thead>
                                    <tbody>` + json.errors.map(e => `
                                        <tr>
                                            <td class="px-3 py-2 text-gray-600">${escHtml(e.row)}</td>
                                            <td class="px-3 py-2 text-gray-800">${escHtml(e.nama)}</td>
                                            <td class="px-3 py-2 text-gray-700">${escHtml(e.produsen_input)}</td>
                                            <td class="px-3 py-2 text-yellow-900">${escHtml(e.reason)}</td>
                                        </tr>
                                    `).join('') + `</tbody></table></div></div>`;
                    importErrorsEl.classList.remove('hidden');
                }
            }
        } else {
            showImportAlert('error', json.message || 'Import gagal.');
        }

    } catch (err) {
        btnImport.disabled = false;
        showImportAlert('error', 'Terjadi kesalahan: ' + err.message);
    }
}

/* ============================================================
   RENDER HELPERS
   ============================================================ */
function renderStats(json) {
    const stats = [
        { label: 'Total Baris',   value: json.total_rows, color: '#e0f2fe', text: '#0369a1' },
        { label: 'Akan Diimport', value: json.new,        color: '#dcfce7', text: '#15803d' },
        { label: 'Sudah di DB',   value: json.dup_db,     color: '#fef3c7', text: '#92400e' },
        { label: 'Dilewati',      value: json.skipped,    color: '#f3f4f6', text: '#6b7280' },
    ];
    // if there are produsen errors, add a badge
    if (json.produsen_errors_count && json.produsen_errors_count > 0) {
        stats.push({ label: 'Produsen Tidak Cocok', value: json.produsen_errors_count, color: '#fff7ed', text: '#c2410c' });
    }

    document.getElementById('statsBar').innerHTML = stats.map(s =>
        `<div class="rounded-lg p-3 text-center" style="background:${s.color};">
            <p class="text-2xl font-bold" style="color:${s.text};">${s.value}</p>
            <p class="text-xs font-medium mt-0.5" style="color:${s.text};">${s.label}</p>
        </div>`
    ).join('');
}

function renderPreviewTable(rows) {
    /*
     * PERUBAHAN:
     * - Kolom "Nama (Setelah Normalisasi)" dihapus. Nama di DB sudah tidak dinormalisasi.
     *   Yang dinormalisasi hanya alias, bukan nama. Kolom yang ditampilkan:
     *   Row | Nama | Alias (Generik) | Klasifikasi | Tipe Data | Satuan | Tahun | Produsen | Status
     * - Kolom tahun pakai r.tahun_mulai_data (bukan r.tahun_data)
     */
    document.getElementById('previewBody').innerHTML = rows.length === 0
        ? '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-400 italic">Tidak ada data valid</td></tr>'
        : rows.map(r => `
            <tr class="hover:bg-gray-50 transition-colors ${r.exists_in_db ? 'row-existing' : 'row-new'}"
                style="${r.exists_in_db ? 'opacity:0.55;' : ''}">
                <td class="px-3 py-2.5 text-gray-400">${r.row}</td>
                <td class="px-3 py-2.5">
                    <p class="font-semibold text-gray-800">${escHtml(r.nama)}</p>
                </td>
                <td class="px-3 py-2.5 text-gray-400 text-xs italic">
                    ${r.alias ? escHtml(r.alias) : '<span class="text-gray-300">—</span>'}
                </td>
                <td class="px-3 py-2.5">
                    <span class="px-2 py-0.5 rounded-full text-xs" style="background:#e0f2fe; color:#0369a1;">
                        ${escHtml(r.klasifikasi)}
                    </span>
                </td>
                <td class="px-3 py-2.5 text-gray-600">${escHtml(r.tipe_data)}</td>
                <td class="px-3 py-2.5 text-gray-600">${escHtml(r.satuan_data)}</td>
                <td class="px-3 py-2.5 text-gray-600">${escHtml(r.tahun_mulai_data)}</td>
                <td class="px-3 py-2.5 text-gray-500 max-w-32 truncate" title="${escHtml(r.produsen)}">
                    ${escHtml(r.produsen)}
                    ${(!r.produsen || r.produsen === '-' ) ? '<div class="text-xs text-yellow-800 mt-1"><i class="fas fa-exclamation-triangle"></i> Produsen tidak ditemukan</div>' : ''}
                </td>
                <td class="px-3 py-2.5 text-center">
                    ${r.exists_in_db
                        ? '<span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#fef3c7; color:#92400e;">Sudah ada</span>'
                        : '<span class="px-2 py-0.5 rounded-full text-xs font-medium" style="background:#dcfce7; color:#15803d;">Baru</span>'}
                </td>
            </tr>`
        ).join('');
}

function renderSkipped(rows) {
    const section = document.getElementById('skippedSection');
    if (rows.length === 0) { section.classList.add('hidden'); return; }

    section.classList.remove('hidden');
    document.getElementById('skippedLabel').textContent = `Lihat ${rows.length} baris yang dilewati (duplikat)`;
    document.getElementById('skippedBody').innerHTML = rows.map(r =>
        `<tr>
            <td class="px-3 py-2 text-gray-400">${r.row}</td>
            <td class="px-3 py-2 text-gray-600">${escHtml(r.nama)}</td>
            <td class="px-3 py-2">
                <span class="px-2 py-0.5 rounded-full text-xs" style="background:#fef3c7; color:#b45309;">
                    ${escHtml(r.reason)}
                </span>
            </td>
        </tr>`
    ).join('');
}

function applyTableFilter() {
    const hide = document.getElementById('hideExisting').checked;
    document.querySelectorAll('.row-existing').forEach(tr => {
        tr.style.display = hide ? 'none' : '';
    });
}

function toggleSkipped() {
    skippedOpen = !skippedOpen;
    document.getElementById('skippedTable').classList.toggle('hidden', !skippedOpen);
    document.getElementById('skippedChevron').className = skippedOpen ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
}

function showImportAlert(type, msg) {
    const isErr = type === 'error';
    const el    = document.getElementById('importResult');
    if (!el) {
        if (isErr) {
            alert(msg);
        } else {
            console.log(msg);
        }
        return;
    }
    el.innerHTML =
        `<div class="flex items-center gap-3 p-4 rounded-lg text-sm
                     ${isErr ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'}">
            <i class="fas ${isErr ? 'fa-exclamation-circle text-red-500' : 'fa-check-circle text-green-500'} text-lg shrink-0"></i>
            <p>${escHtml(msg)}</p>
        </div>`;
    el.classList.remove('hidden');
}

/* ============================================================
   HEADER TABEL PREVIEW — sesuaikan dengan kolom baru
   Ganti <thead> di HTML tabel preview menjadi:

   <tr>
       <th class="px-3 py-2.5 font-semibold w-12">Row</th>
       <th class="px-3 py-2.5 font-semibold min-w-52">Nama</th>
       <th class="px-3 py-2.5 font-semibold min-w-36 text-gray-400">Alias Generik</th>
       <th class="px-3 py-2.5 font-semibold">Klasifikasi</th>
       <th class="px-3 py-2.5 font-semibold">Tipe Data</th>
       <th class="px-3 py-2.5 font-semibold">Satuan</th>
       <th class="px-3 py-2.5 font-semibold">Tahun</th>
       <th class="px-3 py-2.5 font-semibold">Produsen</th>
       <th class="px-3 py-2.5 font-semibold text-center">Status</th>
   </tr>
   ============================================================ */

/* ============================================================
   INIT — satu DOMContentLoaded untuk semua
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    const hasError = {{ $errors->any() ? 'true' : 'false' }};
    switchTab(hasError ? 'manual' : 'manual'); // default selalu manual

    updateDateTime();
    setInterval(updateDateTime, 1000);

    initTomSelect('.tom-select');   
    initTagSystem();
    initFrekuensiSystem();
    initTipeGroupSystem(); 
    initNamaChecker();
});
</script>

@endsection