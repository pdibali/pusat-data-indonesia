@if($sourceComparison->isEmpty())
    <p class="text-xs text-gray-400 text-center py-4">Tidak ada data pembanding.</p>
@else
    @php
        $unitsConsistent   = $sourceComparison->first()['units_consistent'] ?? true;
        $sourceComparison  = $sourceComparison->sortByDesc(fn($s) => $s['data_id'] === ($data->id ?? null));
    @endphp
    @unless($unitsConsistent)
    <div class="mb-2 px-3 py-2 rounded-lg text-xs font-medium bg-pink-50 text-pink-700 border border-pink-200 flex items-center gap-2">
        <i class="fas fa-scale-unbalanced"></i>
        Satuan rujukan berbeda antar sumber untuk periode ini, periksa kemungkinan kesalahan normalisasi selain konflik nilainya.
    </div>
    @endunless
    <div class="overflow-x-auto -mx-5 px-5 sm:mx-0 sm:px-0">
        <table class="w-full min-w-225 text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-2 text-left text-gray-500 font-medium">Rujukan</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">Angka di Satuan Lama (Rujukan)</th>
                    <th class="px-2 py-2 text-left text-gray-500 font-medium">Satuan Lama (Rujukan)</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">Angka Setelah Disesuaikan</th>
                    <th class="px-2 py-2 text-left text-gray-500 font-medium">Satuan Sesuai Metadata</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">Selisih</th>
                    <th class="px-2 py-2 text-right text-gray-500 font-medium">% Diff</th>
                    <th class="px-2 py-2 text-center text-gray-500 font-medium">Status Nilai</th>
                    <th class="px-2 py-2 text-center text-gray-500 font-medium">Status Satuan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($sourceComparison as $src)
                <tr class="{{ $src['conflict'] ? 'bg-amber-50/40' : '' }}">
                    <td class="px-2 py-2 font-medium text-gray-700">{{ $src['rujukan'] }}</td>
                    <td class="px-2 py-2 text-right font-mono text-gray-800">
                        {{ $src['old_unit_value'] !== null ? number_format($src['old_unit_value'], 2) : '—' }}
                    </td>
                    <td class="px-2 py-2 text-gray-500">{{ $src['old_unit_name'] ?? '—' }}</td>
                    <td class="px-2 py-2 text-right font-mono text-gray-800">
                        {{ number_format($src['adjusted_value'], 2) }}
                    </td>
                    <td class="px-2 py-2 text-gray-500">{{ $src['satuan_metadata'] ?? '—' }}</td>
                    <td class="px-2 py-2 text-right font-mono {{ $src['selisih'] >= 0 ? 'text-red-600' : 'text-blue-600' }}">
                        {{ $src['selisih'] >= 0 ? '+' : '' }}{{ number_format($src['selisih'], 2) }}
                    </td>
                    <td class="px-2 py-2 text-right font-mono {{ $src['conflict'] ? 'text-amber-600 font-semibold' : 'text-gray-500' }}">
                        {{ $src['pct_diff'] }}%
                    </td>
                    <td class="px-2 py-2 text-center">
                        @if($src['conflict'])
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                style="background:#fef9c3; color:#a16207;">Konflik</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                style="background:#dcfce7; color:#15803d;">OK</span>
                        @endif
                    </td>
                    <td class="px-2 py-2 text-center">
                        @if($src['unit_conflict'] ?? false)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                style="background:#fce7f3; color:#be185d;">Konflik</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                style="background:#dcfce7; color:#15803d;">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif