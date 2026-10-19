{{--
    Laporan PDF Anomali Data — dikirim ke Produsen Data sebagai bahan pertimbangan.
    Di-render via barryvdh/laravel-dompdf.

    Variabel yang dibutuhkan (sama seperti show.blade.php):
    - $anomaly            : App\Models\Anomaly
    - $data               : App\Models\Data (dengan relasi metadata, location, produsen, rujukan, time, user)
    - $sourceComparison   : Collection perbandingan sumber (opsional, boleh kosong)
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Anomali Data #{{ $anomaly->anomalies_id }}</title>
    <style>
        @page {
            margin: 55px 50px 45px 50px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.35;
        }

        /* ══════════ KOP SURAT ══════════ */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .kop-table td {
            vertical-align: middle;
            padding: 0;
        }
        .kop-title {
            font-size: 14px;
            font-weight: bold;
            color: #001734;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .kop-subtitle {
            font-size: 10px;
            color: #374151;
            margin: 1px 0 0 0;
        }
        .kop-address {
            font-size: 8.5px;
            color: #6b7280;
            margin: 2px 0 0 0;
        }
        .kop-rule-thick {
            border-top: 2.5px solid #001734;
            margin-top: 8px;
        }
        .kop-rule-thin {
            border-top: 1px solid #001734;
            margin-top: 2px;
            margin-bottom: 12px;
        }

        /* ══════════ JUDUL LAPORAN ══════════ */
        .report-title {
            text-align: center;
            margin-bottom: 12px;
        }
        .report-title h1 {
            font-size: 12px;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .report-title p {
            font-size: 9.5px;
            margin: 0;
        }

        /* ══════════ META SURAT (Nomor/Lampiran/Hal) ══════════ */
        .meta-surat {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9.5px;
        }
        .meta-surat td { padding: 1px 0; }
        .meta-label { width: 70px; }
        .meta-colon { width: 12px; }

        /* ══════════ ISI SURAT ══════════ */
        .isi p {
            margin: 0 0 7px 0;
            text-align: justify;
        }

        /* ══════════ TABEL DETAIL ══════════ */
        .section-heading {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
            margin: 9px 0 4px 0;
            letter-spacing: 0.3px;
        }
        table.detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 9.5px;
        }
        table.detail-table td {
            padding: 2px 6px;
            vertical-align: top;
        }
        table.detail-table td.label {
            width: 105px;
            color: #161616;
        }
        table.detail-table td.colon {
            width: 10px;
        }

        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
        }
        table.grid-table th,
        table.grid-table td {
            border: 1px solid #d1d5db;
            padding: 4px 7px;
        }
        table.grid-table th {
            background: #f3f4f6;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            color: #4b5563;
        }
        table.grid-table td.num { text-align: right; font-family: monospace; }
        table.grid-table td.center { text-align: center; }

        .highlight-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 9.5px;
        }
        .highlight-box .val {
            font-weight: bold;
            font-size: 11px;
            color: #b91c1c;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8.5px;
            font-weight: bold;
        }

        /* ══════════ TANDA TANGAN ══════════ */
        .ttd-wrap {
            width: 100%;
            margin-top: 56px;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-table td {
            width: 50%;
            vertical-align: top;
            font-size: 9.5px;
        }
        .ttd-right {
            text-align: center;
        }
        .ttd-space {
            height: 42px;
        }
        .ttd-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .footer-note {
            margin-top: 12px;
            font-size: 7.5px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    {{-- ═══════════════════════ KOP SURAT ═══════════════════════ --}}
    <table class="kop-table">
        <tr>
            <td>
                <p class="kop-title">PUSAT DATA INDONESIA BALI</p>
                <p class="kop-subtitle">Laporan Temuan Anomali Data</p>
                <p class="kop-address">
                    Jl. Raya Puputan No. 86, Dangin Puri Klod, Kec. Denpasar Timur, Kota Denpasar, Bali 80234
                    &nbsp;·&nbsp; Telp. (0361) 244445 &nbsp;·&nbsp; pusatdataindonesiabali@gmail.com
                </p>
            </td>
        </tr>
    </table>
    <div class="kop-rule-thick"></div>
    <div class="kop-rule-thin"></div>

    {{-- ═══════════════════════ JUDUL LAPORAN ═══════════════════════ --}}
    <div class="report-title">
        <h1>Laporan Anomali Data</h1>
        <p>Nomor: LAP-ANM/{{ $anomaly->anomalies_id }}/PDIB/{{ now()->format('m/Y') }}</p>
    </div>

    {{-- ═══════════════════════ META SURAT ═══════════════════════ --}}
    <table class="meta-surat">
        <tr>
            <td class="meta-label">Kepada Yth.</td>
            <td class="meta-colon">:</td>
            <td><strong>{{ $data->produsen_display ?: ($data->rujukan?->produsen?->nama_produsen ?? '-') }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Perihal</td>
            <td class="meta-colon">:</td>
            <td>Pemberitahuan Anomali pada Data Statistik yang Diinput</td>
        </tr>
    </table>

    {{-- ═══════════════════════ ISI SURAT ═══════════════════════ --}}
    <div class="isi">
        <p>Dengan hormat,</p>
        <p>
            Sehubungan dengan proses validasi data pada sistem Pusat Data Indonesia Bali (PDIB), sistem kami
            telah mendeteksi adanya indikasi anomali pada salah satu data yang bersumber dari instansi Bapak/Ibu.
            Berikut kami sampaikan rincian temuan sebagai bahan pertimbangan dan tindak lanjut.
        </p>
    </div>

    @php
        $sourceItems = collect($sourceComparison ?? []);
        $avgSourceValue = $sourceItems->count()
            ? $sourceItems->avg(function ($row) {
                if (is_array($row)) {
                    return $row['value'] ?? 0;
                }
                return $row->value ?? 0;
            })
            : null;
    @endphp

    <div class="section-heading">Ringkasan Temuan</div>
    <table class="detail-table">
        <tr>
            <td class="label">Jenis Anomali</td>
            <td class="colon">:</td>
            <td>
                @if($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_SOURCE_CONFLICT)
                    Konflik Sumber Data
                @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNIT_CONFLICT)
                    Konflik Satuan
                @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNREASONABLE)
                    Nilai Tidak Wajar
                @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_EXTREME_INCREASE)
                    Kenaikan Ekstrem
                @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_EXTREME_DECREASE)
                    Penurunan Ekstrem
                @else
                    {{ ucfirst(str_replace('_', ' ', $anomaly->anomaly_type)) }}
                @endif
            </td>
        </tr>
        @if($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_SOURCE_CONFLICT)
            <tr>
                <td class="label">Keterangan</td>
                <td class="colon">:</td>
                <td>Nilai sumber ini tidak konsisten dengan referensi lain untuk lokasi dan periode yang sama.</td>
            </tr>
        @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNIT_CONFLICT)
            <tr>
                <td class="label">Satuan Metadata</td>
                <td class="colon">:</td>
                <td>{{ $data->satuan?->nama_satuan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Satuan Rujukan</td>
                <td class="colon">:</td>
                <td>{{ $data->satuanAsal?->nama_satuan ?? '-' }}</td>
            </tr>
        @else
            <tr>
                <td class="label">Nilai Sebelumnya</td>
                <td class="colon">:</td>
                <td>{{ $anomaly->previous_value !== null ? number_format($anomaly->previous_value, 2, ',', '.') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nilai Saat Ini</td>
                <td class="colon">:</td>
                <td>{{ $anomaly->current_value !== null ? number_format($anomaly->current_value, 2, ',', '.') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Perubahan</td>
                <td class="colon">:</td>
                <td>{{ $anomaly->formatted_percentage_change }}</td>
            </tr>
            <tr>
                <td class="label">Catatan</td>
                <td class="colon">:</td>
                <td>
                    @if($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNREASONABLE)
                        Nilai berada di luar kisaran historis yang diharapkan.
                    @else
                        Perubahan nilai cukup besar dibanding periode sebelumnya.
                    @endif
                </td>
            </tr>
        @endif
    </table>

    @if($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_SOURCE_CONFLICT && $conflictData)

    <div class="section-heading">Detail Data {{ $data->produsen_display ?: '-' }}</div>
        <table class="detail-table">
            <tr>
                <td class="label">Judul Data</td>
                <td class="colon">:</td>
                <td>{{ $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_asal_id ?? $data->satuan_id ?? 0)) : ($data->metadata?->nama ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Satuan</td>
                <td class="colon">:</td>
                <td>{{ $data->satuanAsal?->nama_satuan ?? ($data->satuan?->nama_satuan ?? ($data->metadata?->satuan_data ?? '-')) }}</td>
            </tr>
            <tr>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $data->location?->nama_wilayah ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td class="colon">:</td>
                <td>{{ $data->time
                    ? ($data->time->year
                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                        . ($data->time->quarter  ? '/Q'.$data->time->quarter    : '')
                        . ($data->time->semester ? '/S'.$data->time->semester   : ''))
                    : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Produsen Data</td>
                <td class="colon">:</td>
                <td>{{ $data->produsen_display ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Rujukan</td>
                <td class="colon">:</td>
                <td>{{ $data->rujukan?->nama_rujukan ?? '-' }}</td>
            </tr>
        </table>

        <div class="section-heading">Detail Data {{ $conflictData->produsen_display ?: '-' }}</div>
        <table class="detail-table">
            <tr>
                <td class="label">Judul Data</td>
                <td class="colon">:</td>
                <td>{{ $conflictData->metadata ? $conflictData->metadata->getSubNamaForSatuan((int)($conflictData->satuan_asal_id ?? $conflictData->satuan_id ?? 0)) : ($conflictData->metadata?->nama ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Satuan</td>
                <td class="colon">:</td>
                <td>{{ $conflictData->satuanAsal?->nama_satuan ?? ($conflictData->satuan?->nama_satuan ?? ($conflictData->metadata?->satuan_data ?? '-')) }}</td>
            </tr>
            <tr>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $conflictData->location?->nama_wilayah ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td class="colon">:</td>
                <td>{{ $conflictData->time
                    ? ($conflictData->time->year
                        . ($conflictData->time->month   ? '/Bln-'.$conflictData->time->month   : '')
                        . ($conflictData->time->quarter  ? '/Q'.$conflictData->time->quarter    : '')
                        . ($conflictData->time->semester ? '/S'.$conflictData->time->semester   : ''))
                    : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Produsen Data</td>
                <td class="colon">:</td>
                <td>{{ $conflictData->produsen_display ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Rujukan</td>
                <td class="colon">:</td>
                <td>{{ $conflictData->rujukan?->nama_rujukan ?? '-' }}</td>
            </tr>
        </table>

    @elseif($anomaly->anomaly_type === \App\Models\Anomaly::TYPE_UNIT_CONFLICT)

        <div class="section-heading">Menurut Metadata (Satuan Sistem)</div>
        <table class="detail-table">
            <tr>
                <td class="label">Judul Data</td>
                <td class="colon">:</td>
                <td>{{ $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_id ?? 0)) : ($data->metadata?->nama ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Satuan</td>
                <td class="colon">:</td>
                <td>{{ $data->satuan?->nama_satuan ?? ($data->metadata?->satuan_data ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $data->location?->nama_wilayah ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td class="colon">:</td>
                <td>{{ $data->time
                    ? ($data->time->year
                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                        . ($data->time->quarter  ? '/Q'.$data->time->quarter    : '')
                        . ($data->time->semester ? '/S'.$data->time->semester   : ''))
                    : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Produsen Data</td>
                <td class="colon">:</td>
                <td>{{ $data->produsen_display ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Rujukan</td>
                <td class="colon">:</td>
                <td>{{ $data->rujukan?->nama_rujukan ?? '-' }}</td>
            </tr>
        </table>

        <div class="section-heading">Menurut Sumber Rujukan (Satuan Asli Sumber)</div>
        <table class="detail-table">
            <tr>
                <td class="label">Judul Data</td>
                <td class="colon">:</td>
                <td>{{ $data->metadata ? $data->metadata->getSubNamaForSatuan((int)($data->satuan_asal_id ?? 0)) : ($data->metadata?->nama ?? '-') }}</td>
            </tr>
            <tr>
                <td class="label">Satuan</td>
                <td class="colon">:</td>
                <td>{{ $data->satuanAsal?->nama_satuan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $data->location?->nama_wilayah ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td class="colon">:</td>
                <td>{{ $data->time
                    ? ($data->time->year
                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                        . ($data->time->quarter  ? '/Q'.$data->time->quarter    : '')
                        . ($data->time->semester ? '/S'.$data->time->semester   : ''))
                    : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Produsen Data</td>
                <td class="colon">:</td>
                <td>{{ $data->produsen_display ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Rujukan</td>
                <td class="colon">:</td>
                <td>{{ $data->rujukan?->nama_rujukan ?? '-' }}</td>
            </tr>
        </table>

    @else

        <div class="section-heading">Detail Data</div>
        <table class="detail-table">
            <tr>
                <td class="label">Judul Data</td>
                <td class="colon">:</td>
                <td>{{ $data->metadata?->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Lokasi</td>
                <td class="colon">:</td>
                <td>{{ $data->location?->nama_wilayah ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td class="colon">:</td>
                <td>{{ $data->time
                    ? ($data->time->year
                        . ($data->time->month   ? '/Bln-'.$data->time->month   : '')
                        . ($data->time->quarter  ? '/Q'.$data->time->quarter    : '')
                        . ($data->time->semester ? '/S'.$data->time->semester   : ''))
                    : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Produsen Data</td>
                <td class="colon">:</td>
                <td>{{ $data->produsen_display ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Rujukan</td>
                <td class="colon">:</td>
                <td>{{ $data->rujukan?->nama_rujukan ?? '-' }}</td>
            </tr>
        </table>

    @endif

    @if(in_array($anomaly->anomaly_type, [\App\Models\Anomaly::TYPE_SOURCE_CONFLICT, \App\Models\Anomaly::TYPE_UNIT_CONFLICT])
    && isset($sourceComparison) && $sourceComparison->isNotEmpty())
        @php
            $sourceUnits = collect($sourceComparison)->pluck('satuan_rujukan')->filter()->unique()->values();
            $hasUnitMismatch = $sourceUnits->count() > 1;
            $unitLabel = $sourceUnits->first();
        @endphp
        <div class="section-heading">Perbandingan Antar Sumber Data</div>
        <p style="font-size:9px; margin-bottom:6px; color:#475569;">
            @if($hasUnitMismatch)
                <strong>Perhatian:</strong> terdapat perbedaan satuan rujukan antar sumber: {{ $sourceUnits->join(', ') }}.
            @elseif($unitLabel)
                Semua sumber melaporkan satuan rujukan: {{ $unitLabel }}.
            @endif
        </p>
        <table class="grid-table">
            <thead>
                <tr>
                    <th>Rujukan</th>
                    <th style="text-align:right">Nilai</th>
                    <th style="text-align:right">Satuan Rujukan</th>
                    <th style="text-align:right">Selisih</th>
                    <th style="text-align:right">% Diff</th>
                    <th style="text-align:center">Status Nilai</th>
                    <th style="text-align:center">Status Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sourceComparison as $row)
                    <tr>
                        <td>{{ $row->rujukan ?? $row['rujukan'] ?? '-' }}</td>
                        <td class="num">{{ number_format($row->value ?? $row['value'] ?? 0, 2, ',', '.') }}</td>
                        <td>{{ $row->satuan_rujukan ?? $row['satuan_rujukan'] ?? $row->satuan ?? $row['satuan'] ?? '-' }}</td>
                        <td class="num">{{ number_format($row->selisih ?? $row['selisih'] ?? 0, 2, ',', '.') }}</td>
                        <td class="num">{{ $row->pct_diff ?? $row['pct_diff'] ?? 0 }}%</td>
                        <td class="center">
                            @if($row->conflict ?? $row['conflict'] ?? false)
                                <span class="badge" style="background:#fef9c3;color:#a16207;">Konflik</span>
                            @else
                                <span class="badge" style="background:#dcfce7;color:#15803d;">OK</span>
                            @endif
                        </td>
                        <td class="center">
                            @if($row->unit_conflict ?? $row['unit_conflict'] ?? false)
                                <span class="badge" style="background:#fce7f3;color:#be185d;">Konflik</span>
                            @else
                                <span class="badge" style="background:#dcfce7;color:#15803d;">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="isi">
        <p>
            Kami mohon kesediaan Bapak/Ibu untuk meninjau kembali data yang telah disampaikan, mengingat
            adanya indikasi perubahan nilai yang signifikan di luar pola normal data periode sebelumnya.
            Konfirmasi atau klarifikasi dari pihak Bapak/Ibu akan sangat membantu proses validasi data pada
            sistem kami.
        </p>
        <p>
            Demikian laporan ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
        </p>
    </div>

    {{-- ═══════════════════════ TANDA TANGAN ═══════════════════════ --}}
    <div class="ttd-wrap">
        <table class="ttd-table">
            <tr>
                <td></td>
                <td class="ttd-right">
                    Denpasar, {{ now()->translatedFormat('d F Y') }}
                    <div class="ttd-space"></div>
                    <div class="ttd-name">Kepala Pusat Data Indonesia Bali</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>