@extends('layouts.main')

@section('content')
<div class="py-6">
    <a href="{{ route('data.show', $datum) }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali ke Detail
    </a>

    <div class="mt-2 bg-white rounded-xl shadow p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start gap-3 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Edit Data</h1>
                <p class="text-sm text-gray-400 mt-1">Perbarui data yang sudah tersimpan dan kirim ulang untuk verifikasi.</p>
            </div>
            <div class="text-sm text-gray-500">
                <p>ID Data #{{ $datum->id }}</p>
            </div>
        </div>

        <form action="{{ route('data.update', $datum) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Metadata <span class="text-red-500">*</span></label>
                    <select name="metadata_id" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" required>
                        <option value="">Pilih metadata...</option>
                        @foreach($metadataList as $meta)
                            <option value="{{ $meta->metadata_id }}" {{ old('metadata_id', $datum->metadata_id) == $meta->metadata_id ? 'selected' : '' }}>
                                {{ $meta->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('metadata_id')
                        <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Lokasi <span class="text-red-500">*</span></label>
                    <select name="location_id" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" required>
                        <option value="">Pilih lokasi...</option>
                        @foreach($locationList as $location)
                            <option value="{{ $location->location_id }}" {{ old('location_id', $datum->location_id) == $location->location_id ? 'selected' : '' }}>
                                {{ $location->nama_wilayah }}
                            </option>
                        @endforeach
                    </select>
                    @error('location_id')
                        <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Waktu <span class="text-red-500">*</span></label>
                    <select name="time_id" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" required>
                        <option value="">Pilih waktu...</option>
                        @foreach($timeList as $time)
                            @php
                                $timeLabel = collect([$time->year, $time->semester ? 'S'.$time->semester : null, $time->quarter ? 'Q'.$time->quarter : null, $time->month ? date('F', mktime(0,0,0,$time->month,1)) : null])->filter()->implode(' - ');
                            @endphp
                            <option value="{{ $time->time_id }}" {{ old('time_id', $datum->time_id) == $time->time_id ? 'selected' : '' }}>
                                {{ $timeLabel ?: 'Periode '.$time->time_id }}
                            </option>
                        @endforeach
                    </select>
                    @error('time_id')
                        <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Rujukan <span class="text-red-500">*</span></label>
                    <p class="mt-1 text-xs text-gray-400">Produsen akan terisi otomatis berdasarkan pilihan Rujukan jika tidak diisi manual.</p>
                    <select id="rujukan_id" name="rujukan_id" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" required>
                        <option value="">Pilih rujukan...</option>
                        @foreach($rujukanList as $rujukan)
                            <option value="{{ $rujukan->rujukan_id }}" {{ old('rujukan_id', $datum->rujukan_id) == $rujukan->rujukan_id ? 'selected' : '' }}>
                                {{ $rujukan->nama_rujukan }}
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="produsen_id" id="hiddenProdusenId" value="{{ old('produsen_id', $datum->produsen_id) }}">
                    <div id="produsenInfo" class="{{ $datum->produsen ? '' : 'hidden' }} mt-2 px-3 py-2 bg-emerald-50 border border-emerald-100 rounded-md text-xs text-emerald-700 flex items-center gap-2">
                        <i class="fa-solid fa-industry text-emerald-500"></i>
                        Produsen: <span id="produsenInfoText" class="font-semibold ml-1">{{ $datum->produsen?->nama_produsen ?? '' }}</span>
                        <span class="text-emerald-400 ml-1">(otomatis dari rujukan)</span>
                    </div>
                    @error('rujukan_id')
                        <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nilai Angka</label>
                <input type="number" step="0.01" name="number_value" value="{{ old('number_value', $datum->number_value) }}" placeholder="Masukkan nilai angka" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                @error('number_value')
                    <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-md shadow text-sm font-semibold flex items-center gap-2 transition-colors">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const PRODUSEN_URL = '{{ route("data.get_produsen_by_rujukan") }}';
    const CSRF = '{{ csrf_token() }}';

    async function syncProdusenInfo(value) {
        const hidden = document.getElementById('hiddenProdusenId');
        const infoEl = document.getElementById('produsenInfo');
        const infoTxt = document.getElementById('produsenInfoText');

        if (!value) {
            hidden.value = '';
            infoEl.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`${PRODUSEN_URL}?rujukan_id=${value}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            });
            const json = await response.json();

            if (json.success) {
                hidden.value = json.produsen_id || '';
                infoTxt.textContent = json.nama_produsen || '-';
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

    document.addEventListener('DOMContentLoaded', () => {
        const rujukanSelect = document.getElementById('rujukan_id');
        if (rujukanSelect) {
            rujukanSelect.addEventListener('change', (event) => syncProdusenInfo(event.target.value));
            syncProdusenInfo(rujukanSelect.value);
        }
    });
</script>
@endsection
