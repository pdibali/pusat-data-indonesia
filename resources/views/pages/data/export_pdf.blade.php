<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metadata->nama }}</title>
    <style>
        /* ── Reset & Base ─────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            background: #ffffff;
        }

        /* ── Print setup ─────────────────────────────────── */
        @page {
            size: A4 landscape;
            margin: 15mm 12mm 12mm 12mm;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr    { page-break-inside: avoid; page-break-after: auto; }
        }

        /* ── Print button (hanya tampil di layar) ────────── */
        .no-print {
            position: fixed;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
            z-index: 100;
        }

        .btn-print {
            background: #0284c7;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-print:hover { background: #0369a1; }
        .btn-close {
            background: #6b7280;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-close:hover { background: #4b5563; }

        /* ── Wrapper ─────────────────────────────────────── */
        .page-wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px 24px 32px;
        }

        /* ── Tabel utama ─────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-bottom: 14px;
        }

        /* Row 1: Judul / Title */
        .row-title td {
            background: #FFC000;
            text-align: center;
            font-size: 12pt;
            padding: 8px 10px;
            border: 1px solid #000;
        }

        /* Row 2: Rentang Tahun / Year Range */
        .row-range td {
            background: #FFC000;
            text-align: center;
            font-size: 10.5pt;
            padding: 5px 10px;
            border: 1px solid #000;
        }

        /* Row 3: Header kolom */
        .row-header th {
            background: #FFC000;
            text-align: center;
            font-weight: normal;
            padding: 6px 8px;
            border: 1px solid #000;
            white-space: nowrap;
        }
        .row-header th:first-child {
            text-align: left;
            min-width: 130px;
        }

        /* Baris data */
        .row-data td {
            padding: 5px 8px;
            border: 1px solid #d0d0d0;
            vertical-align: middle;
        }
        .row-data td:not(:first-child) {
            text-align: center;
        }
        .row-data.alt td {
            background: #f9f9f9;
        }

        /* Nilai '-' lebih redup */
        .dash-val {
            color: #9ca3af;
        }

        /* Baris Total */
        .row-total td {
            background: #FFC000;
            padding: 6px 8px;
            border: 1px solid #000;
            font-weight: bold;
        }
        .row-total td:not(:first-child) {
            text-align: center;
        }

        /* Border tebal di sekeliling tabel */
        .data-table {
            border: 2px solid #000;
        }

        /* ── Footer: Sumber / Source ─────────────────────── */
        .footer-source {
            font-size: 9.5pt;
            color: #374151;
            margin-top: 6px;
        }
        .footer-source span {
            font-weight: bold;
        }

        /* ── Metadata info kanan atas ────────────────────── */
        .meta-info {
            font-size: 9pt;
            color: #6b7280;
            text-align: right;
            margin-bottom: 10px;
        }

        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 16px;
            table-layout: fixed;
        }
        .metadata-table th,
        .metadata-table td {
            border: 1px solid #d0d0d0;
            padding: 5px 8px;
            text-align: left;
            vertical-align: top;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }
        .metadata-table th {
            width: 24%;
            background: #f3f4f6;
        }
    </style>
</head>
<body>

{{-- Tombol Print (tidak dicetak) --}}
<div class="no-print">
    <button class="btn-print" onclick="window.print()">
        &#x1F5A8; Cetak / Print
    </button>
    <a class="btn-close" href="javascript:history.back()">✕ Tutup</a>
</div>

<div class="page-wrap">

    <table class="metadata-table">
        <tbody>
            @foreach($metadata_fields as $field)
                <tr>
                    <th>{{ $field['label'] }}</th>
                    <td>{{ $field['value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Info tanggal cetak --}}
    <div class="meta-info">
        Dicetak / Printed: {{ now()->translatedFormat('d F Y, H:i') }} WITA
        &nbsp;|&nbsp; Satuan / Unit: {{ $satuan ?: '-' }}
    </div>

    <table class="data-table">

        {{-- Baris 1: Judul bilingual --}}
        <tr class="row-title">
            <td colspan="{{ 1 + count($years) }}">
                {{ $metadata->nama }} / {{ $metadata->nama }}
            </td>
        </tr>

        {{-- Baris 2: Rentang Tahun bilingual --}}
        <tr class="row-range">
            <td colspan="{{ 1 + count($years) }}">
                Rentang Tahun: {{ $year_range }} / Year Range: {{ $year_range }}
            </td>
        </tr>

        {{-- Baris 3: Header kolom --}}
        <tr class="row-header">
            <th>Kecamatan / District</th>
            @foreach($years as $y)
                <th>{{ $y }}</th>
            @endforeach
        </tr>

        {{-- Baris data kecamatan --}}
        @foreach($districts as $i => $row)
            <tr class="row-data {{ $i % 2 === 1 ? 'alt' : '' }}">
                <td>{{ $row['name'] }}</td>
                @foreach($row['values'] as $v)
                    <td @if($v === '-') class="dash-val" @endif>
                        @if($v === '-')
                            -
                        @else
                            {{ $fmt($v) }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach

        {{-- Baris Total --}}
        <tr class="row-total">
            <td>{{ $total_label }}</td>
            @foreach($totals as $v)
                <td>
                    @if($v === '-')
                        -
                    @else
                        {{ $fmt($v) }}
                    @endif
                </td>
            @endforeach
        </tr>

    </table>

    {{-- Footer Sumber / Source --}}
    <div class="footer-source">
        <span>Sumber / Source:</span>
        {{ $produsen }} / {{ $produsen }}
    </div>

</div>

</body>
</html>