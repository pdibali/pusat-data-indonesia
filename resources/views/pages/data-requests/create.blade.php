@extends('layouts.main')

@section('content')
<div class="bg-white rounded-xl shadow p-4 lg:p-6 max-w-3xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('data_requests.index') }}" class="text-xs text-sky-400 hover:text-sky-600 flex items-center gap-1 mb-6">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
        </a>
        <h1 class="text-lg sm:text-xl font-bold text-gray-800">Ajukan Usulan Penyediaan Data</h1>
        <p class="text-xs sm:text-sm text-gray-400 mt-0.5">
            Sampaikan data yang Anda butuhkan namun belum tersedia di platform ini.
        </p>
    </div>

    <form action="{{ route('data_requests.store') }}" method="POST" class="space-y-5">
        @csrf

        {{-- Nama Data --}}
        <div class="md:flex md:items-start md:gap-6">
            <label class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-0 md:w-48 md:pt-2.5 md:shrink-0">
                Nama Data <span class="text-red-500">*</span>
            </label>
            <div class="flex-1">
                <input type="text" name="nama_data" value="{{ old('nama_data') }}" required maxlength="255"
                       placeholder="Contoh: Jumlah Produksi Padi per Kecamatan"
                       class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 @error('nama_data') border-red-400 @enderror">
                @error('nama_data') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Wilayah --}}
        <div class="md:flex md:items-start md:gap-6">
            <label class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-0 md:w-48 md:pt-2.5 md:shrink-0">
                Wilayah <span class="text-red-500">*</span>
            </label>
            <div class="flex-1">
                <x-wilayah-cascade />
            </div>
        </div>

        {{-- Deskripsi Data --}}
        <div class="md:flex md:items-start md:gap-6">
            <label class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-0 md:w-48 md:pt-2.5 md:shrink-0">
                Deskripsi Data <span class="text-red-500">*</span>
            </label>
            <div class="flex-1">
                <textarea name="deskripsi" rows="4" required maxlength="2000"
                          placeholder="Isi deskripsi data yang Anda ajukan..."
                          class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 @error('deskripsi') border-red-400 @enderror">{{ old('deskripsi') }}</textarea>
                          <p class="text-xs text-gray-800 mt-1">Jelaskan data seperti apa yang Anda butuhkan, periode waktu, tingkat kedetailan, dsb.</p>
                @error('deskripsi') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Instansi Perkiraan --}}
        <div class="md:flex md:items-start md:gap-6">
            <label class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-0 md:w-48 md:pt-2.5 md:shrink-0">
                Instansi Produsen (Perkiraan) <span class="text-red-500">*</span>
            </label>
            <div class="flex-1">
                <input type="text" name="instansi_perkiraan" value="{{ old('instansi_perkiraan') }}" required maxlength="255"
                       placeholder="Contoh: Dinas Pertanian Provinsi Bali"
                       class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 @error('instansi_perkiraan') border-red-400 @enderror">
                @error('instansi_perkiraan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Tombol — kanan di semua breakpoint --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('data_requests.index') }}"
               class="px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700">
                Batal
            </a>
            <button type="submit"
                    class="px-4 py-2.5 btn-primary text-sm font-semibold rounded-lg shadow-sm flex items-center gap-2">
                <i class="fas fa-paper-plane"></i>
                <span>Ajukan Usulan</span>
            </button>
        </div>
    </form>
</div>
@endsection