@php
    $plans = $plans ?? collect();

    // Batasi maksimal kolom biar card gak jadi terlalu kurus kalau paket makin banyak.
    // Di atas 3 paket, akan wrap ke baris berikutnya (tetap proporsional & seragam).
    $planCount = $plans->count();
    $colsClass = match (true) {
        $planCount <= 1 => 'lg:grid-cols-1',
        $planCount === 2 => 'lg:grid-cols-2',
        $planCount === 3 => 'lg:grid-cols-3',
        $planCount === 4 => 'lg:grid-cols-4',
        default          => 'lg:grid-cols-5',
    };
@endphp

@if($plans->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-10 text-center text-sm text-slate-500">
        Belum ada paket untuk kategori ini.
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 {{ $colsClass }} auto-rows-fr gap-4">
        @foreach($plans as $layanan)
            @php
                $sudahAktif = auth()->check()
                    ? \App\Models\Transaksi::where('user_id', auth()->user()->user_id)
                        ->where('layanan_id', $layanan->layanan_id)
                        ->where('status', 'success')
                        ->where(function ($q) { $q->whereNull('aktif_sampai')->orWhere('aktif_sampai', '>=', now()); })
                        ->exists()
                    : false;
            @endphp

            @if($layanan->is_popular)
                <div class="relative flex flex-col h-full min-w-0 overflow-hidden bg-white ring-2 ring-sky-400 shadow-2xl shadow-sky-500/15 w-full">
                    <div class="h-[3px] bg-sky-400 w-full shrink-0"></div>
                    <div class="bg-sky-50 border-b border-sky-100 px-4 py-2 flex items-center justify-between shrink-0">
                        <span class="flex items-center gap-1 text-[9px] font-black text-sky-700 uppercase tracking-widest font-poppins">
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            Paling Populer
                        </span>
                        <span class="text-[8px] font-bold text-sky-500 uppercase tracking-widest font-poppins">Terbaik</span>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0 p-[14px] pt-4">
                        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-2 font-poppins">{{ $layanan->durasi_label }}</div>
                        <div class="text-xs font-bold text-stikom mb-1 font-poppins truncate">{{ $layanan->nama_layanan }}</div>
                        <div class="text-xl font-black text-stikom font-poppins leading-tight break-words">{{ $layanan->harga_format }}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5 mb-3 font-body">/ {{ strtolower($layanan->durasi_label) }}</div>
                        <div class="border-t border-sky-100 mb-3"></div>
                        <ul class="flex-1 overflow-y-auto space-y-2 mb-4 pr-0.5" style="min-height: 90px; max-height: 140px; scrollbar-width: thin; scrollbar-color: rgba(16,185,129,.25) transparent;">
                            @forelse($layanan->fiturs->sortBy('urutan') as $fitur)
                                <li class="flex items-start gap-2 text-[11px] leading-snug {{ $fitur->aktif ? 'text-gray-600' : 'text-gray-300 opacity-40' }}">
                                    @if($fitur->aktif)
                                        <div class="w-[14px] h-[14px] bg-stikom-blue flex items-center justify-center shrink-0 mt-px">
                                            <svg class="w-[7px] h-[7px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-[14px] h-[14px] border border-gray-200 flex items-center justify-center shrink-0 mt-px">
                                            <svg class="w-[7px] h-[7px] text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <span class="break-words">{{ $fitur->nama_fitur }}</span>
                                </li>
                            @empty
                                <li class="text-gray-300 text-[10px] italic font-body">Fitur belum diatur.</li>
                            @endforelse
                        </ul>
                        @if($sudahAktif)
                            <div class="w-full py-3 mt-auto text-[10px] font-black text-center shrink-0 bg-gray-50 text-gray-400 font-poppins cursor-default border border-gray-200 uppercase tracking-wide">
                                ✓ Langganan Aktif
                            </div>
                        @elseif(auth()->check())
                            <form method="POST" action="{{ route('transaksi.checkout') }}" class="mt-auto">
                                @csrf
                                <input type="hidden" name="layanan_id" value="{{ $layanan->layanan_id }}">
                                <button type="submit" class="w-full py-3 text-[10px] font-black text-stikom hover:text-white bg-stikom-accent hover:bg-stikom-accent transition-colors duration-200 font-poppins uppercase tracking-wide shrink-0">
                                    Pilih {{ $layanan->nama_layanan }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}?redirect={{ urlencode(route('langganan')) }}" class="block w-full py-3 mt-auto text-[10px] font-black text-center text-stikom shrink-0 bg-stikom-accent hover:bg-yellow-600 hover:text-white transition-all duration-200 font-poppins uppercase tracking-wide">
                                <p class="px-2">Login untuk Berlangganan</p>
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white border border-gray-100 flex flex-col h-full min-w-0 overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md w-full" style="border-top: 3px solid transparent;" onmouseenter="this.style.borderTopColor='#A32D2D'" onmouseleave="this.style.borderTopColor='transparent'">
                    <div class="px-4 py-1.5 border-b border-gray-100 shrink-0">
                        <span class="text-stikom-red text-[9px] font-bold uppercase tracking-widest font-poppins">Paket</span>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0 p-[14px]">
                        <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-2 font-poppins">{{ $layanan->durasi_label }}</div>
                        <div class="text-xs font-bold text-stikom mb-1 font-poppins truncate">{{ $layanan->nama_layanan }}</div>
                        <div class="text-lg font-black text-stikom font-poppins leading-tight break-words">{{ $layanan->harga_format }}</div>
                        <div class="text-[10px] text-gray-400 mt-0.5 mb-3 font-body">/ {{ strtolower($layanan->durasi_label) }}</div>
                        <div class="border-t border-gray-100 mb-3"></div>
                        <ul class="flex-1 overflow-y-auto space-y-2 mb-4 pr-0.5" style="min-height: 90px; max-height: 140px; scrollbar-width: thin; scrollbar-color: rgba(0,0,0,.1) transparent;">
                            @forelse($layanan->fiturs->sortBy('urutan') as $fitur)
                                <li class="flex items-start gap-2 text-[11px] leading-snug {{ $fitur->aktif ? 'text-gray-600' : 'text-gray-300 opacity-40' }}">
                                    @if($fitur->aktif)
                                        <div class="w-[14px] h-[14px] bg-stikom-blue flex items-center justify-center shrink-0 mt-px">
                                            <svg class="w-[7px] h-[7px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-[14px] h-[14px] border border-gray-200 flex items-center justify-center shrink-0 mt-px">
                                            <svg class="w-[7px] h-[7px] text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <span class="break-words">{{ $fitur->nama_fitur }}</span>
                                </li>
                            @empty
                                <li class="text-gray-300 text-[10px] italic font-body">Fitur belum diatur.</li>
                            @endforelse
                        </ul>
                        @if($sudahAktif)
                            <div class="w-full py-3 mt-auto text-[10px] font-black text-center shrink-0 bg-gray-50 text-gray-400 font-poppins cursor-default border border-gray-200 uppercase tracking-wide">
                                ✓ Langganan Aktif
                            </div>
                        @elseif(auth()->check())
                            <form method="POST" action="{{ route('transaksi.checkout') }}" class="mt-auto">
                                @csrf
                                <input type="hidden" name="layanan_id" value="{{ $layanan->layanan_id }}">
                                <button type="submit" class="w-full py-3 text-[10px] font-black text-stikom shrink-0 bg-stikom-accent hover:bg-yellow-600 hover:text-white transition-all duration-200 font-poppins uppercase tracking-wide">
                                    Pilih {{ $layanan->nama_layanan }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}?redirect={{ urlencode(route('langganan')) }}" class="block w-full py-3 mt-auto text-[10px] font-black text-center text-stikom shrink-0 bg-stikom-accent hover:bg-yellow-600 hover:text-white transition-all duration-200 font-poppins uppercase tracking-wide">
                                <p class="px-2">Login untuk Berlangganan</p>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif