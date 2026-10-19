@extends('layouts.main')

@section('title', 'Bulk Input Produsen')

@section('content')
<div class="space-y-4">
    <div class="flex items-center gap-2 text-xs text-gray-500">
        <a href="{{ route('admin.produsen.index') }}" class="hover:text-green-500 transition">Kelola Produsen</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="text-gray-700">Bulk Input Excel</span>
    </div>

    @include('layouts.alert')

    <div class="card-panel p-6 space-y-5">
        <div>
            <h2 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-file-import text-green-500 mr-2"></i>Bulk Input Produsen
            </h2>
            <p class="text-xs text-gray-500 mt-2">
                Isi template Excel lalu unggah untuk menambahkan beberapa produsen sekaligus.
                Jika ada baris tidak valid atau nama yang sudah terdaftar, seluruh file ditolak tanpa menyimpan sebagian data.
            </p>
        </div>

        <a href="{{ route('admin.produsen.import.template') }}"
           class="inline-flex items-center gap-2 btn-primary text-xs">
            <i class="fas fa-download"></i> Unduh Template Excel
        </a>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-xs text-blue-800">
            <p class="font-semibold mb-1">Kolom template</p>
            <p><code>nama_produsen</code> wajib diisi. Kolom lainnya: <code>nama_contact_person</code>, <code>kontak</code>, <code>email</code>, <code>alamat</code>, dan <code>status</code>.</p>
            <p class="mt-1">Status boleh dikosongkan (default Aktif), atau diisi <code>1/aktif</code> maupun <code>0/nonaktif</code>.</p>
        </div>

        <form action="{{ route('admin.produsen.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="file_excel" class="block text-xs font-semibold text-gray-700 mb-2">File Excel (.xlsx atau .xls)</label>
                <input id="file_excel" name="file_excel" type="file" accept=".xlsx,.xls" required
                       class="block w-full text-xs text-gray-600 border border-gray-300 rounded-lg p-2">
                <p class="text-[11px] text-gray-400 mt-1">Ukuran maksimal 10MB. Baris kosong akan dilewati.</p>
                @error('file_excel')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if (session('bulk_import_errors'))
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-700">
                    <p class="font-semibold mb-2">Periksa baris berikut:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach (session('bulk_import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary text-xs">
                    <i class="fas fa-eye"></i> Preview Data
                </button>
                <a href="{{ route('admin.produsen.index') }}"
                   class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-gray-500 text-xs px-4 py-2 rounded-lg transition">
                    Batal
                </a>
            </div>
        </form>

        @if ($preview)
            <section class="space-y-3 border-t border-gray-200 pt-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Pratinjau Produsen</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ count($preview['rows']) }} baris dibaca,
                            {{ $preview['valid_count'] }} valid,
                            {{ count($preview['errors']) }} masalah.
                        </p>
                    </div>
                    @if (!empty($preview['errors']))
                        <span class="rounded-full bg-red-50 border border-red-200 px-3 py-1 text-xs text-red-700">
                            Perbaiki file untuk melanjutkan
                        </span>
                    @else
                        <span class="rounded-full bg-green-50 border border-green-200 px-3 py-1 text-xs text-green-700">
                            Semua baris siap diimpor
                        </span>
                    @endif
                </div>

                @if (!empty($preview['errors']))
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-xs text-red-700">
                        <p class="font-semibold mb-2">Masalah yang ditemukan:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($preview['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left">Baris</th>
                                <th class="px-3 py-2 text-left">Nama Produsen</th>
                                <th class="px-3 py-2 text-left">Contact Person</th>
                                <th class="px-3 py-2 text-left">Kontak</th>
                                <th class="px-3 py-2 text-left">Email</th>
                                <th class="px-3 py-2 text-left">Alamat</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Validasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($preview['rows'] as $row)
                                <tr class="{{ empty($row['_errors']) ? 'bg-white' : 'bg-red-50' }}">
                                    <td class="px-3 py-2 text-gray-500">{{ $row['_excel_row'] }}</td>
                                    <td class="px-3 py-2 font-medium text-gray-700">{{ $row['nama_produsen'] ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['nama_contact_person'] ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['kontak'] ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['email'] ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['alamat'] ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ ($row['status'] ?? null) === 1 ? 'Aktif' : (($row['status'] ?? null) === 0 ? 'Nonaktif' : 'Tidak valid') }}
                                    </td>
                                    <td class="px-3 py-2">
                                        @if (empty($row['_errors']))
                                            <span class="text-green-700">Valid</span>
                                        @else
                                            <ul class="list-disc pl-4 text-red-700">
                                                @foreach ($row['_errors'] as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">Tidak ada baris produsen untuk ditampilkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($token && empty($preview['errors']))
                    <form action="{{ route('admin.produsen.import.confirm') }}" method="POST" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit" class="btn-primary text-xs"
                                onclick="return confirm('Simpan {{ $preview['valid_count'] }} produsen yang ditampilkan?')">
                            <i class="fas fa-check"></i> Konfirmasi dan Simpan {{ $preview['valid_count'] }} Produsen
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
