@extends('layouts.main')

@section('content')
@php
    $statusConfig = [
        'diajukan' => ['label' => 'Diajukan', 'bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-200', 'icon' => 'fa-paper-plane'],
        'ditinjau' => ['label' => 'Ditinjau', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => 'fa-hourglass-half'],
        'diterima' => ['label' => 'Diterima', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'fa-check-circle'],
        'ditolak'  => ['label' => 'Ditolak', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'fa-times-circle'],
    ];
    $cfg = $statusConfig[$dataRequest->status] ?? $statusConfig['diajukan'];
@endphp

<div class="bg-white rounded-xl shadow p-4 lg:p-6 max-w-3xl mx-auto">

    <a href="{{ route('admin.data_requests.index') }}" class="text-xs text-sky-400 hover:text-sky-600 flex items-center gap-1 mb-3">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
    </a>

    <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-800">{{ $dataRequest->nama_data }}</h1>
            <p class="text-xs text-gray-400 mt-1">
                Diajukan oleh <span class="font-medium text-gray-600">{{ $dataRequest->user->name ?? '-' }}</span>
                pada {{ $dataRequest->created_at->format('d M Y, H:i') }} WITA
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }}">
            <i class="fas {{ $cfg['icon'] }} text-[10px]"></i>
            {{ $cfg['label'] }}
        </span>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 border-t border-gray-100 pt-4 mb-4">
        <div>
            <p class="text-xs text-gray-400 mb-1">Wilayah</p>
            <p class="text-sm font-medium text-gray-700">{{ $dataRequest->location->nama_wilayah ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Instansi Produsen (Perkiraan)</p>
            <p class="text-sm font-medium text-gray-700">{{ $dataRequest->instansi_perkiraan }}</p>
        </div>
    </div>

    <div class="mb-4">
        <p class="text-xs text-gray-400 mb-1">Deskripsi Data</p>
        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $dataRequest->deskripsi }}</p>
    </div>

    @if($dataRequest->status !== 'diajukan')
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 mb-4">
            <p class="text-xs text-gray-400 mb-1">
                Ditinjau oleh <span class="font-medium text-gray-600">{{ $dataRequest->reviewer->name ?? '-' }}</span>
                pada {{ $dataRequest->reviewed_at?->format('d M Y, H:i') }} WITA
            </p>
            @if($dataRequest->admin_notes)
                <p class="text-sm text-gray-700 mt-2">{{ $dataRequest->admin_notes }}</p>
            @endif
        </div>
    @endif

    @if(in_array(auth()->user()->group_id, [1, 2], true) && in_array($dataRequest->status, ['diajukan', 'ditinjau']))
        <div class="border-t border-gray-100 pt-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Tindak Lanjuti Usulan</h2>
            <form action="{{ route('admin.data_requests.review', $dataRequest) }}" method="POST" id="reviewForm">
                @csrf
                <input type="hidden" name="status" id="reviewStatus">
                <textarea name="admin_notes" rows="3" maxlength="2000" placeholder="Catatan (opsional)"
                          class="w-full rounded-md p-2 border-2 border-gray-300 text-sm mb-3"></textarea>
                <div class="flex gap-2">
                    <button type="button" onclick="submitReview('diterima')"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg flex items-center gap-2">
                        <i class="fas fa-check"></i> Terima Usulan
                    </button>
                    <button type="button" onclick="submitReview('ditolak')"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg flex items-center gap-2">
                        <i class="fas fa-times"></i> Tolak Usulan
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
function submitReview(status) {
    const label = status === 'diterima' ? 'menerima' : 'menolak';
    Swal.fire({
        title: `Yakin ingin ${label} usulan ini?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: status === 'diterima' ? '#059669' : '#dc2626',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('reviewStatus').value = status;
            document.getElementById('reviewForm').submit();
        }
    });
}
</script>
@endsection