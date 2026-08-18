@extends('layouts.main')

@section('content')

<div class="py-6">

    {{-- Tombol Kembali --}}
    <a href="{{ route('metadata.detail', $metadata->metadata_id) }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali ke Detail
    </a>

    <form action="{{ route('metadata.update', $metadata->metadata_id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ═══════════════════════════════════ HEADER ═══════════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow overflow-hidden mb-5">
            <div style="height:4px; background: linear-gradient(90deg, #0284c7, #38bdf8, #7dd3fc);"></div>
            <div class="p-6">
                <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-pen-to-square text-sky-500"></i>
                    Edit Metadata
                </h1>
                <p class="text-sm text-gray-400 mt-1">
                    ID: <span class="font-mono font-semibold text-gray-500">#{{ $metadata->metadata_id }}</span>
                    — {{ $metadata->nama }}
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
                <p class="font-semibold mb-1.5 flex items-center gap-1.5">
                    <i class="fas fa-triangle-exclamation"></i> Terdapat kesalahan input:
                </p>
                <ul class="list-disc ps-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ═══════════════════════════════ INFORMASI DASAR ═══════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#eff6ff; color:#3b82f6;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-lightbulb"></i>
                </span>
                Informasi Dasar
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nama Metadata *</label>
                    <input type="text" name="nama" value="{{ old('nama', $metadata->nama) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Alias</label>
                    <input type="text" name="alias" value="{{ old('alias', $metadata->alias) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Klasifikasi *</label>
                    <select name="klasifikasi_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                        <option value="">— Pilih Klasifikasi —</option>
                        @foreach($klasifikasiList as $k)
                            <option value="{{ $k->klasifikasi_id }}"
                                @selected(old('klasifikasi_id', $metadata->klasifikasi_id) == $k->klasifikasi_id)>
                                {{ $k->nama_klasifikasi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tag (pisahkan dengan koma) *</label>
                    <input type="text" name="tag" value="{{ old('tag', $metadata->tag) }}"
                           placeholder="contoh: ekonomi, inflasi, harga"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ INFORMASI KONSEPTUAL ═══════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#f0fdf4; color:#22c55e;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-book"></i>
                </span>
                Informasi Konseptual
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Konsep *</label>
                    <textarea name="konsep" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>{{ old('konsep', $metadata->konsep) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Definisi *</label>
                    <textarea name="definisi" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>{{ old('definisi', $metadata->definisi) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Asumsi</label>
                    <textarea name="asumsi" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('asumsi', $metadata->asumsi) }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Metodologi *</label>
                        <input type="text" name="metodologi" maxlength="100" value="{{ old('metodologi', $metadata->metodologi) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Penjelasan Metodologi *</label>
                    <textarea name="penjelasan_metodologi" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>{{ old('penjelasan_metodologi', $metadata->penjelasan_metodologi) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ INFORMASI TEKNIS ═══════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#fffbeb; color:#f59e0b;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-cogs"></i>
                </span>
                Informasi Teknis
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tipe Data *</label>
                    <input type="text" name="tipe_data" maxlength="50" value="{{ old('tipe_data', $metadata->tipe_data) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Satuan Data *</label>
                    <input type="text" name="satuan_data" maxlength="50" value="{{ old('satuan_data', $metadata->satuan_data) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Flag Desimal *</label>
                    <select name="flag_desimal"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                        <option value="0" @selected(old('flag_desimal', $metadata->flag_desimal) == 0)>Tidak Ada Desimal</option>
                        <option value="1" @selected(old('flag_desimal', $metadata->flag_desimal) == 1)>Ada Desimal</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Sumber Metadata Pertama</label>
                    <select name="produsen_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                        <option value="">— Pilih Produsen —</option>
                        @foreach($produsen as $p)
                            <option value="{{ $p->produsen_id }}"
                                @selected(old('produsen_id', $metadata->sumber_metadata_pertama) == $p->produsen_id)>
                                {{ $p->nama_produsen }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tipe Group *</label>
                    <select name="tipe_group" id="tipe_group"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                        <option value="0" @selected(old('tipe_group', $metadata->tipe_group) == 0)>Berdiri Sendiri</option>
                        <option value="1" @selected(old('tipe_group', $metadata->tipe_group) == 1)>Bagian dari Group</option>
                    </select>
                </div>

                <div id="group_by_wrapper" style="{{ old('tipe_group', $metadata->tipe_group) == 1 ? '' : 'display:none;' }}">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Metadata Induk</label>
                    <select name="group_by"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="">— Pilih Metadata Induk —</option>
                        @foreach($metadataList as $m)
                            <option value="{{ $m->metadata_id }}"
                                @selected(old('group_by', $metadata->group_by) == $m->metadata_id)>
                                {{ $m->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ INFORMASI PUBLIKASI ═══════════════════════════════ --}}
        <div class="bg-white rounded-xl shadow p-5 mb-5">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2 pb-3 border-b">
                <span style="background:#fff1f2; color:#f43f5e;"
                      class="w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0">
                    <i class="fas fa-newspaper"></i>
                </span>
                Informasi Publikasi
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tahun Mulai Data (label) *</label>
                    <input type="text" name="tahun_mulai_data" maxlength="50"
                           value="{{ old('tahun_mulai_data', $metadata->tahun_mulai_data) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                    <p class="text-xs text-gray-400 mt-1">Tahun mulai data aktual dihitung otomatis dari data yang sudah masuk.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tahun Metadata</label>
                    <input type="number" name="tahun_metadata" min="1900" max="2100"
                           value="{{ old('tahun_metadata', $metadata->tahun_metadata ?? 2026) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Frekuensi Penerbitan *</label>
                    <input type="text" name="frekuensi_penerbitan" maxlength="50"
                           value="{{ old('frekuensi_penerbitan', $metadata->frekuensi_penerbitan) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tahun Pertama Rilis</label>
                    <input type="number" name="tahun_pertama_rilis" min="1900" max="2100"
                           value="{{ old('tahun_pertama_rilis', $metadata->tahun_pertama_rilis) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Bulan Pertama Rilis</label>
                    <select name="bulan_pertama_rilis"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="">— Tidak ditentukan —</option>
                        @php
                            $bulanList = ['','Januari','Februari','Maret','April','Mei','Juni',
                                          'Juli','Agustus','September','Oktober','November','Desember'];
                        @endphp
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" @selected(old('bulan_pertama_rilis', $metadata->bulan_pertama_rilis) == $i)>
                                {{ $bulanList[$i] }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tanggal Rilis</label>
                    <input type="number" name="tanggal_rilis" min="1" max="31"
                           value="{{ old('tanggal_rilis', $metadata->tanggal_rilis) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════ ACTIONS ═══════════════════════════════ --}}
        <div class="flex justify-end gap-3 pb-10">
            <a href="{{ route('metadata.detail', $metadata->metadata_id) }}"
               class="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors"
               style="border:1.5px solid #d1d5db; color:#6b7280; background:transparent;"
               onmouseover="this.style.background='#f9fafb'"
               onmouseout="this.style.background='transparent'">
                Batal
            </a>
            <button type="submit"
                style="background:#0284c7; color:#fff;"
                onmouseover="this.style.background='#0369a1'"
                onmouseout="this.style.background='#0284c7'"
                class="px-6 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors shadow-md">
                <i class="fas fa-floppy-disk"></i>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('tipe_group').addEventListener('change', function () {
        document.getElementById('group_by_wrapper').style.display =
            this.value === '1' ? '' : 'none';
    });
</script>

@endsection