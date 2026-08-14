<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Metadata;
use App\Models\ProdusenData;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow;
    private $endRow;

    public function __construct($startRow, $endRow)
    {
        $this->startRow = $startRow;
        $this->endRow   = $endRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        return $row === 1 || ($row >= $this->startRow && $row <= $this->endRow);
    }
}

class MetadataImportController extends Controller
{
    private function getValidKlasifikasi(): array
    {
        return \App\Models\Klasifikasi::pluck('nama_klasifikasi')
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->toArray();
    }

    private function normalizeKlasifikasi(?string $value): ?string
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach ($this->getValidKlasifikasi() as $item) {
            if (mb_strtolower($item) === mb_strtolower($value)) {
                return $item; // pakai versi database
            }
        }

        return ucwords(mb_strtolower($value));
    }

    private array $klasifikasiCache = [];

    private function resolveKlasifikasiId(?string $nama): ?int
    {
        if (!$nama || trim($nama) === '') return null;

        $key = mb_strtolower(trim($nama));

        if (!isset($this->klasifikasiCache[$key])) {
            $found = \App\Models\Klasifikasi::whereRaw('LOWER(nama_klasifikasi) = ?', [$key])->first();
            $this->klasifikasiCache[$key] = $found?->klasifikasi_id;
        }

        return $this->klasifikasiCache[$key];
    }
    // ─────────────────────────────────────────────────────────────
    // TUNING
    // ─────────────────────────────────────────────────────────────
    private const READ_CHUNK  = 300;

    private const INSERT_CHUNK = 100;

    // ─────────────────────────────────────────────────────────────
    // MAPPING: indeks kolom Excel 
    // ─────────────────────────────────────────────────────────────
    private const COL = [
        0  => 'excel_id',
        1  => 'nama',
        2  => 'alias',
        3  => 'konsep',
        4  => 'definisi',
        5  => 'klasifikasi_id',
        6  => 'asumsi',
        7  => 'metodologi',
        8  => 'penjelasan_metodologi',
        9  => 'tipe_data',
        10 => 'satuan_data',
        11 => 'tahun_mulai_data',
        12 => 'frekuensi_penerbitan',
        13 => 'tahun_pertama_rilis',
        14 => 'bulan_pertama_rilis',
        15 => 'tanggal_rilis',
        16 => 'sumber_metadata_pertama',
        17 => 'tag',
        18 => 'flag_desimal',
        19 => 'tipe_group',
        20 => 'group_by',
        21 => '_status_excel',
        22 => 'tahun_metadata',
    ];

    private const STRING_DASH = [
        'konsep', 'definisi',
        'metodologi', 'penjelasan_metodologi',
        'tipe_data', 'satuan_data', 'tahun_mulai_data',
        'frekuensi_penerbitan',
        
    ];

    // Normalisasi kata
    private const ALIAS_WILAYAH = [

        // ── KHUSUS CABANG WILAYAH ─────────────────
        '/\s+Cabang\s+(Gianyar|Ubud|Sukawati|Denpasar|Badung|Bangli|Karangasem)(?:\s+\w+)?(?=\s|$)/iu',
        
        // ── POLA WILAYAH UMUM ─────────────────────
        '/\s+di\s+(Kabupaten|Kab\.?|Kecamatan|Kec\.?|Kota|Provinsi|Prov\.?|Desa|Kelurahan|Kel\.?)\s+[\w\s]+$/iu',
        '/\s+(Kabupaten|Kab\.|Kecamatan|Kec\.|Kota|Provinsi|Prov\.|Desa|Kelurahan|Kel\.)\s+\w+(\s+\w+)*$/iu',
        
        '/\s+(Sukawati|Blahbatuh|Tampaksiring|Tegallalang|Payangan)\s*$/iu',
        '/\s+(Badung|Bangli|Buleleng|Jembrana|Karangasem|Klungkung|Tabanan|Denpasar)\s*$/iu',
        '/\s+(Utara|Selatan|Barat|Timur|Tengah)\s*$/iu',
        '/\s+(Ubud|Gianyar)\s*$/iu',

        // ── CABANG TANPA NAMA (DI AKHIR) ──────────
        '/\s+Cabang$/iu',

        // ── KAB TANPA NAMA ────────────────────────
        '/\s+Kab\.?$/iu',
    ];

    private function smartNormalizeWilayah(?string $text): ?string
    {
        if (!$text) return $text;

        $text = trim($text);

        // ── 1. Hard cut (alamat jelas) ──
        $lower = mb_strtolower($text);
        $triggers = ['bertempat di', 'alamat'];

        foreach ($triggers as $trigger) {
            $pos = mb_strpos($lower, $trigger);
            if ($pos !== false) {
                return trim(mb_substr($text, 0, $pos));
            }
        }

        // ── 2. Hapus "di Kabupaten ..." ──
        $text = preg_replace(
            '/\bdi\s+(kabupaten|kab\.?|kecamatan|kota|provinsi)\s+[a-z]+/iu',
            '',
            $text
        );

        // ── 3. Hapus "Cabang + wilayah" (INI YANG FIX UTAMA) ──
        $text = preg_replace(
            '/\bcabang\s+(gianyar|ubud|sukawati|denpasar|badung|bangli|karangasem)\b/iu',
            '',
            $text
        );

        // ── 4. Hapus duplikasi lokasi ──
        $text = preg_replace(
            '/(\bdi\s+(kabupaten|kab\.?|kecamatan|kota|provinsi)\s+[a-z]+)(\s+\1)+/iu',
            '$1',
            $text
        );

        // ── 5. Hapus trailing wilayah ──
        $text = preg_replace(
            '/\b(kabupaten|kab\.?|kecamatan|kota|provinsi)\s+[a-z]+\s*$/iu',
            '',
            $text
        );

        // ── 6. Hapus nama wilayah di akhir ──
        $text = preg_replace(
            '/\b(gianyar|ubud|sukawati|denpasar|badung|bangli|karangasem)\s*$/iu',
            '',
            $text
        );

        // ── 7. Rapikan spasi ──
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text, " ,");

        return $text;
    }

    private function normalizeAlias(?string $rawAlias): ?string
    {
        if ($rawAlias === null || trim($rawAlias) === '') return null;

        $cleaned = $rawAlias;
        foreach (self::ALIAS_WILAYAH as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }
        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));

        return $cleaned !== '' ? $cleaned : trim($rawAlias);
    }

    
    // ═════════════════════════════════════════════════════════════
    // PREVIEW — POST
    // ═════════════════════════════════════════════════════════════
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes'    => 'Format file harus .xlsx atau .xls.',
            'file.max'      => 'Ukuran file maksimal 20 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            array_shift($rows); 

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $existingInDb = Metadata::pluck('nama')
                ->map(fn($n) => $this->dedupKey($n))
                ->flip()->all();

            $produsenCache = ProdusenData::pluck('nama_produsen', 'produsen_id')->toArray();

            $valid   = [];
            $skipped = [];
            $produsenErrors = [];
            $seen    = [];
            $rowNum  = 2;
            $klasifikasiMap = \App\Models\Klasifikasi::pluck('nama_klasifikasi', 'klasifikasi_id')->toArray();

            foreach ($rows as $raw) {
                if (empty(array_filter($raw, fn($v) => $v !== null && $v !== ''))) {
                    $rowNum++;
                    continue;
                }

                $r   = $this->parseRow($raw);
                $key = $this->dedupKey($r['nama']);

                if (isset($seen[$key])) {
                    $skipped[] = [
                        'row'    => $rowNum,
                        'nama'   => $this->smartNormalizeWilayah($r['nama']),
                        'reason' => 'Duplikat dalam file Excel',
                    ];
                    $rowNum++;
                    continue;
                }

                $seen[$key] = true;

                $produsenNama = '-';
                $pmVal = $r['sumber_metadata_pertama'] ?? null;
                $produsenFound = false;
                if (is_numeric($pmVal) && isset($produsenCache[(int)$pmVal])) {
                    $produsenNama = $produsenCache[(int)$pmVal];
                    $produsenFound = true;
                } elseif (is_string($pmVal) && trim($pmVal) !== '') {
                    $trim = trim($pmVal);
                    // try exact match by name in cache (case-sensitive)
                    $found = array_search($trim, $produsenCache, true);
                    if ($found !== false) {
                        $produsenNama = $produsenCache[$found];
                        $produsenFound = true;
                    } else {
                        // case-insensitive search
                        foreach ($produsenCache as $id => $name) {
                            if (strcasecmp($name, $trim) === 0) {
                                $produsenNama = $name;
                                $produsenFound = true;
                                break;
                            }
                        }
                    }
                }

                // If produsen not found, mark as skipped/error so it won't be counted as importable
                if (! $produsenFound) {
                    $skipped[] = [
                        'row'    => $rowNum,
                        'nama'   => $this->smartNormalizeWilayah($r['nama']),
                        'reason' => 'Produsen tidak ditemukan',
                    ];
                    $produsenErrors[] = [
                        'row' => $rowNum,
                        'nama' => $this->smartNormalizeWilayah($r['nama']),
                        'produsen_input' => $pmVal,
                    ];
                    $rowNum++;
                    continue;
                }

                $valid[] = [
                    'row'              => $rowNum,
                    'nama'             => $this->smartNormalizeWilayah($r['nama']),
                    'alias'            => $this->smartNormalizeWilayah($r['alias']),
                    'klasifikasi'      => $klasifikasiMap[$r['klasifikasi_id']] ?? ('ID: ' . $r['klasifikasi_id']),
                    'tipe_data'        => $r['tipe_data'],
                    'satuan_data'      => $r['satuan_data'],
                    'tahun_mulai_data' => $r['tahun_mulai_data'],
                    'frekuensi'        => $r['frekuensi_penerbitan'],
                    'tahun_metadata'   => $r['tahun_metadata'] ?? '-',
                    'produsen'         => $produsenNama,
                    'tag'              => $r['tag'],
                    'exists_in_db'     => isset($existingInDb[$key]),
                ];

                $rowNum++;
            }
            return response()->json([
                'success'      => true,
                'total_rows'   => count($valid) + count($skipped),
                'valid'        => count($valid),
                'new'          => count(array_filter($valid, fn($r) => !$r['exists_in_db'])),
                'dup_db'       => count(array_filter($valid, fn($r) => $r['exists_in_db'])),
                'skipped'      => count($skipped),
                'rows'         => $valid,
                'skipped_rows' => $skipped,
                'produsen_errors_count' => count($produsenErrors),
                'produsen_errors' => $produsenErrors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'file'                => 'required|file|mimes:xlsx,xls|max:20480',
            'skip_existing'       => 'nullable|boolean',
            'produsen_default_id' => 'nullable|exists:produsen_data,produsen_id',
        ]);

        $filePath          = $request->file('file')->getRealPath();
        $skipExisting      = $request->boolean('skip_existing', true);
        $defaultProdusenId = $request->input('produsen_default_id');
        $userId            = Auth::user()->user_id;
        $now               = now()->toDateTimeString();

        try {
            $existingInDb = Metadata::pluck('nama')
                ->map(fn($n) => $this->dedupKey($n))
                ->flip()->all();

            $totalRows = $this->countExcelRows($filePath);

            $seen     = [];
            $inserted = 0;
            $skipped  = 0;
            $toInsert = [];

            // pre-check removed; we'll validate produsen existence per-row during processing

            $errorRows = [];

            DB::transaction(function () use (
                $filePath, $skipExisting, $defaultProdusenId,
                $userId, $now, $totalRows,
                &$existingInDb,
                &$seen, &$inserted, &$skipped, &$toInsert,
                &$errorRows
            ) {
                for ($startRow = 2; $startRow <= $totalRows; $startRow += self::READ_CHUNK) {
                    $endRow = min($startRow + self::READ_CHUNK - 1, $totalRows);

                    $reader = IOFactory::createReaderForFile($filePath);
                    $reader->setReadFilter(new ChunkReadFilter($startRow, $endRow));
                    $reader->setReadDataOnly(true);

                    $spreadsheet = $reader->load($filePath);
                    $chunkRows   = $spreadsheet->getActiveSheet()
                                               ->toArray(null, true, true, false);

                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet, $reader);

                    if (!empty($chunkRows) && $chunkRows[0][0] !== null && !is_numeric($chunkRows[0][0])) {
                        array_shift($chunkRows);
                    }

                    $rowIndex = $startRow;
                    foreach ($chunkRows as $raw) {
                        if (empty(array_filter($raw, fn($v) => $v !== null && $v !== ''))) { $rowIndex++; continue; }

                        $r   = $this->parseRow($raw);
                        $key = $this->dedupKey($r['nama']);

                        if (isset($seen[$key])) { $skipped++; $rowIndex++; continue; }
                        $seen[$key] = true;

                        if ($skipExisting && isset($existingInDb[$key])) { $skipped++; $rowIndex++; continue; }

                        // Resolve produsen input: accept numeric id or produsen name
                        $produsenId = null;
                        $pmVal = $r['sumber_metadata_pertama'] ?? null;
                        if (is_numeric($pmVal)) {
                            $produsenId = (int) $pmVal;
                        } elseif (is_string($pmVal) && trim($pmVal) !== '') {
                            $p = ProdusenData::whereRaw('LOWER(nama_produsen) = ?', [strtolower(trim($pmVal))])->first();
                            if ($p) $produsenId = $p->produsen_id;
                        }

                        // Validate produsen exists; fallback to defaultProdusenId if provided and valid
                        $validProdusen = false;
                        if ($produsenId && ProdusenData::where('produsen_id', $produsenId)->exists()) {
                            $validProdusen = true;
                        }

                        if (! $validProdusen && $defaultProdusenId && ProdusenData::where('produsen_id', $defaultProdusenId)->exists()) {
                            $produsenId = $defaultProdusenId;
                            $validProdusen = true;
                        }

                        if (! $validProdusen) {
                            $skipped++;
                            $errorRows[] = [
                                'row' => $rowIndex,
                                'nama' => $r['nama'] ?? null,
                                'produsen_input' => $pmVal,
                                'reason' => 'Produsen tidak ditemukan',
                            ];
                            $rowIndex++;
                            continue;
                        }

                        $toInsert[] = $this->buildRow($r, $produsenId, $userId, $now);

                        if (count($toInsert) >= self::INSERT_CHUNK) {
                            DB::table('metadata')->insert($toInsert);
                            $inserted += count($toInsert);
                            $toInsert  = [];
                        }
                        $rowIndex++;
                    }

                    unset($chunkRows);
                }

                if (!empty($toInsert)) {
                    DB::table('metadata')->insert($toInsert);
                    $inserted += count($toInsert);
                    $toInsert  = [];
                }
            });

            return response()->json([
                'success'  => true,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'error_count' => count($errorRows),
                'errors'   => $errorRows,
                'message'  => "$inserted metadata berhasil diimport. $skipped baris dilewati.",
                'redirect' => route('metadata.approval'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 422);
        }
    }
    private function countExcelRows(string $filePath): int
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $count       = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);
        return $count;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: parseRow
    // ─────────────────────────────────────────────────────────────
    private function parseRow(array $raw): array
    {
        $r = [];
        foreach (self::COL as $i => $field) {
            $r[$field] = $raw[$i] ?? null;
        }

        foreach (self::STRING_DASH as $field) {
            if (is_null($r[$field]) || trim((string)$r[$field]) === '') {
                $r[$field] = '-';
            }
        }

        if (!empty($r['tag'])) {
            $decoded = json_decode((string)$r['tag'], true);
            if (is_array($decoded)) {
                $r['tag'] = implode(', ', array_map('trim', $decoded));
            }
        }
        if (empty(trim((string)($r['tag'] ?? '')))) $r['tag'] = '-';

        if (!empty($r['tipe_data'])) {
            $r['tipe_data'] = str_ireplace('Angka Numerik', 'Numerik', $r['tipe_data']);
        }

        // ── Klasifikasi: dukung ID numerik ATAU nama klasifikasi ──
        $rawKlasifikasi = $r['klasifikasi_id'];

        if (is_numeric($rawKlasifikasi)) {
            $r['klasifikasi_id'] = (int) $rawKlasifikasi;
        } elseif (is_string($rawKlasifikasi) && trim($rawKlasifikasi) !== '') {
            $r['klasifikasi_id'] = $this->resolveKlasifikasiId(trim($rawKlasifikasi));
        } else {
            $r['klasifikasi_id'] = null;
        }

        return $r;
    }

    private function dedupKey(?string $nama): string
    {
        if (empty($nama)) return '';
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $nama)));
    }


    private function buildRow(array $r, int $produsenId, int $userId, string $now): array
    {
        $groupBy = null;

        if (is_numeric($r['group_by'])) {
            $exists = DB::table('metadata')
                ->where('metadata_id', (int)$r['group_by'])
                ->exists();

            if ($exists) {
                $groupBy = (int)$r['group_by'];
            }
        }

        return [
            'nama'                   => $this->smartNormalizeWilayah($r['nama']),
            'alias'                  => $this->smartNormalizeWilayah($r['alias']),

            'konsep'                 => $this->smartNormalizeWilayah($r['konsep']),
            'definisi'               => $this->smartNormalizeWilayah($r['definisi']),  
            'klasifikasi_id'         => $r['klasifikasi_id'] ?? null,
            'asumsi'                 => (!empty($r['asumsi']) && $r['asumsi'] !== '-') ? $r['asumsi'] : null,

            'metodologi'            => $this->smartNormalizeWilayah($r['metodologi']),
            'penjelasan_metodologi' => $this->smartNormalizeWilayah($r['penjelasan_metodologi']),

            'tipe_data'              => $r['tipe_data'],
            'satuan_data'            => $r['satuan_data'],
            'tahun_mulai_data'       => $r['tahun_mulai_data'],

            'frekuensi_penerbitan'   => $r['frekuensi_penerbitan'],
            'tahun_pertama_rilis'    => is_numeric($r['tahun_pertama_rilis'])       ? (int)$r['tahun_pertama_rilis']       : null,
            'bulan_pertama_rilis'    => is_numeric($r['bulan_pertama_rilis']) ? (int)$r['bulan_pertama_rilis'] : null,
            'tanggal_rilis'          => is_numeric($r['tanggal_rilis'])       ? (int)$r['tanggal_rilis']       : null,

            'sumber_metadata_pertama'            => $produsenId,

            'tahun_metadata'         => is_numeric($r['tahun_metadata']) ? (int)$r['tahun_metadata'] : 2026,

            'tag'                    => $r['tag'],

            'flag_desimal'           => 0,
            'tipe_group'             => is_numeric($r['tipe_group']) ? (int)$r['tipe_group'] : 2,
            'group_by'               => $groupBy,

            'status'                 => Metadata::STATUS_PENDING,
            'date_inputed'           => $now,
            'user_id'                => $userId,
        ];
    }
}