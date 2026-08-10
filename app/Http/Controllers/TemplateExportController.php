<?php

namespace App\Http\Controllers;

use App\Models\Tampilan;
use App\Models\IsiTampilan;
use App\Models\Metadata;
use App\Models\Location;
use App\Models\Data;
use App\Models\Transaksi;
use App\Models\Waktu;
use App\Services\SubscriptionAccessService;
use App\Services\SubscriptionLimitsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * TemplateExportController
 *
 * Mengekspor data pivot dari panel Tampilkan Data (template-panel.blade.php)
 * ke format Excel, PDF, dan JSON.
 *
 * Format Excel mengikuti referensi Book1.xlsx:
 *   Row 1: Header 2 baris — Nama Metadata (merge row 1-2) | Wilayah (merge) |
 *           Periode ... [colspan kolom] | Satuan (merge) | Sumber (merge)
 *   Row 2: Label per kolom periode
 *   Row 3+: Data per metadata × wilayah
 *           → Kolom A di-merge untuk semua baris wilayah dari metadata yang sama
 *
 * Route (tambahkan di routes/web.php):
 *   Route::post('/template-export/excel', [TemplateExportController::class, 'excel'])->name('template.export.excel');
 *   Route::post('/template-export/pdf',   [TemplateExportController::class, 'pdf'])->name('template.export.pdf');
 *   Route::post('/template-export/json',  [TemplateExportController::class, 'json'])->name('template.export.json');
 */
class TemplateExportController extends Controller
{
    // ══════════════════════════════════════════════════════════
    // ENTRY POINTS
    // ══════════════════════════════════════════════════════════

    /**
     * Export ke Excel (.xlsx)
     * POST body sama persis dengan fetchTableData:
     *   tampilan_id, frekuensi, year_from, year_to, period_from, period_to
     */
    public function excel(Request $request)
    {
        $this->checkAccess();
        $payload = $this->buildPayload($request);

        if (!$payload['success']) {
            return response()->json(['success' => false, 'message' => $payload['message']], 422);
        }

        $spreadsheet = $this->buildSpreadsheet($payload);
        $this->addInformasiDataSheet($spreadsheet, $payload);   // ← tambahan
        $spreadsheet->setActiveSheetIndex(0);                    // tab "Data" tetap yang terbuka duluan

        $writer      = new Xlsx($spreadsheet);
        $filename    = $this->makeFilename($payload, 'xlsx');

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
    private function checkAccess(): void
    {
        if (!Auth::check()) {
            abort(403, 'Akses ditolak. Silakan login dan berlangganan.');
        }

        $user = Auth::user();
        if ((int) $user->group_id === 3) {
            $hasAccess = app(SubscriptionAccessService::class)->hasActiveAccess($user);

            if (!$hasAccess) {
                abort(403, 'Langganan Anda tidak aktif.');
            }
        }
    }

    /**
     * Export ke PDF — render Blade view lalu cetak via browser / wkhtmltopdf
     */
    public function pdf(Request $request)
    {
        $this->checkAccess();
        $payload = $this->buildPayload($request);

        if (!$payload['success']) {
            return back()->with('error', $payload['message']);
        }

        return view('pages.template.export_pdf', $payload);
    }

    /**
     * Export ke JSON
     */
    public function json(Request $request)
    {
        $this->checkAccess();
        $payload = $this->buildPayload($request);

        if (!$payload['success']) {
            return response()->json(['success' => false, 'message' => $payload['message']], 422, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return response()->json(
            $this->buildJsonResponse($payload),
            200,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    // ══════════════════════════════════════════════════════════
    // CORE — Build Payload
    // ══════════════════════════════════════════════════════════

    /**
     * Bangun payload data pivot dari request, mirip fetchTableData di TemplateController
     * tapi tanpa pagination (ambil semua data untuk export).
     */
    private function buildPayload(Request $request): array
    {
        $request->validate([
            'tampilan_id' => 'required|integer|exists:tampilan,tampilan_id',
            'frekuensi'   => 'required|string|in:10tahunan,5tahunan,tahunan,semesteran,kuartal,bulanan',
            'year_from'   => 'nullable|integer|min:1900|max:2100',
            'year_to'     => 'nullable|integer|min:1900|max:2100',
            'period_from' => 'nullable|integer',
            'period_to'   => 'nullable|integer',
        ]);

        // ── 1. Load template ───────────────────────────────────
        $tampilanQuery = Tampilan::where('tampilan_id', $request->tampilan_id);
        if (Auth::check()) {
            $tampilanQuery->where('user_id', Auth::id());
        }
        $tampilan = $tampilanQuery->firstOrFail();

        $limitsService = app(SubscriptionLimitsService::class);
        if ($limitsService->isTemplateLocked($tampilan)) {
            return ['success' => false, 'message' => 'Template ini terkunci karena melebihi kuota paket Anda. Silakan berlangganan untuk membuka kembali.'];
        }

        // ── 2. Isi tampilan (metadata + location_ids) ──────────
        $isiList = IsiTampilan::where('tampilan_id', $tampilan->tampilan_id)->get();
        if ($isiList->isEmpty()) {
            return ['success' => false, 'message' => 'Tidak ada metadata dalam template ini.'];
        }

        $metaLocationMap = [];
        foreach ($isiList as $isi) {
            $metaLocationMap[$isi->metadata_id] = $isi->location_ids
                ? array_map('intval', $isi->location_ids)
                : [];
        }
        $metadataIds = array_keys($metaLocationMap);

        // ── 3. Metadata aktif ──────────────────────────────────
        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', 2)
            ->orderBy('nama')
            ->with(['klasifikasi', 'produsen'])   // ← load relasi penuh, bukan select kolom terbatas lagi
            ->get();

        if ($metadataList->isEmpty()) {
            return ['success' => false, 'message' => 'Tidak ada metadata aktif.'];
        }

        // ── 4. Kolom waktu ─────────────────────────────────────
        $columns    = $this->buildColumns($request->frekuensi, $request);
        $timeIdMap  = $this->resolveTimeIds($columns, $request->frekuensi);
        $allTimeIds = array_values(array_filter(array_column($timeIdMap, 'time_id')));

        if (empty($columns)) {
            return ['success' => false, 'message' => 'Tidak ada kolom periode pada rentang yang dipilih.'];
        }

        // ── 5. Ambil semua data sekaligus ──────────────────────
        $allLocationIds = array_unique(array_merge(...array_values($metaLocationMap)));
        $allLocationIds = array_values(array_filter($allLocationIds));

        $locationMap = Location::pluck('nama_wilayah', 'location_id');

        $dataQuery = Data::with(['rujukan:rujukan_id,nama_rujukan'])
            ->whereIn('metadata_id', $metadataIds)
            ->where('status', Data::STATUS_AVAILABLE);

        if (!empty($allTimeIds)) {
            $dataQuery->whereIn('time_id', $allTimeIds);
        }
        if (!empty($allLocationIds)) {
            $dataQuery->whereIn('location_id', $allLocationIds);
        }

        $allData = $dataQuery->get(['id', 'metadata_id', 'location_id', 'time_id', 'number_value', 'rujukan_id']);

        $dataIndex    = [];
        $rujukanIndex = [];
        foreach ($allData as $d) {
            $dataIndex[$d->metadata_id][$d->location_id][$d->time_id] = $d->number_value;
            if ($d->rujukan) {
                $rujukanIndex[$d->metadata_id][$d->location_id][$d->time_id] = $d->rujukan->nama_rujukan;
            }
        }

        // ── 6. Bangun baris grouped (per metadata → [lokasi, ...]) ──
        $grouped = []; // [ metadata_id => ['nama'=>, 'satuan'=>, 'sumber'=>, 'rows'=>[ ['lokasi'=>, 'values'=>] ]] ]

        foreach ($metadataList as $m) {
            $mId    = $m->metadata_id;
            $locIds = $metaLocationMap[$mId] ?? [];

            $locsToShow = empty($locIds) ? [null] : $locIds;

            $metaRows = [];
            foreach ($locsToShow as $locId) {
                $locNama = $locId ? ($locationMap[$locId] ?? 'Tidak diketahui') : 'Semua Wilayah';
                $level   = $locId ? $this->detectLokasiLevel((string) $locId) : 0;

                $values = [];
                foreach ($columns as $col) {
                    $timeId = $timeIdMap[$col['label']]['time_id'] ?? null;
                    if ($timeId === null) {
                        $values[$col['label']] = null;
                    } elseif ($locId !== null) {
                        $values[$col['label']] = $dataIndex[$mId][$locId][$timeId] ?? null;
                    } else {
                        $values[$col['label']] = isset($dataIndex[$mId])
                            ? collect($dataIndex[$mId])
                                ->map(fn ($times) => $times[$timeId] ?? null)
                                ->filter(fn ($v) => $v !== null)
                                ->first()
                            : null;
                    }
                }

                $rujukan = '-';
                if ($locId && isset($rujukanIndex[$mId][$locId])) {
                    $rujukan = collect($rujukanIndex[$mId][$locId])->filter()->first() ?? '-';
                } elseif ($m->rujukan) {
                    $rujukan = $m->rujukan->nama_rujukan ?? '-';
                }

                $metaRows[] = [
                    'lokasi'  => $locNama,
                    'level'   => $level,
                    'loc_id'  => $locId,
                    'values'  => $values,
                    'rujukan' => $rujukan,
                ];
            }

            $grouped[$mId] = [
                'metadata_id' => $mId,
                'nama'        => $m->nama,
                'satuan'      => $m->satuan_data ?? '-',
                'sumber'      => collect($metaRows)->pluck('rujukan')->filter(fn ($r) => $r !== '-')->first() ?? '-',
                'rows'        => $metaRows,
                'model'       => $m,
            ];
        }

        return [
            'success'      => true,
            'tampilan'     => $tampilan,
            'columns'      => $columns,
            'grouped'      => $grouped,
            'frekuensi'    => $request->frekuensi,
            'year_from'    => $request->year_from,
            'year_to'      => $request->year_to,
            'period_from'  => $request->period_from,
            'period_to'    => $request->period_to,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // EXCEL BUILDER
    // ══════════════════════════════════════════════════════════

    private function buildSpreadsheet(array $p): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $ws          = $spreadsheet->getActiveSheet()->setTitle('Data');

        $columns = $p['columns'];   // [['label'=>, 'meta'=>], ...]
        $grouped = $p['grouped'];   // keyed by metadata_id

        $nPeriodCols = count($columns);
        // Kolom: A=Nama Metadata, B=Wilayah, C..X=Periode, X+1=Satuan, X+2=Sumber
        $colWilayah  = 'B';
        $colPeriodStart = 3;                               // 1-based: C
        $colPeriodEnd   = 2 + $nPeriodCols;               // 1-based
        $colSatuan      = $colPeriodEnd + 1;
        $colSumber      = $colPeriodEnd + 2;
        $totalCols      = $colSumber;

        $colLetterStart  = $this->colLetter($colPeriodStart);
        $colLetterEnd    = $this->colLetter($colPeriodEnd);
        $colLetterSatuan = $this->colLetter($colSatuan);
        $colLetterSumber = $this->colLetter($colSumber);

        // ── BARIS 1: Header atas ─────────────────────────────────
        // Merge A1:A2 — "Nama Metadata"
        $ws->mergeCells("A1:A2");
        $ws->setCellValue('A1', 'Nama Metadata');
        $this->applyHeaderStyle($ws, 'A1:A2');
        $ws->getColumnDimension('A')->setWidth(36);

        // Merge B1:B2 — "Wilayah"
        $ws->mergeCells('B1:B2');
        $ws->setCellValue('B1', 'Wilayah');
        $this->applyHeaderStyle($ws, 'B1:B2');
        $ws->getColumnDimension('B')->setWidth(24);

        // Merge C1:X1 — "Periode ..."
        $periodeLabel = $this->periodeLabel($p['frekuensi']);
        if ($nPeriodCols > 1) {
            $ws->mergeCells("{$colLetterStart}1:{$colLetterEnd}1");
        }
        $ws->setCellValue("{$colLetterStart}1", "Periode ({$periodeLabel})");
        $this->applyHeaderStyle($ws, "{$colLetterStart}1:{$colLetterEnd}1");

        // Merge Satuan1:Satuan2
        $ws->mergeCells("{$colLetterSatuan}1:{$colLetterSatuan}2");
        $ws->setCellValue("{$colLetterSatuan}1", 'Satuan');
        $this->applyHeaderStyle($ws, "{$colLetterSatuan}1:{$colLetterSatuan}2");
        $ws->getColumnDimension($colLetterSatuan)->setWidth(12);

        // Merge Sumber1:Sumber2
        $ws->mergeCells("{$colLetterSumber}1:{$colLetterSumber}2");
        $ws->setCellValue("{$colLetterSumber}1", 'Sumber');
        $this->applyHeaderStyle($ws, "{$colLetterSumber}1:{$colLetterSumber}2");
        $ws->getColumnDimension($colLetterSumber)->setWidth(28);

        $ws->getRowDimension(1)->setRowHeight(22);

        // ── BARIS 2: Label kolom periode ──────────────────────────
        foreach ($columns as $i => $col) {
            $colLetter = $this->colLetter($colPeriodStart + $i);
            $label     = $col['label'];
            // Untuk label numerik murni (tahun/dekade), simpan sebagai angka
            if (is_numeric($label) && !str_contains($label, '/')) {
                $ws->setCellValueExplicit($colLetter . '2', (int)$label, DataType::TYPE_NUMERIC);
            } else {
                $ws->setCellValue($colLetter . '2', $label);
            }
            $ws->getStyle($colLetter . '2')->applyFromArray([
                'font'      => ['bold' => false, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            $ws->getColumnDimension($colLetter)->setWidth(max(10, strlen($label) + 2));
        }
        $ws->getRowDimension(2)->setRowHeight(18);

        // ── BARIS DATA (3+) ───────────────────────────────────────
        $currentRow = 3;

        foreach ($grouped as $meta) {
            $rowCount = count($meta['rows']);
            $startRow = $currentRow;
            $endRow   = $currentRow + $rowCount - 1;

            foreach ($meta['rows'] as $ri => $row) {
                $r = $currentRow;

                // Kolom A — Nama Metadata (hanya pada baris pertama, di-merge)
                if ($ri === 0) {
                    if ($rowCount > 1) {
                        $ws->mergeCells("A{$startRow}:A{$endRow}");
                    }
                    $ws->setCellValue("A{$startRow}", $meta['nama']);
                    $ws->getStyle("A{$startRow}")->applyFromArray([
                        'font'      => ['size' => 11],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true,
                        ],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                    ]);
                }

                // Kolom B — Wilayah
                $indent = $this->indentPrefix($row['level']);
                $ws->setCellValue("B{$r}", $indent . $row['lokasi']);
                $ws->getStyle("B{$r}")->applyFromArray([
                    'font'      => ['size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                // Kolom Periode C..X — nilai data
                foreach ($columns as $i => $col) {
                    $colLetter = $this->colLetter($colPeriodStart + $i);
                    $val       = $row['values'][$col['label']] ?? null;

                    if ($val !== null && $val !== '') {
                        $ws->setCellValue("{$colLetter}{$r}", (float) $val);
                        $ws->getStyle("{$colLetter}{$r}")->getNumberFormat()
                            ->setFormatCode('#,##0.##');
                    } else {
                        $ws->setCellValue("{$colLetter}{$r}", '-');
                    }

                    $ws->getStyle("{$colLetter}{$r}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                    ]);
                }

                // Kolom Satuan — merge untuk semua baris wilayah metadata ini
                if ($ri === 0) {
                    if ($rowCount > 1) {
                        $ws->mergeCells("{$colLetterSatuan}{$startRow}:{$colLetterSatuan}{$endRow}");
                    }
                    $ws->setCellValue("{$colLetterSatuan}{$startRow}", $meta['satuan']);
                    $ws->getStyle("{$colLetterSatuan}{$startRow}")->applyFromArray([
                        'font'      => ['size' => 11],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                    ]);

                    // Kolom Sumber — merge
                    if ($rowCount > 1) {
                        $ws->mergeCells("{$colLetterSumber}{$startRow}:{$colLetterSumber}{$endRow}");
                    }
                    $ws->setCellValue("{$colLetterSumber}{$startRow}", $meta['sumber']);
                    $ws->getStyle("{$colLetterSumber}{$startRow}")->applyFromArray([
                        'font'      => ['size' => 10, 'italic' => true],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                    ]);
                }

                $ws->getRowDimension($r)->setRowHeight(18);
                $currentRow++;
            }
        }

        // ── Freeze baris 1-2 ──────────────────────────────────────
        $ws->freezePane('C3');

        return $spreadsheet;
    }

    // ══════════════════════════════════════════════════════════
    // SHEET 2 — INFORMASI DATA (penjelasan metadata untuk awam)
    // ══════════════════════════════════════════════════════════
    private function addInformasiDataSheet(Spreadsheet $spreadsheet, array $p): void
    {
        $ws = $spreadsheet->createSheet();
        $ws->setTitle('Informasi Data');

        $C_HEADER_BG  = '1F4E78';
        $C_SECTION_BG = 'FFC000'; // konsisten dengan warna sheet Data
        $C_LABEL_BG   = 'F2F2F2';

        $ws->getColumnDimension('A')->setWidth(28);
        $ws->getColumnDimension('B')->setWidth(90);

        // ── Judul sheet ─────────────────────────────────────
        $ws->mergeCells('A1:B1');
        $ws->setCellValue('A1', 'INFORMASI DATA');
        $ws->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C_HEADER_BG]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(26);

        $r = 3;
        $no = 1;

        foreach ($p['grouped'] as $meta) {
            /** @var Metadata|null $m */
            $m = $meta['model'] ?? null;

            // ── Header section per metadata ──────────────────
            $ws->mergeCells("A{$r}:B{$r}");
            $ws->setCellValue("A{$r}", "{$no}. {$meta['nama']}");
            $ws->getStyle("A{$r}")->applyFromArray([
                'font'    => ['bold' => true, 'size' => 11],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C_SECTION_BG]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            $ws->getRowDimension($r)->setRowHeight(22);
            $r++;

            // Field label => [value, tinggi baris]
            $fields = [
                'Alias'                  => [$m?->alias, 18],
                'Klasifikasi'            => [$m?->klasifikasi?->nama_klasifikasi, 18],
                'Konsep'                 => [$m?->konsep, 55],
                'Definisi'               => [$m?->definisi, 55],
                'Metodologi'             => [$m?->metodologi, 18],
                'Penjelasan Metodologi'  => [$m?->penjelasan_metodologi, 55],
                'Asumsi'                 => [$m?->asumsi, 40],
                'Tipe Data'              => [$m?->tipe_data, 18],
                'Satuan Data'            => [$meta['satuan'], 18],
                'Frekuensi Penerbitan'   => [$m?->frekuensi_penerbitan, 18],
                'Tahun Mulai Data'       => [$m?->tahun_mulai_data, 18],
                'Tahun Data Tersedia'    => [$m?->tahun_data_tersedia, 18],
                'Produsen Data'          => [$m?->produsen?->nama_produsen, 18],
                'Tag'                    => [$m?->tag, 18],
            ];

            foreach ($fields as $label => [$value, $height]) {
                $value = ($value === null || trim((string) $value) === '') ? '-' : $value;

                $ws->setCellValue("A{$r}", $label);
                $ws->setCellValue("B{$r}", $value);

                $ws->getStyle("A{$r}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C_LABEL_BG]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']]],
                ]);
                $ws->getStyle("B{$r}")->applyFromArray([
                    'font'      => ['size' => 10],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']]],
                ]);
                $ws->getRowDimension($r)->setRowHeight($height);
                $r++;
            }

            // Spacer antar metadata
            $r++;
            $no++;
        }

        $ws->freezePane('A3');
    }

    // ── Helper: terapkan style header ────────────────────────────
    private function applyHeaderStyle($ws, string $range): void
    {
        $ws->getStyle($range)->applyFromArray([
            'font'      => ['bold' => false, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
    }

    // ── Helper: prefix indent untuk wilayah ──────────────────────
    private function indentPrefix(int $level): string
    {
        return str_repeat('  ', $level);
    }

    // ══════════════════════════════════════════════════════════
    // JSON BUILDER
    // ══════════════════════════════════════════════════════════

    private function buildJsonResponse(array $p): array
    {
        $rows = [];

        foreach ($p['grouped'] as $meta) {
            foreach ($meta['rows'] as $row) {
                $rows[] = [
                    'nama_metadata' => $meta['nama'],
                    'satuan'        => $meta['satuan'],
                    'sumber'        => $meta['sumber'],
                    'wilayah'       => $row['lokasi'],
                    'level_wilayah' => $row['level'],
                    'values'        => array_map(
                        fn ($v) => $v !== null ? (float) $v : null,
                        $row['values']
                    ),
                ];
            }
        }

        $informasiData = [];
        foreach ($p['grouped'] as $meta) {
            $m = $meta['model'] ?? null;
            $informasiData[] = [
                'nama_metadata'          => $meta['nama'],
                'alias'                  => $m?->alias,
                'klasifikasi'            => $m?->klasifikasi?->nama_klasifikasi,
                'konsep'                 => $m?->konsep,
                'definisi'               => $m?->definisi,
                'metodologi'             => $m?->metodologi,
                'penjelasan_metodologi'  => $m?->penjelasan_metodologi,
                'asumsi'                 => $m?->asumsi,
                'tipe_data'              => $m?->tipe_data,
                'satuan_data'            => $meta['satuan'],
                'frekuensi_penerbitan'   => $m?->frekuensi_penerbitan,
                'tahun_mulai_data'       => $m?->tahun_mulai_data,
                'tahun_data_tersedia'    => $m?->tahun_data_tersedia,
                'produsen_data'          => $m?->produsen?->nama_produsen,
                'tag'                    => $m?->tag,
            ];
        }

        return [
            'success'   => true,
            'generated_at' => now()->toIso8601String(),
            'template'  => [
                'id'   => $p['tampilan']->tampilan_id,
                'nama' => $p['tampilan']->nama_tampilan,
            ],
            'filter'    => [
                'frekuensi'   => $p['frekuensi'],
                'year_from'   => $p['year_from'],
                'year_to'     => $p['year_to'],
                'period_from' => $p['period_from'],
                'period_to'   => $p['period_to'],
            ],
            'columns'   => array_column($p['columns'], 'label'),
            'data'      => $rows,
            'informasi_data' => $informasiData,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS — sama dengan TemplateController
    // ══════════════════════════════════════════════════════════

    private function buildColumns(string $frekuensi, Request $request): array
    {
        $columns = [];

        switch ($frekuensi) {
            case '10tahunan':
                $from = (int) floor(($request->period_from ?? $request->year_from ?? 2010) / 10) * 10;
                $to   = (int) floor(($request->period_to   ?? $request->year_to   ?? 2020) / 10) * 10;
                for ($d = $from; $d <= $to; $d += 10) {
                    $columns[] = ['label' => (string) $d, 'meta' => ['decade' => $d]];
                }
                break;

            case '5tahunan':
                $from = (int) floor(($request->period_from ?? $request->year_from ?? 2010) / 5) * 5;
                $to   = (int) floor(($request->period_to   ?? $request->year_to   ?? 2020) / 5) * 5;
                for ($d = $from; $d <= $to; $d += 5) {
                    $columns[] = ['label' => (string) $d, 'meta' => ['year_5' => $d]];
                }
                break;

            case 'tahunan':
                $from = (int) ($request->period_from ?? $request->year_from ?? 2020);
                $to   = (int) ($request->period_to   ?? $request->year_to   ?? 2021);
                for ($y = $from; $y <= $to; $y++) {
                    $columns[] = ['label' => (string) $y, 'meta' => ['year' => $y]];
                }
                break;

            case 'semesteran':
                $yFrom = (int) ($request->year_from   ?? 2021);
                $yTo   = (int) ($request->year_to     ?? 2022);
                $pFrom = (int) ($request->period_from ?? 1);
                $pTo   = (int) ($request->period_to   ?? 2);
                for ($y = $yFrom; $y <= $yTo; $y++) {
                    $sStart = ($y === $yFrom) ? $pFrom : 1;
                    $sEnd   = ($y === $yTo)   ? $pTo   : 2;
                    for ($s = $sStart; $s <= $sEnd; $s++) {
                        $columns[] = ['label' => "S{$s}/{$y}", 'meta' => ['year' => $y, 'semester' => $s]];
                    }
                }
                break;

            case 'kuartal':
                $yFrom = (int) ($request->year_from   ?? 2021);
                $yTo   = (int) ($request->year_to     ?? 2022);
                $pFrom = (int) ($request->period_from ?? 1);
                $pTo   = (int) ($request->period_to   ?? 4);
                for ($y = $yFrom; $y <= $yTo; $y++) {
                    $qStart = ($y === $yFrom) ? $pFrom : 1;
                    $qEnd   = ($y === $yTo)   ? $pTo   : 4;
                    for ($q = $qStart; $q <= $qEnd; $q++) {
                        $columns[] = ['label' => "Q{$q}/{$y}", 'meta' => ['year' => $y, 'quarter' => $q]];
                    }
                }
                break;

            case 'bulanan':
                $yFrom = (int) ($request->year_from   ?? 2021);
                $yTo   = (int) ($request->year_to     ?? 2022);
                $pFrom = (int) ($request->period_from ?? 1);
                $pTo   = (int) ($request->period_to   ?? 12);
                for ($y = $yFrom; $y <= $yTo; $y++) {
                    $mStart = ($y === $yFrom) ? $pFrom : 1;
                    $mEnd   = ($y === $yTo)   ? $pTo   : 12;
                    for ($mo = $mStart; $mo <= $mEnd; $mo++) {
                        $columns[] = ['label' => $this->namaBulan($mo) . "/{$y}", 'meta' => ['year' => $y, 'month' => $mo]];
                    }
                }
                break;
        }

        return $columns;
    }

    private function resolveTimeIds(array $columns, string $frekuensi): array
    {
        $result = [];

        foreach ($columns as $col) {
            $meta  = $col['meta'];
            $query = \Illuminate\Support\Facades\DB::table('time');

            switch ($frekuensi) {
                case '10tahunan':
                    $query->where('decade', $meta['decade'])->where('year', 0)->where('semester', 0)->where('quarter', 0)->where('month', 0);
                    break;
                case 'tahunan':
                    $query->where('year', $meta['year'])->where('semester', 0)->where('quarter', 0)->where('month', 0);
                    break;
                case 'semesteran':
                    $query->where('year', $meta['year'])->where('semester', $meta['semester'])->where('quarter', 0)->where('month', 0);
                    break;
                case 'kuartal':
                    $query->where('year', $meta['year'])->where('quarter', $meta['quarter'])->where('month', 0);
                    break;
                case 'bulanan':
                    $query->where('year', $meta['year'])->where('month', $meta['month']);
                    break;
            }

            $timeIds = $query->pluck('time_id')->toArray();
            $result[$col['label']] = ['time_id' => $timeIds[0] ?? null, 'time_ids' => $timeIds];
        }

        return $result;
    }

    private function detectLokasiLevel(string $locationId): int
    {
        if (str_ends_with($locationId, '00000000')) return 0;
        if (str_ends_with($locationId, '000000'))   return 1;
        if (str_ends_with($locationId, '0000'))      return 2;
        return 3;
    }

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

    private function namaBulan(int $bulan): string
    {
        return ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$bulan] ?? (string) $bulan;
    }

    private function periodeLabel(string $frekuensi): string
    {
        return match ($frekuensi) {
            '10tahunan'  => 'Dekade',
            '5tahunan'   => '5 Tahunan',
            'tahunan'    => 'Tahun',
            'semesteran' => 'Semester',
            'kuartal'    => 'Kuartal',
            'bulanan'    => 'Bulan',
            default      => 'Periode',
        };
    }

    private function makeFilename(array $p, string $ext): string
    {
        $name = str_replace([' ', '/'], '_', $p['tampilan']->nama_tampilan ?? 'export');
        $range = ($p['year_from'] ?? '') . '-' . ($p['year_to'] ?? '');
        return "Export_{$name}_{$p['frekuensi']}_{$range}.{$ext}";
    }
}