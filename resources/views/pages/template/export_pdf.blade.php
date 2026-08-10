<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export PDF — {{ $tampilan->nama_tampilan }}</title>
    <style>
        /* ═══════════════════════════════════════════════
           RESET & BASE
        ═══════════════════════════════════════════════ */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            background: #fff;
            padding: 20px;
        }

        /* ═══════════════════════════════════════════════
        WATERMARK
        ═══════════════════════════════════════════════ */
        body::before {
            content: "Pusat Data Indonesia Bali";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 46px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.06);
            white-space: nowrap;
            pointer-events: none;
            z-index: 9999;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        @media print {
            body::before {
                color: rgba(0, 0, 0, 0.08); /* sedikit lebih gelap saat print agar tetap terlihat */
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* ═══════════════════════════════════════════════
           PRINT HEADER (tampil hanya di layar, hilang saat cetak)
        ═══════════════════════════════════════════════ */
        .screen-bar {
            background: #0ea5e9;
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .screen-bar h1 { font-size: 14px; font-weight: 600; }
        .screen-bar .meta { font-size: 11px; opacity: 0.85; margin-top: 2px; }
        .screen-bar .actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn-print {
            background: #fff;
            color: #0ea5e9;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }

        /* ═══════════════════════════════════════════════
           TABEL PIVOT
        ═══════════════════════════════════════════════ */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: max-content;
            min-width: 100%;
            font-size: 11px;
        }

        /* Header */
        thead th {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: center;
            vertical-align: middle;
            font-weight: normal;
            white-space: nowrap;
            background: #fff;
        }

        /* Data cells */
        tbody td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: middle;
            font-size: 11px;
        }

        /* Kolom Nama Metadata — center + wrap */
        td.col-nama {
            text-align: center;
            vertical-align: middle;
            max-width: 200px;
            white-space: normal;
            word-wrap: break-word;
        }

        /* Kolom Wilayah — indent sesuai level */
        td.col-wilayah { text-align: left; white-space: nowrap; }
        td.col-wilayah.level-0 { font-weight: 600; }
        td.col-wilayah.level-1 { padding-left: 16px; }
        td.col-wilayah.level-2 { padding-left: 28px; color: #555; }
        td.col-wilayah.level-3 { padding-left: 40px; color: #777; font-size: 10px; }

        /* Kolom nilai periode — right align */
        td.col-val { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

        /* Kolom Satuan & Sumber */
        td.col-satuan { text-align: center; white-space: normal; word-wrap: break-word; max-width: 80px; }
        td.col-sumber { text-align: left;   white-space: normal; word-wrap: break-word; max-width: 160px; font-size: 10px; color: #444; font-style: italic; }

        /* Zebra ringan */
        tbody tr:nth-child(even) td { background: #f8f8f8; }
        tbody tr:hover td { background: #eef6ff; }

        /* Info bar */
        .info-bar {
            margin-bottom: 10px;
            padding: 6px 10px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            font-size: 11px;
            color: #0369a1;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .info-bar span { display: flex; align-items: center; gap: 4px; }

        /* ═══════════════════════════════════════════════
           PRINT STYLES
        ═══════════════════════════════════════════════ */
        @media print {
            body { padding: 8px; font-size: 10px; }
            .screen-bar { display: none !important; }
            .info-bar { border: none; background: none; padding: 4px 0; margin-bottom: 6px; }
            table { font-size: 9px; }
            thead th { font-size: 9px; padding: 3px 5px; }
            tbody td { font-size: 9px; padding: 3px 5px; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }

            @page {
                size: A4 landscape;
                margin: 10mm 8mm;
            }
        }
    </style>
</head>
<body>

    {{-- ══ SCREEN TOOLBAR ══ --}}
    <div class="screen-bar">
        <div>
            <div class="h1">{{ $tampilan->nama_tampilan }}</div>
            <div class="meta">
                Frekuensi: {{ ucfirst($frekuensi) }}
                @if($year_from || $year_to)
                    · Periode: {{ $year_from }} – {{ $year_to }}
                @endif
                · Diekspor: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="actions">
            <a href="javascript:history.back()" class="btn-back">← Kembali</a>
            <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
        </div>
    </div>

    {{-- ══ INFO BAR ══ --}}
    <div class="info-bar">
        <span>📋 <strong>Template:</strong> {{ $tampilan->nama_tampilan }}</span>
        <span>⏱️ <strong>Frekuensi:</strong> {{ $periodeLabel ?? ucfirst($frekuensi) }}</span>
        @if($year_from || $year_to)
            <span>📅 <strong>Rentang:</strong> {{ $year_from }} – {{ $year_to }}</span>
        @endif
        <span>📊 <strong>Kolom periode:</strong> {{ count($columns) }}</span>
    </div>

    {{-- ══ TABEL PIVOT ══ --}}
    <div class="table-wrap">
        <table>
            <thead>
                {{-- Baris 1: nama kolom utama --}}
                <tr>
                    <th rowspan="2" style="min-width:160px; max-width:200px;">Nama Metadata</th>
                    <th rowspan="2" style="min-width:140px;">Wilayah</th>
                    <th colspan="{{ count($columns) }}">
                        Periode ({{ $periodeLabel ?? ucfirst($frekuensi) }})
                    </th>
                    <th rowspan="2" style="min-width:70px;">Satuan</th>
                    <th rowspan="2" style="min-width:140px;">Sumber</th>
                </tr>
                {{-- Baris 2: label setiap kolom periode --}}
                <tr>
                    @foreach($columns as $col)
                        <th style="min-width:70px; font-size:10px;">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($grouped as $meta)
                    @php $rowCount = count($meta['rows']); @endphp
                    @foreach($meta['rows'] as $ri => $row)
                        <tr>
                            {{-- Kolom A: Nama Metadata — rowspan --}}
                            @if($ri === 0)
                                <td class="col-nama" rowspan="{{ $rowCount }}">
                                    {{ $meta['nama'] }}
                                </td>
                            @endif

                            {{-- Kolom B: Wilayah --}}
                            <td class="col-wilayah level-{{ $row['level'] }}">
                                {{ $row['lokasi'] }}
                            </td>

                            {{-- Kolom Periode: nilai data --}}
                            @foreach($columns as $col)
                                @php
                                    $val = $row['values'][$col['label']] ?? null;
                                    $fmt = ($val !== null && $val !== '')
                                        ? number_format((float)$val, 2, ',', '.')
                                        : '—';
                                    // Jika bilangan bulat, hilangkan desimal
                                    if ($val !== null && $val !== '' && (float)$val == (int)$val) {
                                        $fmt = number_format((int)$val, 0, ',', '.');
                                    }
                                @endphp
                                <td class="col-val">{{ $fmt }}</td>
                            @endforeach

                            {{-- Kolom Satuan — rowspan --}}
                            @if($ri === 0)
                                <td class="col-satuan" rowspan="{{ $rowCount }}">{{ $meta['satuan'] }}</td>
                            @endif

                            {{-- Kolom Sumber — rowspan --}}
                            @if($ri === 0)
                                <td class="col-sumber" rowspan="{{ $rowCount }}">{{ $meta['sumber'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="page-break-before: always;"></div>
    <h2 style="margin-top:50px; margin-bottom:20px;">Informasi Data</h2>

    @foreach($grouped as $meta)
        @php($m = $meta['model'] ?? null)
        <table style="width:100%; border-collapse:collapse; margin-bottom:16px; font-size:11px;">
            <tr>
                <td colspan="2" style="background:#FFC000; border:1px solid #000; padding:6px; font-weight:bold;">
                    {{ $loop->iteration }}. {{ $meta['nama'] }}
                </td>
            </tr>
            <tr><td style="width:180px; background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Alias</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->alias ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Klasifikasi</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->klasifikasi?->nama_klasifikasi ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Konsep</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->konsep ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Definisi</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->definisi ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Metodologi</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->metodologi ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Penjelasan Metodologi</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->penjelasan_metodologi ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Asumsi</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->asumsi ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Tipe Data</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->tipe_data ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Satuan Data</td><td style="border:1px solid #ccc; padding:6px;">{{ $meta['satuan'] ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Frekuensi Penerbitan</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->frekuensi_penerbitan ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Tahun Mulai Data</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->tahun_mulai_data ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Tahun Data Tersedia</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->tahun_data_tersedia ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Produsen Data</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->produsen?->nama_produsen ?: '-' }}</td></tr>
            <tr><td style="background:#f2f2f2; border:1px solid #ccc; padding:6px; font-weight:bold;">Tag</td><td style="border:1px solid #ccc; padding:6px;">{{ $m?->tag ?: '-' }}</td></tr>
        </table>
    @endforeach

    <p style="margin-top:12px; font-size:10px; color:#888; text-align:right;">
        Diekspor dari sistem pada {{ now()->format('d F Y, H:i:s') }}
    </p>
</body>
</html>