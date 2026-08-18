<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use App\Models\Klasifikasi;
use App\Models\Metadata;
use App\Models\ProdusenData;
use App\Models\Satuan;
use Illuminate\Support\Facades\DB;

class MetadataController extends Controller
{
    const STATUS_PENDING  = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;


    public function index(Request $request)
    {
        $query = Metadata::with(['produsen', 'klasifikasi'])
                ->where('status', self::STATUS_ACTIVE)
                ->leftJoinSub(
                    DB::table('data')
                        ->join('time', 'data.time_id', '=', 'time.time_id')
                        ->where('data.status', 1)
                        ->whereNotNull('data.number_value')
                        ->selectRaw('data.metadata_id, MAX(time.year) as max_year')
                        ->groupBy('data.metadata_id'),
                    'data_avail', 'data_avail.metadata_id', '=', 'metadata.metadata_id'
                )
                ->select('metadata.*');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('nama',  'like', "%$s%")
                ->orWhere('alias', 'like', "%$s%")
                ->orWhere('tag',   'like', "%$s%")
            );
        }

        if ($request->filled('filter_nama'))        { $query->where('nama', 'like', '%'.$request->filter_nama.'%'); }
        if ($request->filled('filter_klasifikasi')) { $query->where('klasifikasi_id', $request->filter_klasifikasi); }
        if ($request->filled('filter_tipe_data'))   { $query->where('tipe_data', $request->filter_tipe_data); }
        if ($request->filled('filter_satuan'))      { $query->where('satuan_data', 'like', '%'.$request->filter_satuan.'%'); }
        if ($request->filled('filter_frekuensi'))   { $query->where('frekuensi_penerbitan', $request->filter_frekuensi); }
        if ($request->filled('filter_produsen_id')) { $query->where('sumber_metadata_pertama', $request->filter_produsen_id); }

        // ── Filter Akses (is_free) ──────────────────────────────────────
        if ($request->filled('filter_akses')) {
            $query->where('is_free', $request->filter_akses === 'gratis' ? 1 : 0);
        }

        $data = $query
            ->orderByRaw('metadata.is_free DESC')           // free di atas
            ->orderByRaw('data_avail.max_year IS NULL, data_avail.max_year DESC')
            ->orderBy('metadata.metadata_id', 'desc')
            ->paginate(15)->withQueryString();

        $klasifikasiList = Klasifikasi::orderBy('nama_klasifikasi')
            ->get(['klasifikasi_id', 'nama_klasifikasi']);

        $tipeDataList = Metadata::where('status', self::STATUS_ACTIVE)
            ->distinct()->orderBy('tipe_data')->pluck('tipe_data')->filter()->values();

        $frekuensiList = Metadata::where('status', self::STATUS_ACTIVE)
            ->distinct()->orderBy('frekuensi_penerbitan')->pluck('frekuensi_penerbitan')->filter()->values();

        $produsenList = ProdusenData::whereIn(
                'produsen_id',
                Metadata::where('status', self::STATUS_ACTIVE)->distinct()->pluck('sumber_metadata_pertama')
            )->orderBy('nama_produsen')->get(['produsen_id', 'nama_produsen']);

        $produsenAll = ProdusenData::orderBy('nama_produsen')->get(['produsen_id', 'nama_produsen']);

        $pendingCount = Metadata::where('status', self::STATUS_PENDING)->count();

        return view('pages.metadata.index', compact(
            'data',
            'klasifikasiList', 'tipeDataList', 'frekuensiList',
            'produsenList', 'produsenAll',
            'pendingCount'
        ));
    }

    public function coverageData(int $id)
    {
        $metadata = Metadata::where('metadata_id', $id)
            ->where('status', self::STATUS_ACTIVE)
            ->firstOrFail(['metadata_id', 'nama']);

        $locations = DB::table('data')
            ->join('location', 'data.location_id', '=', 'location.location_id')
            ->join('time', 'data.time_id', '=', 'time.time_id')
            ->where('data.metadata_id', $id)
            ->where('data.status', 1)  // include "Negara Indonesia"
            ->whereNotNull('data.number_value')
            ->groupBy('location.location_id', 'location.nama_wilayah')
            ->selectRaw('
                location.location_id,
                location.nama_wilayah,
                COUNT(data.id)      AS jumlah_data,
                MIN(time.year)      AS tahun_min,
                MAX(time.year)      AS tahun_max
            ')
            ->orderBy('location.location_id')
            ->get();

        return response()->json([
            'metadata_id' => $metadata->metadata_id,
            'nama'        => $metadata->nama,
            'locations'   => $locations,
        ]);
    }

    public function exportCount(Request $request)
    {
        $query = Metadata::where('status', self::STATUS_ACTIVE);
        if ($request->filled('produsen_id')) { $query->where('sumber_metadata_pertama', $request->produsen_id); }
        if ($request->filled('frekuensi'))   { $query->where('frekuensi_penerbitan', $request->frekuensi); }
        return response()->json(['count' => $query->count()]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'produsen_id' => 'nullable|exists:produsen_data,produsen_id',
            'frekuensi'   => 'nullable|string|max:50',
        ]);

        $query = Metadata::where('status', self::STATUS_ACTIVE)
            ->orderBy('metadata_id');

        if ($request->filled('produsen_id')) {
            $query->where('sumber_metadata_pertama', $request->produsen_id);
        }

        if ($request->filled('frekuensi')) {
            $query->where('frekuensi_penerbitan', $request->frekuensi);
        }

        $rows = $query->with('klasifikasi')->get();

        $parts = ['Metadata'];
        if ($request->filled('produsen_id')) {
            $p = ProdusenData::find($request->produsen_id);
            if ($p) {
                $parts[] = str_replace(' ', '_', $p->nama_produsen);
            }
        }

        if ($request->filled('frekuensi')) {
            $parts[] = str_replace(' ', '_', $request->frekuensi);
        }

        $parts[] = now()->format('Ymd');
        $filename = implode('_', $parts) . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Metadata');

        $headers = [
            'ID', 'Nama', 'Alias', 'Konsep', 'Definisi', 'Klasifikasi', 'Asumsi',
            'Metodologi', 'Penjelasan Metodologi', 'Tipe Data', 'Satuan Data',
            'Tahun Mulai Data', 'Tahun Metadata', 'Frekuensi Penerbitan', 'Tahun Data Tersedia',
            'Bulan Pertama Rilis', 'Tanggal Rilis', 'Sumber Metadata Pertama', 'Tag',
            'Flag Desimal', 'Tipe Group', 'Group By', 'Status',
        ];

        // ── Header Row ──────────────────────────────────────────────────────
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $sheet->getStyle('A1:W1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // ── Data Rows — HANYA setCellValue, TANPA getStyle per baris ─────────
        foreach ($rows as $index => $m) {
            $row = $index + 2;

            $sheet->setCellValue("A{$row}", $m->metadata_id);
            $sheet->setCellValue("B{$row}", $m->nama);
            $sheet->setCellValue("C{$row}", $m->alias);
            $sheet->setCellValue("D{$row}", $m->konsep);
            $sheet->setCellValue("E{$row}", $m->definisi);
            $sheet->setCellValue("F{$row}", $m->klasifikasi?->nama_klasifikasi);
            $sheet->setCellValue("G{$row}", $m->asumsi);
            $sheet->setCellValue("H{$row}", $m->metodologi);
            $sheet->setCellValue("I{$row}", $m->penjelasan_metodologi);
            $sheet->setCellValue("J{$row}", $m->tipe_data);
            $sheet->setCellValue("K{$row}", $m->satuan_data);
            $sheet->setCellValue("L{$row}", $m->tahun_mulai);
            $sheet->setCellValue("M{$row}", $m->tahun_metadata ?? 2026);
            $sheet->setCellValue("N{$row}", $m->frekuensi_penerbitan);
            $sheet->setCellValue("O{$row}", $m->tahun_data_tersedia);
            $sheet->setCellValue("P{$row}", $m->bulan_pertama_rilis);
            $sheet->setCellValue("Q{$row}", $m->tanggal_rilis);
            $sheet->setCellValue("R{$row}", $m->sumber_metadata_pertama);

            $sheet->setCellValue(
                "S{$row}",
                is_array($m->tag)
                    ? json_encode($m->tag, JSON_UNESCAPED_UNICODE)
                    : ($m->tag ?? '[]')
            );

            $sheet->setCellValue("T{$row}", $m->flag_decimal ?? 0);
            $sheet->setCellValue("U{$row}", $m->tipe_group ?? 0);
            $sheet->setCellValue("V{$row}", $m->group_by);
            $sheet->setCellValue("W{$row}", $m->status ?? 1);
        }

        // ── Border SEKALI untuk seluruh block data, bukan per baris ──────────
        $lastRow = $rows->count() + 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("A2:W{$lastRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_HAIR);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]
        );
    }
    private function buildKodeWilayah($loc)
    {
        return (string) $loc->location_id;
    }

    public function exportTemplate(Request $request)
    {
        $request->validate([
            'produsen_id' => 'required|exists:produsen_data,produsen_id',
            'rentang'     => 'required|in:5-tahun,semester,quarter,bulanan',
            'tahun_awal'  => 'required|integer|min:1990|max:2099',
        ]);

        $produsen  = ProdusenData::findOrFail($request->produsen_id);
        $tahunAwal = (int) $request->tahun_awal;
        $rentang   = $request->rentang;

        // ── Metadata aktif milik produsen ─────────────────────────────────────
        $metadataList = Metadata::where('status', self::STATUS_ACTIVE)
            ->where('sumber_metadata_pertama', $request->produsen_id)
            ->orderBy('metadata_id')
            ->get(['metadata_id', 'nama', 'satuan_data', 'flag_desimal']);

        // ── Bangun daftar kolom periode ────────────────────────────────────────
        $periodCols = $this->buildPeriodColumns($rentang, $tahunAwal);

        // ── Nama file ──────────────────────────────────────────────────────────
        $rentangLabel = [
            '5-tahun'  => '5Tahun',
            'semester' => 'Semester',
            'quarter'  => 'Quarter',
            'bulanan'  => 'Bulanan',
        ][$rentang];

        $filename = 'Template_' . str_replace(' ', '_', $produsen->nama_produsen)
                . '_' . $rentangLabel
                . '_' . $tahunAwal . '-' . ($tahunAwal + 4)
                . '_' . now()->format('Ymd')
                . '.xlsx';

        $metadataIds = $metadataList->pluck('metadata_id')->toArray();

        $rawData = [];

        if (! empty($metadataIds)) {
            $rawData = \Illuminate\Support\Facades\DB::table('data')
                ->join('time',     'data.time_id',     '=', 'time.time_id')
                ->join('location', 'data.location_id', '=', 'location.location_id')
                ->whereIn('data.metadata_id', $metadataIds)
                ->where('data.status', 1)
                ->whereIn('time.year', $this->extractYearsFromPeriod($rentang, $tahunAwal))
                ->where('time.month',   0)
                ->where('time.quarter', 0)
                ->select(
                    'data.metadata_id',
                    'data.location_id',
                    'data.number_value',
                    'time.year',
                    'time.quarter',
                    'time.month',
                    'location.location_id',
                    'location.nama_wilayah'
                )
                ->orderBy('data.metadata_id')
                ->orderBy('location.location_id')
                ->orderBy('time.year')
                ->get();
        }

        $pivot = [];

        foreach ($rawData as $row) {
            $metaId = (int) $row->metadata_id;
            $locId  = (int) $row->location_id;

            if (! isset($pivot[$metaId][$locId])) {
                $pivot[$metaId][$locId] = [
                    'location_row' => $row,
                    'periods'      => [],
                ];
            }

            $periodKey = $this->buildPeriodKey($rentang, $row);

            if ($periodKey !== null && ! array_key_exists($periodKey, $pivot[$metaId][$locId]['periods'])) {
                $pivot[$metaId][$locId]['periods'][$periodKey] = $row->number_value !== null
                    ? (float) $row->number_value
                    : null;
            }
        }

        // ── Bangun $excelRows sekaligus catat range baris per metadata (utk styling batch) ──
        $excelRows        = [];
        $metaRowRanges    = []; // [metadata_id => ['start' => n, 'end' => n, 'flag_desimal' => x]]

        foreach ($metadataList as $meta) {
            $metaId    = $meta->metadata_id;
            $startIdx  = count($excelRows); // index sebelum ditambah baris metadata ini

            if (! empty($pivot[$metaId])) {
                foreach ($pivot[$metaId] as $locId => $locData) {
                    $namaLokasi = $this->buildLocationName($locData['location_row']);
                    $loc = $locData['location_row'];

                    $row = [
                        'metadata_id'  => $metaId,
                        'nama_metadata'=> $meta->nama,
                        'satuan_metadata'       => $meta->satuan_data,
                        'satuan_rujukan' => null,
                        'location_id' => $this->buildKodeWilayah($loc),
                        'nama_wilayah'  => $namaLokasi,
                        'rujukan_id'    => null,
                        'flag_desimal' => $meta->flag_desimal ?? 0,
                        'periods'      => [],
                    ];

                    foreach ($periodCols as $colLabel) {
                        $row['periods'][$colLabel] = $locData['periods'][$colLabel] ?? null;
                    }

                    $excelRows[] = $row;
                }
            } else {
                $row = [
                    'metadata_id'  => $metaId,
                    'nama_metadata'=> $meta->nama,
                    'satuan_metadata'       => $meta->satuan_data,
                    'satuan_rujukan' => null,
                    'location_id' => '',
                    'nama_wilayah'  => null,
                    'rujukan_id'    => null,
                    'flag_desimal' => $meta->flag_desimal ?? 0,
                    'periods'      => array_fill_keys($periodCols, null),
                ];
                $excelRows[] = $row;
            }

            $endIdx = count($excelRows) - 1;
            if ($endIdx >= $startIdx) {
                $metaRowRanges[] = [
                    'start'        => $startIdx + 5, // +5 karena data mulai di baris ke-5 sheet
                    'end'          => $endIdx + 5,
                    'flag_desimal' => (int) ($meta->flag_desimal ?? 0),
                ];
            }
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Template Import Data')
            ->setDescription('Template import data untuk produsen ' . $produsen->nama_produsen);

        // Warna palet
        $C_HEADER    = '0284C7';
        $C_PERIOD    = '0369A1';
        $C_META_FILL = 'E0F2FE';
        $C_META_ALT  = 'DBEAFE';
        $C_VAL_ALT   = 'F0F9FF';

        $ws = $spreadsheet->getActiveSheet()->setTitle('Data Import');

        $totalCols     = 6 + count($periodCols);
        $lastColLetter = $this->colLetter($totalCols);

        // ── Baris 1: Judul ────────────────────────────────────────────────────
        $ws->mergeCells('A1:' . $lastColLetter . '1');
        $ws->setCellValue('A1',
            'Template Import Data — ' . $produsen->nama_produsen
            . ' | ' . $rentangLabel . ' ' . $tahunAwal . '–' . ($tahunAwal + 4)
        );
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => $C_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(26);

        // ── Baris 2: Info ─────────────────────────────────────────────────────
        $totalWithData = array_reduce($excelRows, function($c, $r) {
            return $c + (!empty($r['location_id']) ? 1 : 0);
        }, 0);

        $ws->mergeCells('A2:' . $lastColLetter . '2');
        $ws->setCellValue('A2',
            $metadataList->count() . ' metadata aktif  |  '
            . count($periodCols) . ' kolom periode  |  '
            . $totalWithData . ' baris berisi data existing'
        );
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $ws->getRowDimension(2)->setRowHeight(16);

        // ── Baris 3: Header kolom ─────────────────────────────────────────────
        $fixedHeaders = [
            ['label' => 'metadata_id',   'width' => 13, 'note' => 'ID metadata (otomatis, jangan diubah)'],
            ['label' => 'nama_metadata', 'width' => 40, 'note' => 'Nama metadata (otomatis, jangan diubah)'],
            ['label' => 'satuan_metadata',        'width' => 20, 'note' => 'Satuan metadata'],
            ['label' => 'satuan_rujukan',        'width' => 20, 'note' => 'Satuan rujukan'],
            ['label' => 'location_id',   'width' => 13, 'note' => 'ID lokasi dari tabel dimensi lokasi'],
            ['label' => 'nama_wilayah',   'width' => 40, 'note' => 'Nama lokasi (referensi, boleh dikosongkan)'],
            ['label' => 'rujukan_id',    'width' => 15, 'note' => 'ID rujukan dari tabel rujukan'],
        ];

        foreach ($fixedHeaders as $i => $h) {
            $col  = $this->colLetter($i + 1);
            $cell = $col . '3';
            $ws->setCellValue($cell, $h['label']);
            $ws->getColumnDimension($col)->setWidth($h['width']);
            $ws->getComment($cell)->getText()->createTextRun($h['note']);
        }

        foreach ($periodCols as $pi => $periodLabel) {
            $col = $this->colLetter(7 + $pi);
            $ws->setCellValue($col . '3', $periodLabel);
            $ws->getColumnDimension($col)->setWidth(12);
        }

        // Style header A–F (sky-600)
        $ws->getStyle('A3:F3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $C_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                            'color'       => ['rgb' => '0369A1']]],
        ]);

        if (count($periodCols) > 0) {
            $ws->getStyle('G3:' . $lastColLetter . '3')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $C_PERIOD]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                'color'       => ['rgb' => '075985']]],
            ]);
        }
        $ws->getRowDimension(3)->setRowHeight(22);

        // ── Baris 4+: Data — HANYA setCellValue di sini, TANPA getStyle per sel ──
        if (empty($excelRows)) {
            $ws->mergeCells('A4:' . $lastColLetter . '4');
            $ws->setCellValue('A4', 'Tidak ada metadata aktif untuk produsen ini.');
            $ws->getStyle('A4')->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font'      => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
            ]);
        } else {
            foreach ($excelRows as $i => $row) {
                $rowNum = $i + 4;

                $ws->setCellValue('A' . $rowNum, $row['metadata_id']);
                $ws->setCellValue('B' . $rowNum, $row['nama_metadata']);
                $ws->setCellValue('C' . $rowNum, $row['satuan_metadata']);
                $ws->setCellValue('D' . $rowNum, $row['satuan_rujukan']);
                $ws->setCellValue('D' . $rowNum, $row['location_id']);
                $ws->setCellValue('E' . $rowNum, $row['nama_wilayah']);
                $ws->setCellValue('F' . $rowNum, $row['rujukan_id']);

                foreach ($periodCols as $pi => $colLabel) {
                    $value = $row['periods'][$colLabel] ?? null;
                    if ($value !== null) {
                        $col = $this->colLetter(7 + $pi);
                        $ws->setCellValue($col . $rowNum, $value);
                    }
                }

                $ws->getRowDimension($rowNum)->setRowHeight(18);
            }

            $lastDataRow = count($excelRows) + 3;

            // ── Styling BATCH per range, bukan per sel ──────────────────────────
            // Kolom A-B (metadata_id, nama_metadata)
            $ws->getStyle("A4:B{$lastDataRow}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $C_META_FILL]],
                'font'      => ['size' => 9, 'color' => ['rgb' => '0369A1']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'BFDBFE']]],
            ]);
            $ws->getStyle("A4:A{$lastDataRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '0369A1']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Kolom D (location_id), E (nama_wilayah), F (rujukan_id)
            $ws->getStyle("D4:F{$lastDataRow}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'font'      => ['size' => 9],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            $ws->getStyle("D4:D{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $ws->getStyle("F4:F{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Kolom periode (G+) — style dasar untuk seluruh block sekaligus
            if (count($periodCols) > 0) {
                $ws->getStyle("G4:{$lastColLetter}{$lastDataRow}")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                    'font'      => ['size' => 9],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'E0F2FE']]],
                ]);

                // Number format per metadata (grouped by contiguous row range, bukan per sel)
                foreach ($metaRowRanges as $range) {
                    $numFormat = $range['flag_desimal'] > 0
                        ? '#,##0.0' . str_repeat('0', $range['flag_desimal'])
                        : '#,##0';

                    $ws->getStyle("G{$range['start']}:{$lastColLetter}{$range['end']}")
                        ->getNumberFormat()->setFormatCode($numFormat);
                }
            }

            // Warna selang-seling (alternating row) — 1 range call per baris ganjil,
            // jauh lebih sedikit dari sebelumnya (dulu 6+N getStyle per baris)
            for ($i = 1; $i < count($excelRows); $i += 2) {
                $rowNum = $i + 4;
                $ws->getStyle("A{$rowNum}:B{$rowNum}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($C_META_ALT);

                if (count($periodCols) > 0) {
                    $ws->getStyle("G{$rowNum}:{$lastColLetter}{$rowNum}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($C_VAL_ALT);
                }
            }
        }

        // ── Freeze & AutoFilter ───────────────────────────────────────────────
        $ws->freezePane('G4');
        $ws->setAutoFilter('A3:' . $lastColLetter . '3');

        $lastDataRow = max(count($excelRows) + 3, 4);
        $unlockStyle = ['protection' => [
            'locked' => Protection::PROTECTION_UNPROTECTED,
        ]];

        $ws->getStyle('A1:' . $lastColLetter . '3')->applyFromArray($unlockStyle);
        $ws->getStyle('C4:' . $lastColLetter . $lastDataRow)->applyFromArray($unlockStyle);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER BARU: extractYearsFromPeriod
    // ═══════════════════════════════════════════════════════════════════════════
    private function extractYearsFromPeriod(string $rentang, int $tahunAwal): array
    {
        return range($tahunAwal, $tahunAwal + 4);
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER BARU: buildPeriodKey
    // ═══════════════════════════════════════════════════════════════════════════
    private function buildPeriodKey(string $rentang, object $row): ?string
    {
        $year = (int) $row->year;
    
        switch ($rentang) {
            case '5-tahun':
                return (string) $year;
    
            case 'semester':
                $s = $row->quarter <= 2 ? 1 : 2;
                return $year . '_S' . $s;
    
            case 'quarter':
                return $year . '_Q' . $row->quarter;
    
            case 'bulanan':
                $bulanPendek = ['Jan','Feb','Mar','Apr','Mei','Jun',
                                'Jul','Agu','Sep','Okt','Nov','Des'];
                $month = (int) $row->month;
                if ($month < 1 || $month > 12) return null;
                return $bulanPendek[$month - 1] . '_' . $year;
        }
    
        return null;
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER buildLocationName
    // ═══════════════════════════════════════════════════════════════════════════
    private function buildLocationName(object $row): string
    {
        return $row->nama_wilayah ?? '-';
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER hasLevel
    // ═══════════════════════════════════════════════════════════════════════════
    private function hasLevel(?string $kode): bool
    {
        if ($kode === null || $kode === '') {
            return false;
        }
        return ltrim($kode, '0') !== '';
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = intdiv($index, 26);
        }
        return $letter;
    }

    private function buildPeriodColumns(string $rentang, int $tahunAwal): array
    {
        $cols      = [];
        $bulanPendek = ['Jan','Feb','Mar','Apr','Mei','Jun',
                         'Jul','Agu','Sep','Okt','Nov','Des'];

        for ($y = $tahunAwal; $y < $tahunAwal + 5; $y++) {
            switch ($rentang) {
                case '5-tahun':
                    $cols[] = (string) $y;
                    break;

                case 'semester':
                    $cols[] = $y . '_S1';
                    $cols[] = $y . '_S2';
                    break;

                case 'quarter':
                    for ($q = 1; $q <= 4; $q++) $cols[] = $y . '_Q' . $q;
                    break;

                case 'bulanan':
                    for ($m = 0; $m < 12; $m++) $cols[] = $bulanPendek[$m] . '_' . $y;
                    break;
            }
        }

        return $cols;
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE / STORE / CHECK NAMA / APPROVAL / APPROVE / REJECT
    // REACTIVATE / BULK APPROVE / BULK APPROVE ALL / DETAIL
    // ═══════════════════════════════════════════════════════════
    public function create()
    {   
        $metadataList = Metadata::where('status', self::STATUS_ACTIVE)
            ->with('klasifikasi:klasifikasi_id,nama_klasifikasi')
            ->orderBy('nama')
            ->get(['metadata_id', 'nama', 'klasifikasi_id']);

        $produsen        = ProdusenData::all();
        $klasifikasiList = Klasifikasi::orderBy('nama_klasifikasi')->get();

        return view('pages.metadata.create', compact('metadataList', 'produsen', 'klasifikasiList'));
    }   

    public function searchForGroupBy(Request $request)
    {
        $q = trim($request->input('q', ''));

        $query = Metadata::where('status', self::STATUS_ACTIVE)
            ->with('klasifikasi:klasifikasi_id,nama_klasifikasi');

        if ($q !== '') {
            $query->where('nama', 'like', "%{$q}%");
        }

        return response()->json(
            $query->orderBy('nama')->limit(30)->get(['metadata_id', 'nama', 'klasifikasi_id'])
                ->map(fn($m) => [
                    'metadata_id' => $m->metadata_id,
                    'nama'        => $m->nama,
                    'klasifikasi' => $m->klasifikasi?->nama_klasifikasi,
                ])
        );
    }

    public function store(Request $request)
    {

        $request->validate([
            'nama'                  => ['required','max:100',Rule::unique('metadata','nama')],
            'alias'                 => 'nullable|max:100',
            'konsep'                => 'required',
            'definisi'              => 'required',
            'klasifikasi_id'        => 'required|exists:klasifikasi,klasifikasi_id',
            'asumsi'                => 'nullable',
            'metodologi'            => 'required|max:100',
            'penjelasan_metodologi' => 'required',
            'tipe_data'             => 'required|max:50',
            'satuan_data'           => 'required|max:50',
            'tahun_mulai_data'      => 'required|max:50',
            'frekuensi_penerbitan'  => 'required|max:50',
            'tahun_pertama_rilis'   => 'nullable|integer|min:1900|max:2100',
            'bulan_pertama_rilis'   => 'nullable|integer|between:1,12',
            'tanggal_rilis'         => 'nullable|integer|between:1,31',
            'flag_desimal'          => 'required|integer',
            'tag'                   => 'required|max:255',
            'produsen_id'           => 'required',
            'tahun_metadata'        => 'nullable|integer|min:1900|max:2100',
            'tahun_metadata'        => 'nullable|integer|min:1900|max:2100',
            'tipe_group'            => 'required|integer',
            'group_by'              => [
                'nullable',
                Rule::requiredIf($request->tipe_group == 1),
                Rule::exists('metadata','metadata_id')->where('status', self::STATUS_ACTIVE),
            ],
        ]);

        // Resolve produsen input (accept numeric ID or produsen name)
        $produsenInput = $request->produsen_id;
        $produsenId = null;
        if (is_numeric($produsenInput)) {
            $produsenId = (int) $produsenInput;
        } elseif (is_string($produsenInput) && trim($produsenInput) !== '') {
            $found = ProdusenData::whereRaw('LOWER(nama_produsen) = ?', [strtolower(trim($produsenInput))])->first();
            if ($found) $produsenId = $found->produsen_id;
        }

        if (! $produsenId) {
            return back()->withErrors(['produsen_id' => 'Produsen tidak ditemukan. Gunakan ID atau nama produsen yang valid.'])->withInput();
        }

        Metadata::create([
            'nama'                   => $request->nama,
            'alias'                  => $request->alias,
            'konsep'                 => $request->konsep,
            'definisi'               => $request->definisi,
            'klasifikasi_id'         => $request->klasifikasi_id,
            'asumsi'                 => $request->filled('asumsi') ? $request->asumsi : null,
            'metodologi'             => $request->metodologi,
            'penjelasan_metodologi'  => $request->penjelasan_metodologi,
            'tipe_data'              => $request->tipe_data,
            'satuan_data'            => $request->satuan_data,
            'tahun_mulai_data'       => $request->tahun_mulai_data,
            'frekuensi_penerbitan'   => $request->frekuensi_penerbitan,
            'tahun_pertama_rilis'    => $request->tahun_pertama_rilis,
            'bulan_pertama_rilis'    => $request->bulan_pertama_rilis,
            'tanggal_rilis'          => $request->tanggal_rilis,
            'flag_desimal'           => $request->flag_desimal,
            'tag'                    => $request->tag,
            'sumber_metadata_pertama'            => $produsenId,
            'tahun_metadata'                     => $request->tahun_metadata ?? 2026,
            'tipe_group'             => $request->tipe_group ?? 0,
            'group_by'               => $request->tipe_group == 1 ? $request->group_by : null,
            'status'                 => self::STATUS_PENDING,
            'date_inputed'           => now(),
            'user_id'                => Auth::user()->user_id,
        ]);

        return redirect()->route('metadata.index')
            ->with('success', 'Metadata berhasil ditambahkan dan menunggu persetujuan admin.');
    }

    public function edit(Metadata $metadata)
    {
        $metadataList    = Metadata::where('status', self::STATUS_ACTIVE)
            ->where('metadata_id', '!=', $metadata->metadata_id) // tidak boleh jadi induk dirinya sendiri
            ->orderBy('nama')->get();
        $produsen        = ProdusenData::all();
        $klasifikasiList = Klasifikasi::orderBy('nama_klasifikasi')->get();

        return view('pages.metadata.edit', compact('metadata', 'metadataList', 'produsen', 'klasifikasiList'));
    }

    public function update(Request $request, Metadata $metadata)
    {
        $request->validate([
            'nama'                  => ['required', 'max:100', Rule::unique('metadata', 'nama')->ignore($metadata->metadata_id, 'metadata_id')],
            'alias'                 => 'nullable|max:100',
            'konsep'                => 'required',
            'definisi'              => 'required',
            'klasifikasi_id'        => 'required|exists:klasifikasi,klasifikasi_id',
            'asumsi'                => 'nullable',
            'metodologi'            => 'required|max:100',
            'penjelasan_metodologi' => 'required',
            'tipe_data'             => 'required|max:50',
            'satuan_data'           => 'required|max:50',
            'tahun_mulai_data'      => 'required|max:50',
            'frekuensi_penerbitan'  => 'required|max:50',
            'tahun_pertama_rilis'   => 'nullable|integer|min:1900|max:2100',
            'bulan_pertama_rilis'   => 'nullable|integer|between:1,12',
            'tanggal_rilis'         => 'nullable|integer|between:1,31',
            'flag_desimal'          => 'required|integer',
            'tag'                   => 'required|max:255',
            'produsen_id'           => 'required',
            'tipe_group'            => 'required|integer',
            'group_by'              => [
                'nullable',
                Rule::requiredIf($request->tipe_group == 1),
                Rule::exists('metadata', 'metadata_id')->where('status', self::STATUS_ACTIVE),
                // cegah memilih dirinya sendiri sebagai induk
                function ($attribute, $value, $fail) use ($metadata) {
                    if ($value == $metadata->metadata_id) {
                        $fail('Metadata tidak boleh menjadi induk dari dirinya sendiri.');
                    }
                },
            ],
        ]);

        // Resolve produsen input (accept numeric ID or produsen name)
        $produsenInput = $request->produsen_id;
        $produsenId = null;
        if (is_numeric($produsenInput)) {
            $produsenId = (int) $produsenInput;
        } elseif (is_string($produsenInput) && trim($produsenInput) !== '') {
            $found = ProdusenData::whereRaw('LOWER(nama_produsen) = ?', [strtolower(trim($produsenInput))])->first();
            if ($found) $produsenId = $found->produsen_id;
        }

        if (! $produsenId) {
            return back()->withErrors(['produsen_id' => 'Produsen tidak ditemukan. Gunakan ID atau nama produsen yang valid.'])->withInput();
        }

        $metadata->update([
            'nama'                   => $request->nama,
            'alias'                  => $request->alias,
            'konsep'                 => $request->konsep,
            'definisi'               => $request->definisi,
            'klasifikasi_id'         => $request->klasifikasi_id,
            'asumsi'                 => $request->filled('asumsi') ? $request->asumsi : null,
            'metodologi'             => $request->metodologi,
            'penjelasan_metodologi'  => $request->penjelasan_metodologi,
            'tipe_data'              => $request->tipe_data,
            'satuan_data'            => $request->satuan_data,
            'tahun_mulai_data'       => $request->tahun_mulai_data,
            'frekuensi_penerbitan'   => $request->frekuensi_penerbitan,
            'tahun_pertama_rilis'    => $request->tahun_pertama_rilis,
            'bulan_pertama_rilis'    => $request->bulan_pertama_rilis,
            'tanggal_rilis'          => $request->tanggal_rilis,
            'flag_desimal'           => $request->flag_desimal,
            'tag'                    => $request->tag,
            'sumber_metadata_pertama'            => $produsenId,
            'tahun_metadata'                     => $request->tahun_metadata ?? 2026,
            'tipe_group'             => $request->tipe_group ?? 0,
            'group_by'               => $request->tipe_group == 1 ? $request->group_by : null,
        ]);

        return redirect()->route('metadata.detail', $metadata->metadata_id)
            ->with('success', "Metadata '{$metadata->nama}' berhasil diperbarui.");
    }

    public function checkNama(Request $request)
    {
        return response()->json(['exists' => Metadata::where('nama', $request->query('nama',''))->exists()]);
    }

    public function approval(Request $request)
    {
        $statusFilter = (int) $request->input('status', self::STATUS_PENDING);
        $query = Metadata::with(['user','produsen', 'klasifikasi'])->where('status', $statusFilter);

        if ($request->filled('search'))             { $query->where('nama','like','%'.$request->search.'%'); }
        if ($request->filled('filter_nama'))        { $query->where('nama','like','%'.$request->filter_nama.'%'); }
        if ($request->filled('filter_klasifikasi')) {
            $query->where('klasifikasi_id', $request->filter_klasifikasi);
        }
        if ($request->filled('filter_produsen_id')) { $query->where('sumber_metadata_pertama',$request->filter_produsen_id); }
        if ($request->filled('filter_tipe_data'))   { $query->where('tipe_data',$request->filter_tipe_data); }
        if ($request->filled('filter_user'))        { $query->whereHas('user',fn($q)=>$q->where('name','like','%'.$request->filter_user.'%')); }
        if ($request->filled('filter_date_from'))   { $query->whereDate('date_inputed','>=',$request->filter_date_from); }
        if ($request->filled('filter_date_to'))     { $query->whereDate('date_inputed','<=',$request->filter_date_to); }

        $data = $query->orderBy('metadata_id','desc')->paginate(15)->withQueryString();

        $countPending  = Metadata::where('status', self::STATUS_PENDING)->count();
        $countActive   = Metadata::where('status', self::STATUS_ACTIVE)->count();
        $countInactive = Metadata::where('status', self::STATUS_INACTIVE)->count();

        $klasifikasiList = Klasifikasi::orderBy('nama_klasifikasi')
            ->get(['klasifikasi_id', 'nama_klasifikasi']);
        $tipeDataList    = Metadata::select('tipe_data')->distinct()->orderBy('tipe_data')->pluck('tipe_data')->filter()->values();
        $produsenList    = ProdusenData::whereIn('produsen_id', Metadata::distinct()->pluck('sumber_metadata_pertama'))->orderBy('nama_produsen')->get(['produsen_id','nama_produsen']);

        return view('pages.metadata.approval', compact('data','countPending','countActive','countInactive','statusFilter','klasifikasiList','tipeDataList','produsenList'));
    }

    public function approve(Request $request, Metadata $metadata)
    {
        $metadata->update(['status' => self::STATUS_ACTIVE]);
        if ($request->wantsJson()) return response()->json(['success'=>true,'message'=>"Metadata '{$metadata->nama}' berhasil diaktifkan."]);
        return back()->with('success', "Metadata '{$metadata->nama}' berhasil diaktifkan.");
    }

    public function reject(Request $request, Metadata $metadata)
    {
        $metadata->update(['status' => self::STATUS_INACTIVE]);
        if ($request->wantsJson()) return response()->json(['success'=>true,'message'=>"Metadata '{$metadata->nama}' dinonaktifkan."]);
        return back()->with('success', "Metadata '{$metadata->nama}' dinonaktifkan.");
    }

    public function reactivate(Request $request, Metadata $metadata)
    {
        $metadata->update(['status' => self::STATUS_ACTIVE]);
        if ($request->wantsJson()) return response()->json(['success'=>true,'message'=>"Metadata '{$metadata->nama}' berhasil diaktifkan kembali."]);
        return back()->with('success', "Metadata '{$metadata->nama}' berhasil diaktifkan kembali.");
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids'=>'required|array|min:1','ids.*'=>'integer|exists:metadata,metadata_id']);
        $updated = Metadata::whereIn('metadata_id',$request->ids)->where('status',self::STATUS_PENDING)->update(['status'=>self::STATUS_ACTIVE]);
        return response()->json(['success'=>true,'updated'=>$updated,'message'=>"{$updated} metadata berhasil diapprove."]);
    }

    public function bulkApproveAll(Request $request)
    {
        $updated = Metadata::where('status', self::STATUS_PENDING)->update(['status' => self::STATUS_ACTIVE]);
        return response()->json(['success'=>true,'updated'=>$updated,'message'=>"{$updated} metadata berhasil diapprove semua."]);
    }

    public function detail(Metadata $metadata)
    {
        $metadata->load([
            'groupParent',
            'groupChildren',
            'user',
            'produsen',
            'klasifikasi'
        ]);
 
        $subNamaMetadata = $metadata->sub_nama_metadata ?? [];
        $satuanNames = [];
        if (!empty($subNamaMetadata)) {
            $satuanNames = Satuan::whereIn('satuan_id', array_keys($subNamaMetadata))
                ->pluck('nama_satuan', 'satuan_id')
                ->toArray();
        }
 
        return view('pages.metadata.detail', compact('metadata', 'satuanNames'));
    }

    public function detailApi(int $id)
    {
        $metadata = Metadata::where('metadata_id', $id)
            ->with(['klasifikasi:klasifikasi_id,nama_klasifikasi', 'produsen:produsen_id,nama_produsen'])
            ->firstOrFail();
    
        return response()->json([
            'metadata_id'              => $metadata->metadata_id,
            'nama'                     => $metadata->nama,
            'alias'                    => $metadata->alias,
            'konsep'                   => $metadata->konsep,
            'definisi'                 => $metadata->definisi,
            'metodologi'               => $metadata->metodologi,
            'penjelasan_metodologi'    => $metadata->penjelasan_metodologi,
            'tipe_data'                => $metadata->tipe_data,
            'satuan_data'              => $metadata->satuan_data,
            'sub_nama_metadata'        => $metadata->sub_nama_metadata ?? [],
            'frekuensi_penerbitan'     => $metadata->frekuensi_penerbitan,
            'tahun_mulai_data'         => $metadata->tahun_mulai,
            'tahun_pertama_rilis'      => $metadata->tahun_pertama_rilis,
            'bulan_pertama_rilis'      => $metadata->bulan_pertama_rilis,
            'tahun_data_tersedia'      => $metadata->tahun_data_tersedia,
            'klasifikasi'              => $metadata->klasifikasi?->nama_klasifikasi,
            'produsen'                 => $metadata->produsen?->nama_produsen,
            'status'                   => $metadata->status,
            'tag'                      => $metadata->tag,
            'date_inputed'             => $metadata->date_inputed,
        ]);
    }

    public function toggleFree(Request $request, Metadata $metadata)
    {
        $metadata->update(['is_free' => !$metadata->is_free]);

        $label = $metadata->is_free ? 'Gratis' : 'Premium';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_free' => $metadata->is_free,
                'message' => "Metadata '{$metadata->nama}' sekarang berstatus {$label}.",
            ]);
        }

        return back()->with('success', "Metadata '{$metadata->nama}' sekarang berstatus {$label}.");
    }
}