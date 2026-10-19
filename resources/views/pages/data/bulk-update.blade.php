@extends('layouts.main')

@section('content')
<div class="py-6">
    <a href="{{ route('data.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali ke Halaman Data
    </a>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-800">Bulk Update Data</h1>
            <p class="text-sm text-gray-500 mt-1">
                Unggah template Excel input data. Sistem membandingkan nilai periode dengan data tersimpan dan menampilkan perubahan sebelum diterapkan.
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Gunakan template dari halaman <a href="{{ route('metadata.index') }}" class="text-sky-600 hover:underline">Metadata</a>.
                Baris yang belum ada akan dilewati. Sel periode, rujukan, produsen, atau satuan yang kosong tidak mengubah nilai tersimpan.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('data.bulk_update.preview') }}" method="POST" enctype="multipart/form-data"
              class="rounded-lg border border-gray-200 bg-gray-50 p-5">
            @csrf
            <label for="file_excel" class="block text-sm font-semibold text-gray-700 mb-2">File Excel</label>
            <input id="file_excel" name="file_excel" type="file" accept=".xlsx,.xls" required
                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-sky-700">
            <p class="mt-2 text-xs text-gray-400">Format .xlsx atau .xls, maksimal 10MB.</p>
            <button type="submit"
                    class="mt-4 inline-flex items-center gap-2 rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-sky-700">
                <i class="fas fa-search"></i> Bandingkan Data
            </button>
        </form>

        @isset($preview)
            <section class="mt-7">
                <h2 class="text-lg font-bold text-gray-800">Hasil Perbandingan</h2>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs text-amber-700">Perubahan ditemukan</p>
                        <p class="mt-1 text-2xl font-bold text-amber-800">{{ $preview['changed_count'] }}</p>
                    </div>
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p class="text-xs text-green-700">Nilai tidak berubah</p>
                        <p class="mt-1 text-2xl font-bold text-green-800">{{ $preview['unchanged_count'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs text-gray-600">Data Excel dilewati (belum ada)</p>
                        <p class="mt-1 text-2xl font-bold text-gray-800">{{ $preview['unmatched_count'] }}</p>
                    </div>
                </div>

                @if($preview['changes'] !== [])
                    <div class="mt-5 overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Metadata</th>
                                    <th class="px-4 py-3">Lokasi</th>
                                    <th class="px-4 py-3">Periode</th>
                                    <th class="px-4 py-3">Rujukan</th>
                                    <th class="px-4 py-3">Produsen</th>
                                    <th class="px-4 py-3">Rincian Perubahan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($preview['changes'] as $change)
                                    <tr>
                                        <td class="px-4 py-3">{{ $change['metadata'] }}</td>
                                        <td class="px-4 py-3">{{ $change['location'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $change['period'] }}</td>
                                        <td class="px-4 py-3">{{ $change['reference'] }}</td>
                                        <td class="px-4 py-3">{{ $change['producer'] }}</td>
                                        <td class="px-4 py-3">
                                            <ul class="space-y-1">
                                                @foreach($change['field_changes'] as $fieldChange)
                                                    <li>
                                                        <span class="font-medium text-gray-700">{{ $fieldChange['field'] }}:</span>
                                                        <span class="text-gray-500">{{ $fieldChange['old'] }}</span>
                                                        <i class="fas fa-arrow-right mx-1 text-xs text-gray-400"></i>
                                                        <span class="font-semibold text-violet-700">{{ $fieldChange['new'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form action="{{ route('data.bulk_update.apply') }}" method="POST" class="mt-5 flex justify-end">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-violet-700"
                                onclick="return confirm('Terapkan {{ $preview['changed_count'] }} perubahan ini? Data yang diperbarui akan dikirim ulang untuk verifikasi.')">
                            <i class="fas fa-save"></i> Terapkan Perubahan
                        </button>
                    </form>
                @else
                    <div class="mt-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Tidak ada perbedaan nilai yang perlu diterapkan.
                    </div>
                @endif
            </section>
        @endisset
    </div>
</div>
@endsection
