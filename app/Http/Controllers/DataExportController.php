<?php

namespace App\Http\Controllers;

use App\Services\DataExportService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
// use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DataExportController extends Controller
{
    public function __construct(private readonly DataExportService $svc) {}

    // ═══════════════════════════════════════════════════════════
    // EXCEL EXPORT
    // ═══════════════════════════════════════════════════════════
    public function excel(Request $request)
    {
        $request->validate([
            'metadata_id' => 'required|integer|exists:metadata,metadata_id',
            'kabupaten'   => 'nullable|string',
            'year'        => 'nullable|integer',
        ]);

        $payload = $this->svc->build(
            (int) $request->metadata_id,
            $request->kabupaten,
            $request->year ? (int) $request->year : null
        );

        if (!$payload) {
            return back()->with('error', 'Tidak ada data untuk diekspor dengan filter tersebut.');
        }

        $spreadsheet = $this->buildSpreadsheet($payload);
        $writer      = new Xlsx($spreadsheet);
        $filename    = $this->buildFilename($payload, 'xlsx');

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    // ═══════════════════════════════════════════════════════════
    // PDF EXPORT — render Blade view, print / simpan browser
    // ═══════════════════════════════════════════════════════════
    public function pdf(Request $request)
    {
        $request->validate([
            'metadata_id' => 'required|integer|exists:metadata,metadata_id',
            'kabupaten'   => 'nullable|string',
            'year'        => 'nullable|integer',
        ]);

        $payload = $this->svc->build(
            (int) $request->metadata_id,
            $request->kabupaten,
            $request->year ? (int) $request->year : null
        );

        if (!$payload) {
            return back()->with('error', 'Tidak ada data untuk diekspor.');
        }

        // Pass helper formatter ke view
        $payload['fmt'] = fn($v) => DataExportService::formatValue($v);

        return view('pages.data.export_pdf', $payload);
    }

    // ═══════════════════════════════════════════════════════════
    // JSON API EXPORT
    // ═══════════════════════════════════════════════════════════
    public function json(Request $request)
    {
        $request->validate([
            'metadata_id' => 'required|integer|exists:metadata,metadata_id',
            'kabupaten'   => 'nullable|string',
            'year'        => 'nullable|integer',
        ]);

        $payload = $this->svc->build(
            (int) $request->metadata_id,
            $request->kabupaten,
            $request->year ? (int) $request->year : null
        );

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'No data found / Tidak ada data ditemukan.',
            ], 404);
        }

        $meta = $payload['metadata'];

        return response()->json([
            'success' => true,
            'meta' => [
                'generated_at'           => now()->toIso8601String(),
                'generated_at_label'     => 'Dihasilkan pada / Generated at',
                'filter' => [
                    'metadata_id'        => $meta->metadata_id,
                    'kabupaten'          => $payload['kabupaten'],
                    'year'               => $request->year,
                ],
            ],
            'metadata' => [
                'id'                     => $meta->metadata_id,
                'name'                   => $meta->nama,
                'name_label'             => 'Judul / Title',
                'fields'                 => $payload['metadata_fields'],
                'unit'                   => $payload['satuan'],
                'unit_label'             => 'Satuan / Unit',
                'producer'               => $payload['produsen'],
                'producer_label'         => 'Sumber / Source',
                'period_type'            => $payload['period_type'],
                'period_type_label'      => 'Jenis Periode / Period Type',
                'year_range'             => $payload['year_range'],
                'year_range_label'       => 'Rentang Tahun / Year Range',
            ],
            'columns' => [
                'district_label'         => 'Kecamatan / District',
                'periods'                => $payload['years'],
            ],
            'data' => array_map(fn($row) => [
                'district'               => $row['name'],
                'values'                 => array_combine(
                    $payload['years'],
                    array_map(fn($v) => $v === '-' ? null : $v, $row['values'])
                ),
            ], $payload['districts']),
            'total' => [
                'label'                  => $payload['total_label'],
                'label_en'               => 'Total / ' . $payload['total_label'],
                'values'                 => array_combine(
                    $payload['years'],
                    array_map(fn($v) => $v === '-' ? null : $v, $payload['totals'])
                ),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // SPREADSHEET BUILDER
    // ═══════════════════════════════════════════════════════════

    /**
     * Bangun Spreadsheet PhpSpreadsheet mengikuti format file contoh:
     *   Row 1  : kosong
     *   Row 2  : Judul / Title (merge A:lastCol) — background #FFC000
     *   Row 3  : Rentang Tahun / Year Range (merge) — background #FFC000
     *   Row 4  : Header kolom (Kecamatan/District + tahun) — background #FFC000
     *   Row 5+ : Data kecamatan
     *   Last   : Baris total kabupaten — background #FFC000
     *   +1     : Kosong
     *   +2     : Sumber/Source
     */
    private function buildSpreadsheet(array $p): Spreadsheet
    {
        $meta       = $p['metadata'];
        $years      = $p['years'];
        $districts  = $p['districts'];
        $totals     = $p['totals'];
        $nCols      = 1 + count($years);          // A = kecamatan, B+ = tahun
        $lastColIdx = $nCols;                      // 1-based index
        $lastColLtr = $this->colLetter($lastColIdx);

        // Warna persis dari file contoh
        $C_AMBER  = 'FFC000';
        $C_WHITE  = 'FFFFFF';
        $C_BLACK  = '000000';
        $C_BORDER = '000000';

        $spreadsheet = new Spreadsheet();
        $metadataSheet = $spreadsheet->getActiveSheet()->setTitle('Informasi Metadata');
        $metadataSheet->setCellValue('A1', 'Informasi Metadata');
        $metadataSheet->mergeCells('A1:B1');
        $metadataSheet->getStyle('A1:B1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C_AMBER]],
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $metadataSheet->getColumnDimension('A')->setWidth(30);
        $metadataSheet->getColumnDimension('B')->setWidth(100);

        foreach ($p['metadata_fields'] as $index => $field) {
            $row = $index + 2;
            $metadataSheet->setCellValue("A{$row}", $field['label']);
            $metadataSheet->setCellValue("B{$row}", $field['value']);
            $metadataSheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0'],
                ]],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ]);
            $metadataSheet->getStyle("A{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'font' => ['bold' => true],
            ]);
        }

        $ws = $spreadsheet->createSheet()->setTitle('Data');

        // ── Row 2: Judul bilingual ─────────────────────────────
        $title = $meta->nama . ' / ' . $meta->nama;   // ID / EN (nama sama)
        $ws->mergeCells("A2:{$lastColLtr}2");
        $ws->setCellValue('A2', $title);
        $this->styleHeader($ws, "A2:{$lastColLtr}2", $C_AMBER, center: true, bold: false);
        $ws->getRowDimension(2)->setRowHeight(24);

        // ── Row 3: Rentang Tahun / Year Range ─────────────────
        $ws->mergeCells("A3:{$lastColLtr}3");
        $ws->setCellValue('A3', "Rentang Tahun: {$p['year_range']} / Year Range: {$p['year_range']}");
        $this->styleHeader($ws, "A3:{$lastColLtr}3", $C_AMBER, center: true, bold: false);
        $ws->getRowDimension(3)->setRowHeight(18);

        // ── Row 4: Header kolom ────────────────────────────────
        $ws->setCellValue('A4', 'Kecamatan / District');
        foreach ($years as $i => $y) {
            $col = $this->colLetter($i + 2);
            // Untuk kolom tahunan simpan sebagai integer agar Excel tidak anggap teks
            $ws->setCellValueExplicit($col . '4', $y,
                is_numeric($y) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
        }
        $this->styleHeader($ws, "A4:{$lastColLtr}4", $C_AMBER, center: true, bold: false);
        $ws->getRowDimension(4)->setRowHeight(20);

        // Lebar kolom
        $ws->getColumnDimension('A')->setWidth(22);
        foreach ($years as $i => $_) {
            $ws->getColumnDimension($this->colLetter($i + 2))->setWidth(10);
        }

        // ── Row 5+: Data kecamatan ─────────────────────────────
        $dataStartRow = 5;
        foreach ($districts as $ri => $row) {
            $r    = $dataStartRow + $ri;
            $isAlt = $ri % 2 === 1;
            $ws->setCellValue("A{$r}", $row['name']);
            foreach ($row['values'] as $ci => $v) {
                $col  = $this->colLetter($ci + 2);
                $cell = $col . $r;
                if ($v === '-') {
                    $ws->setCellValueExplicit($cell, '-', DataType::TYPE_STRING);
                } else {
                    $ws->setCellValue($cell, (float)$v);
                }
            }
            // Zebra fill ringan (abu-abu sangat terang)
            $fillRgb = $isAlt ? 'F9F9F9' : $C_WHITE;
            $ws->getStyle("A{$r}:{$lastColLtr}{$r}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillRgb]],
                'font'    => ['size' => 11],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                               'color' => ['rgb' => 'D0D0D0']]],
            ]);
            $ws->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $ws->getRowDimension($r)->setRowHeight(17);
        }

        // ── Baris Total ────────────────────────────────────────
        $totalRow = $dataStartRow + count($districts);
        $ws->setCellValue("A{$totalRow}", $p['total_label']);
        foreach ($totals as $ci => $v) {
            $col  = $this->colLetter($ci + 2);
            $cell = $col . $totalRow;
            if ($v === '-') {
                $ws->setCellValueExplicit($cell, '-', DataType::TYPE_STRING);
            } else {
                // Gunakan formula SUM agar konsisten dengan contoh
                $colLtr = $this->colLetter($ci + 2);
                $ws->setCellValue($cell, "=SUM({$colLtr}{$dataStartRow}:{$colLtr}" . ($totalRow - 1) . ")");
            }
        }
        $this->styleHeader($ws, "A{$totalRow}:{$lastColLtr}{$totalRow}", $C_AMBER, center: false, bold: false);
        $ws->getRowDimension($totalRow)->setRowHeight(18);

        // ── Baris Sumber / Source ──────────────────────────────
        $sourceRow = $totalRow + 2;
        $ws->setCellValue("A{$sourceRow}", 'Sumber / Source:');
        $ws->setCellValue("B{$sourceRow}", $p['produsen'] . ' / ' . $p['produsen']);
        $ws->getStyle("A{$sourceRow}:{$lastColLtr}{$sourceRow}")->applyFromArray([
            'font' => ['size' => 10, 'italic' => true],
        ]);
        $ws->mergeCells("B{$sourceRow}:{$lastColLtr}{$sourceRow}");
        $ws->getStyle("B{$sourceRow}")->getAlignment()->setWrapText(true);
        $ws->getRowDimension($sourceRow)->setRowHeight(28);

        // Border seluruh tabel data
        $tableRange = "A4:{$lastColLtr}{$totalRow}";
        $ws->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $C_BORDER]],
            ],
        ]);

        $spreadsheet->setActiveSheetIndex(1);

        return $spreadsheet;
    }

    /**
     * Terapkan style header (background amber + teks).
     */
    private function styleHeader($ws, string $range, string $bg, bool $center, bool $bold): void
    {
        $ws->getStyle($range)->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            'font'      => ['bold' => $bold, 'size' => 11],
            'alignment' => [
                'horizontal' => $center ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                              'color' => ['rgb' => '000000']]],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────
    private function colLetter(int $index): string
    {
        $l = '';
        while ($index > 0) {
            $index--;
            $l     = chr(65 + ($index % 26)) . $l;
            $index = intdiv($index, 26);
        }
        return $l;
    }

    private function buildFilename(array $p, string $ext): string
    {
        $name = str_replace([' ', '/'], '_', $p['metadata']->nama ?? 'data');
        $kab  = str_replace(' ', '_', $p['kabupaten'] ?? '');
        return "Data_{$name}_{$kab}_{$p['year_range']}." . $ext;
    }
}