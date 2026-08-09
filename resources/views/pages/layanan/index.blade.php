@extends('layouts.main')

@section('title', 'Kelola Layanan')

@section('content')
<div class="page-layout">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Kelola Layanan</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manajemen paket layanan & pricing</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Toggle Midtrans --}}
            @php $midtransOn = \App\Support\Setting::get('midtrans_enabled', true); @endphp
            <form action="{{ route('admin.settings.toggle_midtrans') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <span class="text-sm text-gray-600 font-medium">Midtrans</span>
                <button type="submit"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 {{ $midtransOn ? 'bg-green-500' : 'bg-gray-300' }}"
                        title="{{ $midtransOn ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 {{ $midtransOn ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
            </form>

            <a href="{{ route('admin.layanan.create') }}" class="btn-primary">
                <i class="fas fa-plus text-xs"></i> Tambah Layanan
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <div class="card-panel">
        {{-- Filters --}}
        <div class="p-4 border-b border-gray-100">
            <form id="form-layanan" method="GET" action="{{ route('admin.layanan.index') }}">
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[180px] max-w-xs">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari layanan..."
                            oninput="autoSubmitDebounce(this)"
                            class="w-full pl-9 pr-8 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @if(request('search'))
                            <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-red-400 transition">
                                <i class="fas fa-times text-xs"></i>
                            </a>
                        @endif
                    </div>

                    {{-- Status --}}
                    <select name="status" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="publish"  {{ request('status') === 'publish'  ? 'selected' : '' }}>Publish</option>
                        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="takedown" {{ request('status') === 'takedown' ? 'selected' : '' }}>Takedown</option>
                    </select>

                    {{-- Active chips + Reset --}}
                    @if(request()->hasAny(['search','status']))
                        <div class="flex items-center gap-1.5 ml-1">
                            @if(request('status'))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-xs font-medium text-blue-700">
                                    {{ ucfirst(request('status')) }}
                                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="hover:text-red-500 transition">
                                        <i class="fas fa-times text-[10px]"></i>
                                    </a>
                                </span>
                            @endif
                            <a href="{{ route('admin.layanan.index') }}"
                            class="text-xs text-gray-400 hover:text-red-500 transition flex items-center gap-1">
                                <i class="fas fa-times-circle"></i> Reset semua
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            @if($layanan->isEmpty())
            <div class="py-16 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-store text-gray-400 text-xl"></i>
                </div>
                <p class="text-gray-500 text-sm font-medium">Belum ada layanan</p>
                <p class="text-gray-400 text-xs mt-1">Mulai dengan menambahkan paket layanan.</p>
                <a href="{{ route('admin.layanan.create') }}"
                   class="btn-link">
                    <i class="fas fa-plus text-xs"></i> Tambah sekarang
                </a>
            </div>
            @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-5 py-3 text-left font-medium w-10">#</th>
                        <th class="px-5 py-3 text-left font-medium">Layanan</th>
                        <th class="px-5 py-3 text-left font-medium">Harga</th>
                        <th class="px-5 py-3 text-left font-medium">Durasi</th>
                        <th class="px-5 py-3 text-left font-medium">Status</th>
                        <th class="px-5 py-3 text-left font-medium">Populer</th>
                        <th class="px-5 py-3 text-left font-medium">Urutan</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($layanan as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3.5 text-gray-400">
                            {{ ($layanan->currentPage() - 1) * $layanan->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                @if($item->thumbnail)
                                <img src="{{ asset('storage/' . $item->thumbnail) }}"
                                     alt="{{ $item->nama_layanan }}"
                                     class="w-9 h-9 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                                @else
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-box text-gray-400 text-xs"></i>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-800">{{ $item->nama_layanan }}</p>
                                    <p class="text-xs text-gray-400">{{ $item->fiturs_count }} fitur</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 font-medium text-gray-700">
                            {{ $item->harga_format }}
                        </td>
                        <td class="px-5 py-3.5 text-gray-600">
                            {{ $item->durasi_label }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($item->status === 'publish')
                            <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs font-medium px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Publish
                            </span>
                            @elseif($item->status === 'pending')
                            <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-xs font-medium px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span> Pending
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Takedown
                            </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <form action="{{ route('admin.layanan.toggle_popular', $item) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="transition {{ $item->is_popular
                                            ? 'text-yellow-500 hover:text-yellow-600'
                                            : 'text-gray-300 hover:text-yellow-400' }}"
                                        title="{{ $item->is_popular ? 'Hapus dari populer' : 'Tandai populer' }}">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500">{{ $item->urutan }}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.layanan.show', $item) }}"
                                   class="p-1.5 rounded-md bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition" title="Detail">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('admin.layanan.edit', $item) }}"
                                   class="p-1.5 rounded-md bg-green-500/10 text-green-400 hover:bg-green-500/20 transition" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>

                                {{-- Status Quick Actions --}}
                                @if($item->status !== 'publish')
                                <form action="{{ route('admin.layanan.publish', $item) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-md bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition" title="Publish">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                </form>
                                @endif
                                @if($item->status !== 'takedown')
                                <form action="{{ route('admin.layanan.takedown', $item) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-md bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 transition" title="Takedown">
                                        <i class="fas fa-eye-slash text-xs"></i>
                                    </button>
                                </form>
                                @endif
                                @if($item->status !== 'pending')
                                <form action="{{ route('admin.layanan.draft', $item) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-md bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 transition" title="Draft">
                                        <i class="fas fa-file-alt text-xs"></i>
                                    </button>
                                </form>
                                @endif

                                <button type="button"
                                        onclick="confirmDelete('{{ route('admin.layanan.destroy', $item) }}', '{{ $item->nama_layanan }}')"
                                        class="p-1.5 rounded-md bg-red-500/10 text-red-400 hover:bg-red-500/20 transition" title="Hapus">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Pagination --}}
        @if($layanan->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <span>Menampilkan {{ $layanan->firstItem() }}–{{ $layanan->lastItem() }} dari {{ $layanan->total() }} data</span>
            <div>{{ $layanan->links() }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Modal Hapus --}}
<div id="modal-delete" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full mx-auto mb-4">
            <i class="fas fa-trash text-red-500"></i>
        </div>
        <h3 class="text-center text-gray-800 font-semibold text-base">Hapus Layanan?</h3>
        <p class="text-center text-gray-500 text-sm mt-1">
            Anda akan menghapus <strong id="modal-nama" class="text-gray-700"></strong>. Thumbnail dan semua fitur akan ikut terhapus.
        </p>
        <form id="modal-form" method="POST" class="mt-5 flex gap-3">
            @csrf @method('DELETE')
            <button type="button" onclick="closeModal()"
                    class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">Batal</button>
            <button type="submit"
                    class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">Ya, Hapus</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(url, nama) {
    document.getElementById('modal-form').action = url;
    document.getElementById('modal-nama').textContent = nama;
    document.getElementById('modal-delete').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('modal-delete').classList.add('hidden');
}
document.getElementById('modal-delete').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush
@endsection