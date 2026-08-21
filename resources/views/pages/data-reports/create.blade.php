@extends('layouts.main')

@section('content')
<div class="bg-white rounded-xl shadow p-4 lg:p-6 max-w-3xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('data_reports.index') }}" class="text-xs text-sky-400 hover:text-sky-600 flex items-center gap-1 mb-2">
            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
        </a>
        <h1 class="text-lg sm:text-xl font-bold text-gray-800">Laporkan Data Bermasalah</h1>
        <p class="text-xs sm:text-sm text-gray-400 mt-0.5">
            Beri tahu kami kalau ada data di platform ini yang menurut Anda salah atau tidak akurat.
        </p>
    </div>

    <form action="{{ route('data_reports.store') }}" method="POST" class="space-y-5">
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

        {{-- Produsen Data --}}
        <div class="md:flex md:items-start md:gap-6">
            <label class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-0 md:w-48 md:pt-2.5 md:shrink-0">
                Produsen Data <span class="text-red-500">*</span>
            </label>
            <div class="flex-1">
                <input type="text" name="produsen_data" value="{{ old('produsen_data') }}" required maxlength="255"
                       placeholder="Contoh: Dinas Pertanian Provinsi Bali"
                       class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 @error('produsen_data') border-red-400 @enderror">
                @error('produsen_data') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Deskripsi Kesalahan --}}
        <div class="md:flex md:items-start md:gap-6">
            <label class="block text-sm font-medium text-gray-700 mb-1.5 md:mb-0 md:w-48 md:pt-2.5 md:shrink-0">
                Deskripsi Kesalahan <span class="text-red-500">*</span>
            </label>
            <div class="flex-1">
                <textarea name="deskripsi_kesalahan" rows="4" required maxlength="2000"
                          placeholder="Jelaskan apa yang salah pada data ini — misal angka tidak sesuai, satuan keliru, periode data tidak update, dsb."
                          class="w-full p-2 rounded-md border-2 border-gray-300 text-sm focus:border-sky-400 focus:ring-sky-400 @error('deskripsi_kesalahan') border-red-400 @enderror">{{ old('deskripsi_kesalahan') }}</textarea>
                @error('deskripsi_kesalahan') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('data_reports.index') }}"
               class="px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700">
                Batal
            </a>
            <button type="submit"
                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm flex items-center gap-2">
                <i class="fas fa-flag"></i>
                <span>Kirim Laporan</span>
            </button>
        </div>
    </form>
</div>
@endsection