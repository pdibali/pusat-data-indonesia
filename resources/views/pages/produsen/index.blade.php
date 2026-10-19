{{-- resources/views/pages/produsen/index.blade.php --}}
@extends('layouts.main')

@section('title', 'Kelola Produsen')

@section('content')
<div class="page-layout">

    <div class="page-header">
        <div>
            <h2 class="text-lg font-semibold text-gray-600">Kelola Produsen</h2>
            <p class="text-xs text-gray-500 mt-0.5">Manajemen data produsen / publisher data</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('admin.produsen.import') }}"
               class="btn-primary bg-indigo-600 hover:bg-indigo-700 text-xs">
                <i class="fas fa-file-upload"></i> Bulk Input Excel
            </a>
            <a href="{{ route('admin.produsen.create') }}"
               class="btn-primary text-xs">
                <i class="fas fa-plus"></i> Tambah Produsen
            </a>
        </div>
    </div>

    @include('layouts.alert')

    <form method="GET" action="{{ route('admin.produsen.index') }}"
                    class="card-panel p-3 flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari nama, email, contact person..."
               class="flex-1 bg-white/5 border border-gray-600 text-gray-700 text-xs rounded-lg px-3 py-2
                      placeholder-gray-600 focus:outline-none focus:border-green-400/50">
        <label class="flex items-center gap-2 text-xs text-gray-500">
            Urutan
            <select name="sort" onchange="this.form.submit()"
                    class="bg-white border border-gray-300 text-gray-700 text-xs rounded-lg px-3 py-2 focus:outline-none focus:border-green-400/50">
                <option value="terbaru" {{ request('sort', 'terbaru') === 'terbaru' ? 'selected' : '' }}>Terbaru</option>
                <option value="terlama" {{ request('sort') === 'terlama' ? 'selected' : '' }}>Terlama</option>
            </select>
        </label>
        <button type="submit"
                class="btn-primary text-xs">
            <i class="fas fa-search"></i> Cari
        </button>
        <a href="{{ route('admin.produsen.export', request()->query()) }}"
           class="btn-primary bg-green-600 hover:bg-green-700 text-xs">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        @if(request('search'))
            <a href="{{ route('admin.produsen.index') }}"
               class="bg-white/10 hover:bg-white/20 text-gray-400 text-xs px-4 py-2 rounded-lg transition">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>

    <div class="card-panel">
        @if ($produsen->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-gray-500">
                <i class="fas fa-industry text-4xl mb-3 opacity-30"></i>
                <p class="text-sm font-medium">Belum ada produsen</p>
                <p class="text-xs mt-1">Klik "Tambah Produsen" untuk menambahkan data baru</p>
            </div>
        @else
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-300 text-gray-400 bg-white/5">
                        <th class="text-left px-4 py-3 font-semibold">#</th>
                        <th class="text-left px-4 py-3 font-semibold">Nama Produsen</th>
                        <th class="text-left px-4 py-3 font-semibold">Contact Person</th>
                        <th class="text-left px-4 py-3 font-semibold">Kontak</th>
                        <th class="text-left px-4 py-3 font-semibold">Email</th>
                        <th class="text-left px-4 py-3 font-semibold">Status</th>
                        <th class="text-center px-4 py-3 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach ($produsen as $i => $p)
                        <tr class="hover:bg-white/5 transition text-gray-300">
                            <td class="px-4 py-3 text-gray-500">{{ $produsen->firstItem() + $i }}</td>
                            <td class="px-4 py-3 font-medium text-gray-600">{{ $p->nama_produsen }} <span class="text-xs text-gray-300">({{ $p->produsen_id }})</span></td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->nama_contact_person ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->kontak ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->email ?? '-' }}</td>
                            <td class="py-3">
                                @if ($p->status === 1)
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <i class="fas fa-check-circle text-xs"></i> Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-red-500/10 text-red-400 border border-red-500/20">
                                        <i class="fas fa-circle-xmark text-xs"></i> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.produsen.show', $p) }}"
                                       class="p-1.5 rounded-md bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.produsen.edit', $p) }}"
                                       class="p-1.5 rounded-md bg-green-500/10 text-green-400 hover:bg-green-500/20 transition">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.produsen.toggle_status', $p) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @if ($p->status === 1)
                                            <button type="submit"
                                                    class="p-1.5 rounded-md bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 transition"
                                                    title="Nonaktifkan"
                                                    onclick="return confirm('Yakin ingin menonaktifkan produsen {{ addslashes($p->nama_produsen) }}?')">
                                                <i class="fas fa-ban text-xs"></i>
                                            </button>
                                        @else
                                            <button type="submit"
                                                    class="p-1.5 rounded-md bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition"
                                                    title="Aktifkan">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($produsen->hasPages())
                <div class="px-4 py-3 border-t border-white/10">
                    {{ $produsen->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection