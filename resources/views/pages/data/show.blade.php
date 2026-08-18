@extends('layouts.main')

@section('content')
<div class="py-6">

    @php
        $backUrl = route('data.index');
        // Jika ada parameter from=approval, prioritaskan kembali ke halaman approval
        if (request()->get('from') === 'approval') {
            $backUrl = route('data.approval');
        } else {
            // otherwise try to use previous URL only if it's a GET route on this app
            $prev = url()->previous();
            if ($prev && $prev !== request()->fullUrl()) {
                try {
                    $prevRequest = \Illuminate\Http\Request::create($prev);
                    $matched = app('router')->getRoutes()->match($prevRequest);
                    $methods = $matched->methods();
                    if (in_array('GET', $methods)) {
                        $backUrl = $prev;
                    }
                } catch (\Exception $e) {
                    // fallback to index
                }
            }
        }
    @endphp

    <a href="{{ $backUrl }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    {{-- HEADER CARD --}}
    <div class="mt-2 bg-white rounded-xl shadow p-6">

        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-1">Detail Data</p>
                <h1 class="text-xl font-bold text-gray-800">
                    {{ $datum->metadata->nama ?? 'Data #' . $datum->id }}
                </h1>
                @if($datum->metadata?->alias)
                    <p class="text-sm text-gray-400 mt-0.5">{{ $datum->metadata->alias }}</p>
                @endif
            </div>

            {{-- Status badge + aksi admin --}}
            <div class="flex flex-col items-end gap-2 shrink-0">
                @php
                    $statusMap = [
                        0 => ['label' => 'Pending',   'style' => 'background:#fef3c7; color:#b45309;', 'icon' => 'fa-clock'],
                        1 => ['label' => 'Available', 'style' => 'background:#dcfce7; color:#15803d;', 'icon' => 'fa-check-circle'],
                        2 => ['label' => 'Ditolak',   'style' => 'background:#fee2e2; color:#b91c1c;', 'icon' => 'fa-times-circle'],
                    ];
                    $s = $statusMap[$datum->status] ?? $statusMap[0];
                @endphp
                <span style="{{ $s['style'] }}"
                      class="px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5">
                    <i class="fas {{ $s['icon'] }}"></i>
                    {{ $s['label'] }}
                </span>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('data.edit', $datum->id) }}"
                       class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm border border-sky-200 text-sky-700 bg-sky-50 hover:bg-sky-100">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>

                {{-- Tombol approve/reject untuk admin --}}
                @if($datum->status == 0)
                    <div class="flex gap-2">
                        <form action="{{ route('data.approve', $datum->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="background-color:#22c55e; color:#fff;"
                                onmouseover="this.style.backgroundColor='#16a34a'"
                                onmouseout="this.style.backgroundColor='#22c55e'"
                                class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                                <i class="fas fa-check"></i> Setujui
                            </button>
                        </form>
                        <form action="{{ route('data.reject', $datum->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                style="background-color:#f87171; color:#fff;"
                                onmouseover="this.style.backgroundColor='#ef4444'"
                                onmouseout="this.style.backgroundColor='#f87171'"
                                class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                                <i class="fas fa-times"></i> Tolak
                            </button>
                        </form>
                    </div>
                @elseif($datum->status == 1)
                    <form action="{{ route('data.reject', $datum->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            style="border:1px solid #fca5a5; color:#ef4444; background:transparent;"
                            onmouseover="this.style.backgroundColor='#fef2f2'"
                            onmouseout="this.style.backgroundColor='transparent'"
                            class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-ban"></i> Cabut Persetujuan
                        </button>
                    </form>
                @elseif($datum->status == 2)
                    <form action="{{ route('data.approve', $datum->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                            style="border:1px solid #86efac; color:#16a34a; background:transparent;"
                            onmouseover="this.style.backgroundColor='#f0fdf4'"
                            onmouseout="this.style.backgroundColor='transparent'"
                            class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-redo"></i> Pulihkan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- INFO META --}}
        <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-400 border-t pt-3">
            <span><i class="fas fa-hashtag mr-1"></i>ID: <strong class="text-gray-600">{{ $datum->id }}</strong></span>
            <span><i class="fas fa-user mr-1"></i>Diinput oleh: <strong class="text-gray-600">{{ $datum->user->name ?? '-' }}</strong></span>
            <span><i class="fas fa-calendar mr-1"></i>
                {{ \Carbon\Carbon::parse($datum->date_inputed)->translatedFormat('d F Y, H:i') }} WITA
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">

        {{-- KOLOM KIRI: Nilai Data --}}
        <div class="lg:col-span-1 space-y-5">

            {{-- Nilai --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-sky-500"></i> Nilai Data
                </h2>

                {{-- Nilai Angka --}}
                @if(!is_null($datum->number_value))
                    <div class="mb-4 p-4 bg-sky-50 border border-sky-100 rounded-lg text-center">
                        <p class="text-xs text-sky-500 font-medium uppercase tracking-wide mb-1">Nilai Angka</p>
                        <p class="text-3xl font-bold text-sky-700">
                            {{ rtrim(rtrim(number_format($datum->number_value, 2, ',', '.'), '0'), ',') }}
                        </p>
                        @if($datum->metadata?->satuan_data)
                            <p class="text-sm text-sky-400 mt-1">{{ $datum->metadata->satuan_data }}</p>
                        @endif
                    </div>
                @endif

                @if(is_null($datum->number_value) && !$datum->text_value && is_null($datum->kategori_value) && !$datum->other)
                    <p class="text-sm text-gray-400 text-center py-4">Tidak ada nilai yang diinput</p>
                @endif
            </div>

            {{-- Rujukan Data --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-link text-rose-500"></i> Rujukan
                </h2>

                @if($datum->rujukan)
                    <p class="text-sm text-gray-600">{{ $datum->rujukan->nama_rujukan }}</p>
                @else
                    <p class="text-sm text-gray-400">Data rujukan tidak ditemukan.</p>
                @endif
            </div>

            {{-- Produsen Data --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-link text-rose-500"></i> Produsen Data
                </h2>

                @if($datum->produsen)
                    <p class="text-sm text-gray-600">{{ $datum->produsen->nama_produsen }}</p>
                @else
                    <p class="text-sm text-gray-400">Data produsen tidak ditemukan.</p>
                @endif
            </div>

        </div>

        {{-- KOLOM KANAN: Dimensi --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Dimensi Metadata --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-database text-sky-500"></i> Metadata
                </h2>

                @if($datum->metadata)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        @php
                            $metaFields = [
                                'Nama'                  => $datum->metadata->nama,
                                'Alias'                 => $datum->metadata->alias,
                                'Klasifikasi'           => $datum->metadata->klasifikasi?->nama_klasifikasi,
                                'Tipe Data'             => $datum->metadata->tipe_data,
                                'Satuan Data'           => $datum->metadata->satuan_data,
                                'Ukuran Data'           => $datum->metadata->ukuran_data,
                                'Sumber Data'           => $datum->metadata->data_sumber,
                                'Frekuensi Penerbitan'  => $datum->metadata->frekuensi_penerbitan,
                                'Produsen Data'         => $datum->metadata->produsen_data,
                                'Rujukan'               => $datum->metadata->rujukan,
                            ];
                        @endphp

                        @foreach($metaFields as $label => $value)
                            @if($value)
                                <div>
                                    <p class="text-xs text-gray-400 font-medium">{{ $label }}</p>
                                    <p class="text-gray-700 font-semibold mt-0.5">{{ $value }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if($datum->metadata->definisi)
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-xs text-gray-400 font-medium mb-1">Definisi</p>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $datum->metadata->definisi }}</p>
                        </div>
                    @endif

                    @if($datum->metadata->konsep)
                        <div class="mt-3">
                            <p class="text-xs text-gray-400 font-medium mb-1">Konsep</p>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $datum->metadata->konsep }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-400">Metadata tidak ditemukan.</p>
                @endif
            </div>

            {{-- Dimensi Lokasi --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-rose-500"></i> Lokasi
                </h2>

                @if($datum->location)
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-3 text-sm">
                        @php
                            $locFields = [
                                'Nama Lokasi'          => $datum->location->nama_wilayah,
                            ];
                        @endphp
                        @foreach($locFields as $label => $value)
                            @if($value)
                                <div>
                                    <p class="text-xs text-gray-400 font-medium">{{ $label }}</p>
                                    <p class="text-gray-700 font-semibold mt-0.5">{{ $value }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Kode wilayah --}}
                    <div class="mt-4 pt-4 border-t flex flex-wrap gap-3">
                        @foreach([
                            'Kode Lokasi'   => $datum->location->location_id,
                        ] as $label => $kode)
                            @if($kode)
                                <div class="bg-gray-50 border rounded-md px-3 py-1.5 text-xs">
                                    <span class="text-gray-400">{{ $label }}: </span>
                                    <span class="font-mono font-semibold text-gray-700">{{ $kode }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400">Data lokasi tidak ditemukan.</p>
                @endif
            </div>
            {{-- Dimensi Waktu --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-emerald-500"></i> Waktu
                </h2>

                @if($datum->time)

                    @php
                        $bulanList = [
                            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                        ];

                        $time = $datum->time;

                        $decade   = $time->decade;
                        $year     = $time->year;
                        $semester = $time->semester;
                        $quarter  = $time->quarter;
                        $month    = $time->month;

                        // Penentuan periode (prioritas: bulan > kuartal > semester > all)
                        if ($month != 0) {
                            $periode = $bulanList[$month];
                            $label   = 'Bulan';
                            $style   = 'bg-indigo-50 border-indigo-200 text-indigo-700';
                        } elseif ($quarter != 0) {
                            $periode = 'Kuartal ' . $quarter;
                            $label   = 'Kuartal';
                            $style   = 'bg-sky-50 border-sky-200 text-sky-700';
                        } elseif ($semester != 0) {
                            $periode = 'Semester ' . $semester;
                            $label   = 'Semester';
                            $style   = 'bg-emerald-50 border-emerald-200 text-emerald-700';
                        } else {
                            $periode = 'All';
                            $label   = 'Periode';
                            $style   = 'bg-gray-50 border-gray-200 text-gray-600';
                        }
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                        {{-- Dekade --}}
                        <div class="bg-gray-50 border rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-400 mb-1">Dekade</p>
                            <p class="text-lg font-bold text-gray-700">
                                {{ $decade ? $decade . '-an' : 'All' }}
                            </p>
                        </div>

                        {{-- Tahun --}}
                        <div class="bg-gray-50 border rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-400 mb-1">Tahun</p>
                            <p class="text-lg font-bold text-gray-700">
                                {{ ($year && $year != 0) ? $year : 'All' }}
                            </p>
                        </div>

                        {{-- Periode (Dynamic) --}}
                        <div class="rounded-lg p-3 text-center border {{ $style }}">
                            <p class="text-xs opacity-70 mb-1">
                                {{ $label }}
                            </p>
                            <p class="text-lg font-bold">
                                {{ $periode }}
                            </p>
                        </div>

                    </div>

                @else
                    <p class="text-sm text-gray-400">Data waktu tidak ditemukan.</p>
                @endif
            </div>

            {{-- Kontak Person --}}
            @if($datum->metadata?->nama_contact_person)
                <div class="bg-white rounded-xl shadow p-5">
                    <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-address-card text-teal-500"></i> Kontak Person
                    </h2>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Nama</p>
                            <p class="text-gray-700 font-semibold mt-0.5">{{ $datum->metadata->nama_contact_person }}</p>
                        </div>
                        @if($datum->metadata->nomor_contact_person)
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Nomor</p>
                                <a href="tel:{{ $datum->metadata->nomor_contact_person }}"
                                   class="text-sky-600 hover:text-sky-800 font-semibold mt-0.5 block transition-colors">
                                    {{ $datum->metadata->nomor_contact_person }}
                                </a>
                            </div>
                        @endif
                        @if($datum->metadata->email_contact_person)
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Email</p>
                                <a href="mailto:{{ $datum->metadata->email_contact_person }}"
                                   class="text-sky-600 hover:text-sky-800 font-semibold mt-0.5 block transition-colors">
                                    {{ $datum->metadata->email_contact_person }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection