<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Metadata;
use App\Models\Rujukan;
use App\Models\Location;
use App\Models\Waktu;
use App\Models\Tampilan;
use App\Models\IsiTampilan;
use App\Imports\DataImport;
use App\Services\AnomalyDetectionService;
use App\Services\AuditTrailService;
use App\Services\SubscriptionLimitsService;
use App\Services\SubscriptionAccessService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DataController extends Controller
{
    const STATUS_AVAILABLE = 1;
    const STATUS_PENDING   = 0;

    public function __construct(
        private readonly AnomalyDetectionService $detector,
        private readonly AuditTrailService       $auditTrail,
        private readonly WorkflowService         $workflow,
    ) {}

    // ═══════════════════════════════════════════════════════════
    // INDEX
    // ═══════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $hasFilter = $request->hasAny([
            'metadata_id', 'filter_wilayah_id', 'year', 'search', 'template_id'
        ]);

        // ── template_id: hanya proses kalau login ────────────────
        if ($request->filled('template_id') && Auth::check()) {
            $tampilan = Tampilan::where('tampilan_id', $request->template_id)
                ->where('user_id', Auth::id())
                ->first();

            if ($tampilan && $tampilan->filter_params) {
                $fp = $tampilan->filter_params;
                $request->merge(array_filter([
                    'metadata_id'       => $fp['metadata_id']       ?? null,
                    'filter_wilayah_id' => $fp['filter_wilayah_id'] ?? null,
                    'year'              => $fp['year']               ?? null,
                ]));
            }
        }

        $data = null;
        if ($hasFilter) {
            $query = Data::with(['metadata', 'location', 'time', 'user', 'latestAnomaly'])
                ->where('status', Data::STATUS_AVAILABLE);

            if ($request->filled('metadata_id')) {
                $query->where('metadata_id', $request->metadata_id);
            }
            if ($request->filled('filter_wilayah_id')) {
                $query->where('location_id', $request->filter_wilayah_id);
            }
            if ($request->filled('year')) {
                $query->whereHas('time', fn($q) => $q->where('year', $request->year));
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) =>
                    $q->whereHas('metadata', fn($m) => $m->where('nama', 'like', "%$s%"))
                    ->orWhere('text_value', 'like', "%$s%")
                );
            }

            $data = $query->orderBy('date_inputed', 'desc')->paginate(15)->withQueryString();
        }

        $activeTemplateId = (int) $request->input('template_id', 0);
        $metadataList     = Metadata::where('status', 2)->orderBy('nama')->get(['metadata_id', 'nama']);
        $wilayahList      = Location::select('nama_wilayah')->distinct()->orderBy('nama_wilayah')->pluck('nama_wilayah');

        // ── availableTemplates: kosong kalau guest ────────────────
        $availableTemplates = Auth::check()
            ? Tampilan::where('user_id', Auth::id())
                ->withCount('isiTampilan')
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        if (Auth::check()) {
            $limitsService = app(SubscriptionLimitsService::class);
            $lockedIds = $limitsService->getLockedTemplateIds(Auth::user());
            $availableTemplates->each(function ($tmpl) use ($lockedIds) {
                $tmpl->is_locked = $lockedIds->contains($tmpl->tampilan_id);
            });
        }

        $templateUsage = Auth::check()
            ? app(SubscriptionLimitsService::class)->getTemplateCountForUser(Auth::user())
            : 0;
        $templateLimit = Auth::check()
            ? app(\App\Services\SubscriptionLimitsService::class)->getMaxTemplates(Auth::user())
            : 0;

        // ── hasAccess: guest dianggap tidak punya akses penuh ─────\
        $hasAccess = false; // default: tidak punya akses

        if (Auth::check()) {
            $user = Auth::user();

            if ((int) $user->group_id === 3) {
                $hasAccess = app(SubscriptionAccessService::class)->hasActiveAccess($user);
            } else {
                // Admin / Pengelola → selalu punya akses
                $hasAccess = true;
            }
        }

        $pendingCount = Data::where('status', Data::STATUS_PENDING)->count();
        $anomalyCount = Data::needsReview()->count();

        return view('pages.data.index', compact(
            'data', 'metadataList', 'wilayahList',
            'availableTemplates', 'pendingCount', 'anomalyCount',
            'hasFilter', 'activeTemplateId', 'hasAccess',
            'templateUsage', 'templateLimit'
        ));
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX HELPERS (tidak berubah)
    // ═══════════════════════════════════════════════════════════

    public function searchWilayah(Request $request)
    {
        $q = $request->input('q', '');
        $query = Location::select('location_id', 'nama_wilayah')->orderBy('nama_wilayah');
        if ($q !== '') $query->where('nama_wilayah', 'like', "%{$q}%");

        return response()->json(
            $query->get()->map(fn($loc) => ['id' => $loc->location_id, 'path' => $loc->nama_wilayah])
        );
    }

    public function getKecamatan(Request $request)
    {
        $request->validate(['kabupaten' => 'required|string']);
        return response()->json(
            Location::where('kabupaten', $request->kabupaten)
                ->select('kecamatan')->distinct()->orderBy('kecamatan')->pluck('kecamatan')
        );
    }

    public function getDesa(Request $request)
    {
        $request->validate(['kecamatan' => 'required|string']);
        return response()->json(
            Location::where('kecamatan', $request->kecamatan)
                ->select('desa')->distinct()->orderBy('desa')->pluck('desa')
        );
    }

    public function searchMetadata(Request $request)
    {
        $q     = $request->input('q', '');
        $limit = $q === '' ? 200 : 30;
        $query = Metadata::with('klasifikasi')->where('status', 2)->orderBy('nama');
        if ($q !== '') $query->where('nama', 'like', "%{$q}%");

        return response()->json(
            $query->limit($limit)->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data'])
                ->map(fn($item) => [
                    'metadata_id'    => $item->metadata_id,
                    'nama'           => $item->nama,
                    'satuan_data'    => $item->satuan_data,
                    'klasifikasi_id' => $item->klasifikasi_id,
                    'klasifikasi'    => $item->klasifikasi?->nama_klasifikasi,
                ])
        );
    }

    public function searchYear(Request $request)
    {
        $q = $request->input('q', '');
        $query = Waktu::select('year')->distinct();
        if ($q !== '') $query->where('year', 'like', "{$q}%");
        return response()->json($query->orderByDesc('year')->pluck('year'));
    }

    private static function detectLocationLevel(string $nama): string
    {
        $lower = strtolower($nama);
        if (str_contains($lower, 'provinsi'))                                   return 'provinsi';
        if (str_contains($lower, 'kabupaten') || str_contains($lower, 'kota')) return 'kabupaten';
        if (str_contains($lower, 'kecamatan'))                                  return 'kecamatan';
        return 'desa';
    }

    public function getProdusenByRujukan(Request $request)
    {
        $rujukan = Rujukan::select('rujukan_id', 'nama_rujukan', 'produsen_id')
            ->with('produsen:produsen_id,nama_produsen')
            ->where('rujukan_id', $request->rujukan_id)
            ->first();

        if (!$rujukan) {
            return response()->json(['success' => false, 'message' => 'Rujukan tidak ditemukan'], 404);
        }

        return response()->json([
            'success'       => true,
            'produsen_id'   => $rujukan->produsen_id,
            'nama_produsen' => $rujukan->produsen?->nama_produsen ?? '-',
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE
    // ═══════════════════════════════════════════════════════════

    public function create()
    {
        $metadataList = Metadata::select(
            'metadata_id', 'nama', 'tipe_data', 'satuan_data', 'frekuensi_penerbitan', 'flag_desimal'
        )->where('status', 2)->orderBy('nama')->get();

        $rujukanList  = Rujukan::select('rujukan_id', 'nama_rujukan')->orderBy('nama_rujukan')->get();
        $locationList = Location::select('location_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();

        $provinsiList  = $locationList->filter(fn($l) => str_contains(strtolower($l->nama_wilayah), 'provinsi'));
        $kabupatenList = $locationList->filter(fn($l) =>
            str_contains(strtolower($l->nama_wilayah), 'kabupaten') ||
            str_contains(strtolower($l->nama_wilayah), 'kota')
        );
        $kecamatanList = $locationList->filter(fn($l) => str_contains(strtolower($l->nama_wilayah), 'kecamatan'));
        $desaList      = $locationList->filter(fn($l) =>
            !str_contains(strtolower($l->nama_wilayah), 'provinsi') &&
            !str_contains(strtolower($l->nama_wilayah), 'kabupaten') &&
            !str_contains(strtolower($l->nama_wilayah), 'kota') &&
            !str_contains(strtolower($l->nama_wilayah), 'kecamatan')
        );

        $timeList = Waktu::select('time_id', 'decade', 'year', 'semester', 'quarter', 'month')
            ->orderBy('decade', 'desc')->orderBy('year', 'desc')->orderBy('month')->get();

        $timeListJs = $timeList->map(fn($t) => [
            'time_id'  => $t->time_id, 'decade'   => $t->decade,
            'year'     => $t->year,    'semester' => $t->semester,
            'quarter'  => $t->quarter, 'month'    => $t->month,
        ])->values()->toArray();

        $locationListJs = $locationList->map(fn($l) => [
            'location_id'  => (string) $l->location_id,
            'nama_wilayah' => $l->nama_wilayah,
            'level'        => self::detectLocationLevel($l->nama_wilayah),
        ])->values()->toArray();

        return view('pages.data.create', compact(
            'metadataList', 'rujukanList', 'locationList',
            'provinsiList', 'kabupatenList', 'kecamatanList', 'desaList',
            'timeList', 'timeListJs', 'locationListJs'
        ));
    }

    // ═══════════════════════════════════════════════════════════
    // STORE — dengan screening anomali otomatis
    // ═══════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        // Resolve location_id dari level terdalam
        $locationId = $request->desa_id
            ?? $request->kecamatan_id
            ?? $request->kabupaten_id
            ?? $request->provinsi_id;

        $request->merge(['location_id' => $locationId]);

        // Validasi metadata aktif
        $metadata = Metadata::where('metadata_id', $request->metadata_id)
            ->where('status', 2)->first();
        if (!$metadata) {
            return back()->withInput()->withErrors([
                'metadata_id' => 'Metadata tidak aktif atau tidak valid.',
            ]);
        }

        // Validasi rujukan
        if (!Rujukan::where('rujukan_id', $request->rujukan_id)->exists()) {
            return back()->withInput()->withErrors([
                'rujukan_id' => 'Rujukan tidak tersedia.',
            ]);
        }

        $request->validate([
            'metadata_id'  => 'required|integer|exists:metadata,metadata_id',
            'location_id'  => 'required|integer|exists:location,location_id',
            'time_id'      => 'required|integer|exists:time,time_id',
            'rujukan_id'   => 'required|integer|exists:rujukan,rujukan_id',
            'number_value' => 'nullable|numeric',
        ]);

        // Cek duplikat
        $duplicate = Data::where('metadata_id', $request->metadata_id)
            ->where('location_id', $request->location_id)
            ->where('time_id', $request->time_id)
            ->where('rujukan_id', $request->rujukan_id)
            ->first();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('duplicate_warning', [
                'message'         => 'Data dengan kombinasi Metadata, Lokasi, dan Waktu yang sama sudah terdaftar.',
                'existing_id'     => $duplicate->id,
                'existing_status' => $duplicate->status_label,
            ]);
        }

        // ── Simpan data ───────────────────────────────────────
        // Resolve produsen_id from rujukan if not provided explicitly
        $rujukan = Rujukan::where('rujukan_id', $request->rujukan_id)->first();
        $produsenFromRujukan = $rujukan?->produsen_id ?? null;

        $data = Data::create([
            'user_id'         => Auth::user()->user_id,
            'metadata_id'     => $request->metadata_id,
            'location_id'     => $request->location_id,
            'time_id'         => $request->time_id,
            'rujukan_id'      => $request->rujukan_id,
            'produsen_id'     => $request->filled('produsen_id') ? $request->produsen_id : $produsenFromRujukan,
            'number_value'    => $request->number_value,
            'status'          => Data::STATUS_PENDING,
            'workflow_status' => Data::WORKFLOW_DRAFT,
            'date_inputed'    => Carbon::now(),
        ]);

        // ── Audit trail: data dibuat ──────────────────────────
        $this->auditTrail->recordCreated('data', $data->id, [
            'metadata_id'  => $data->metadata_id,
            'location_id'  => $data->location_id,
            'time_id'      => $data->time_id,
            'number_value' => $data->number_value,
        ]);

        // ── Screening anomali otomatis ────────────────────────
        $screenResult = null;
        if ($request->number_value !== null) {
            $data->loadMissing(['metadata', 'time', 'location']);
            $screenResult = $this->detector->screenData($data);
        }

        // ── Redirect dengan feedback ──────────────────────────
        if ($screenResult && $screenResult['anomalies_found']) {
            $count    = count($screenResult['anomalies']);
            $severities = collect($screenResult['anomalies'])
                ->pluck('severity')
                ->unique()
                ->implode(', ');

            return redirect()
                ->route('anomaly.control.index')
                ->with('warning', "Data disimpan. Ditemukan {$count} anomali ({$severities}). Silakan lakukan review.");
        }

        return redirect()
            ->route('data.index')
            ->with('success', 'Data berhasil disimpan dan menunggu verifikasi admin.');
    }

    // ═══════════════════════════════════════════════════════════
    // IMPORT EXCEL — PREVIEW (tidak berubah)
    // ═══════════════════════════════════════════════════════════

    public function previewExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/octet-stream,application/zip|max:10240',
        ], [
            'file_excel.required' => 'File Excel wajib diupload.',
            'file_excel.mimes'    => 'File harus berformat .xlsx atau .xls.',
            'file_excel.max'      => 'Ukuran file maksimal 10MB.',
        ]);
 
        try {
            $path   = $request->file('file_excel')->getRealPath();
            $import = new DataImport();
            $result = $import->preview($path);
 
            // result sudah mengandung 'outliers' dan 'outlier_count' dari DataImport
            return response()->json($result);
 
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file Excel: ' . $e->getMessage(),
            ], 422);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // IMPORT EXCEL — SIMPAN + screening anomali batch
    // ═══════════════════════════════════════════════════════════

    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel'      => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/octet-stream,application/zip|max:10240',
            'skip_duplicates' => 'nullable|boolean',
            // excluded_keys: array of record keys yang user pilih untuk TIDAK diimport
            'excluded_keys'   => 'nullable|array',
            'excluded_keys.*' => 'string',
            'anomaly_keys'    => 'nullable|array',
            'unit_conflict_keys'   => 'nullable|array',
            'unit_conflict_keys.*' => 'string',
            'satuan_resolutions'   => 'nullable|array',
            'satuan_resolutions.*' => 'integer',
        ]);
 
        try {
            $path = $request->file('file_excel')->getRealPath();
 
            // Bangun set key yang dikecualikan dari pilihan user di UI
            $excludedKeys = [];
            foreach ($request->input('excluded_keys', []) as $key) {
                $excludedKeys[$key] = true;
            }

            // Bangun set key outlier yang harus dicatat sebagai anomali
            $anomalyKeys = [];
            $unitConflictKeys = [];
            foreach ($request->input('unit_conflict_keys', []) as $key) {
                $unitConflictKeys[$key] = true;
            }
            foreach ($request->input('anomaly_keys', []) as $key) {
                $anomalyKeys[$key] = true;
            }
 
            $import = new DataImport(
                userId:         Auth::user()->user_id,
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );
 
            $result = $import->import($path, $excludedKeys, $anomalyKeys, $unitConflictKeys);

            if (isset($result['success']) && $result['success'] === false) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'] ?? 'Gagal mengimpor data.',
                        'db_error' => $result['db_error'] ?? null,
                        'error_rows' => $result['error_rows'] ?? [],
                        'invalid_metadata_rows' => $result['invalid_metadata_rows'] ?? [],
                        'invalid_satuan_rows' => $result['invalid_satuan_rows'] ?? [],
                    ], 422);
                }

                return redirect()->back()->withErrors(['file_excel' => $result['message'] ?? 'Gagal mengimpor data.']);
            }

            // Screening anomali untuk data yang baru diimport
            $anomalyStats = ['scanned' => 0, 'anomaliesFound' => 0];
            if ($result['imported'] > 0) {
                $anomalyStats = $this->detector->scanExistingData(batchSize: 100);
            }

            $manualAnomalyCount = $result['anomalies_created'] ?? 0;
            $message = $result['message'];
            if ($manualAnomalyCount > 0) {
                $message .= " {$manualAnomalyCount} outlier dicatat sebagai anomali.";
            }
            if ($anomalyStats['anomaliesFound'] > 0) {
                $message .= " {$anomalyStats['anomaliesFound']} anomali terdeteksi — silakan cek halaman Control.";
            }
 
            $totalAnomalyCount = $manualAnomalyCount + $anomalyStats['anomaliesFound'];
            $redirectToAnomaly = $totalAnomalyCount > 0;

            if ($request->wantsJson()) {
                return response()->json([
                    'success'       => true,
                    'message'       => $message,
                    'anomaly_count' => $totalAnomalyCount,
                    'redirect'      => $redirectToAnomaly
                        ? route('anomaly.control.index')
                        : route('data.index'),
                ]);
            }
 
            return $redirectToAnomaly
                ? redirect()->route('anomaly.control.index')->with('warning', $message)
                : redirect()->route('data.index')->with('success', $message);
 
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor data: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['file_excel' => 'Gagal mengimpor: ' . $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // TEMPLATE EXCEL (tidak berubah)
    // ═══════════════════════════════════════════════════════════

    public function downloadTemplateExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $headers     = [
            'A1' => 'metadata_id', 'B1' => 'location_id',
            'C1' => 'time_id',     'D1' => 'number_value', 'E1' => 'rujukan_id',
        ];
        foreach ($headers as $cell => $val) $sheet->setCellValue($cell, $val);
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['horizontal' => 'center'],
        ]);
        $sheet->setCellValue('A2', 1); $sheet->setCellValue('B2', 1);
        $sheet->setCellValue('C2', 1); $sheet->setCellValue('D2', 100.50);
        $sheet->setCellValue('E2', 1);
        foreach (range('A', 'E') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->setTitle('Template Data');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="template_import_data.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    // APPROVAL — diperbarui pakai WorkflowService
    // ═══════════════════════════════════════════════════════════

    // ── Helper: terapkan semua filter approval (dipakai approval() & bulkApprove()) ──
    private function applyApprovalFilters($query, Request $request)
    {
        if ($request->filled('metadata_id')) {
            $query->where('metadata_id', $request->metadata_id);
        }
        if ($request->filled('filter_lokasi')) {
            $v = $request->filter_lokasi;
            $query->whereHas('location', fn($q) => $q->where('nama_wilayah', 'like', "%{$v}%"));
        }
        if ($request->filled('filter_tahun')) {
            $query->whereHas('time', fn($q) => $q->where('year', $request->filter_tahun));
        }
        if ($request->filled('filter_nilai_min')) {
            $query->where('number_value', '>=', $request->filter_nilai_min);
        }
        if ($request->filled('filter_nilai_max')) {
            $query->where('number_value', '<=', $request->filter_nilai_max);
        }
        if ($request->filled('filter_user')) {
            $v = $request->filter_user;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$v}%"));
        }
        if ($request->filled('filter_tanggal_dari')) {
            $query->whereDate('date_inputed', '>=', $request->filter_tanggal_dari);
        }
        if ($request->filled('filter_tanggal_sampai')) {
            $query->whereDate('date_inputed', '<=', $request->filter_tanggal_sampai);
        }

        if ($request->filled('filter_granularitas')) {
            $g = $request->filter_granularitas;
            $query->whereHas('time', function ($q) use ($g, $request) {
                switch ($g) {
                    case 'dekade':
                        $q->where('day', 0)->where('month', 0)->where('quarter', 0)
                        ->where('semester', 0)->where('year', 0);
                        if ($request->filled('filter_dekade')) {
                            $q->where('decade', $request->filter_dekade);
                        }
                        break;

                    case 'tahunan':
                        $q->where('day', 0)->where('month', 0)->where('quarter', 0)
                        ->where('semester', 0)->where('year', '!=', 0);
                        break;

                    case 'semester':
                        $q->where('day', 0)->where('month', 0)->where('quarter', 0)
                        ->where('semester', '!=', 0);
                        if ($request->filled('filter_sub_periode')) {
                            $q->where('semester', $request->filter_sub_periode);
                        }
                        break;

                    case 'quarter':
                        $q->where('day', 0)->where('month', 0)->where('quarter', '!=', 0);
                        if ($request->filled('filter_sub_periode')) {
                            $q->where('quarter', $request->filter_sub_periode);
                        }
                        break;

                    case 'bulanan':
                        $q->where('day', 0)->where('month', '!=', 0);
                        if ($request->filled('filter_sub_periode')) {
                            $q->where('month', $request->filter_sub_periode);
                        }
                        break;

                    case 'tanggal':
                        $q->where('day', '!=', 0);
                        break;
                }
            });
        }

        if ($request->filled('filter_tanggal_lengkap')) {
            $date = \Carbon\Carbon::parse($request->filter_tanggal_lengkap);
            $query->whereHas('time', fn($q) => $q
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->where('day', $date->day));
        }

        return $query;
    }

    public function approval(Request $request)
    {
        $status = $request->input('status', 0);
        $query  = Data::with(['metadata', 'location', 'time', 'user', 'latestAnomaly'])
            ->where('status', $status);

        $this->applyApprovalFilters($query, $request);

        $data          = $query->orderBy('date_inputed', 'desc')->paginate(20)->withQueryString();
        $metadataList  = Metadata::select('metadata_id', 'nama')->orderBy('nama')->get();
        $pendingCount  = Data::where('status', Data::STATUS_PENDING)->count();
        $approvedCount = Data::where('status', Data::STATUS_AVAILABLE)->count();
        $rejectedCount = Data::where('status', Data::STATUS_REJECTED)->count();

        return view('pages.data.approval', compact(
            'data', 'metadataList', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    public function bulkApprove(Request $request)
    {
        $query = Data::where('status', Data::STATUS_PENDING);
        $this->applyApprovalFilters($query, $request);

        $dataItems = $query->get();
        $count     = 0;

        foreach ($dataItems as $item) {
            $this->workflow->approveData($item);
            $count++;
        }

        return redirect()
            ->route('data.approval', $request->only([
                'metadata_id', 'filter_lokasi', 'filter_tahun', 'filter_granularitas',
                'filter_sub_periode', 'filter_dekade', 'filter_tanggal_lengkap',
                'filter_nilai_min', 'filter_nilai_max', 'filter_user',
                'filter_tanggal_dari', 'filter_tanggal_sampai',
            ]))
            ->with('success', "{$count} data berhasil disetujui.");
    }

    public function approve(Data $datum)
    {
        $this->workflow->approveData($datum);
        return redirect()->back()->with('success',
            "Data #{$datum->id} dari metadata {$datum->metadata->nama} berhasil diverifikasi."
        );
    }

    public function reject(Data $datum)
    {
        $reason = request('reason', 'Ditolak oleh administrator.');
        $this->workflow->rejectData($datum, $reason);
        return redirect()->back()->with('success',
            "Data #{$datum->id} dari metadata {$datum->metadata->nama} ditolak."
        );
    }

    // ═══════════════════════════════════════════════════════════
    // SHOW
    // ═══════════════════════════════════════════════════════════

    public function edit(Data $datum)
    {
        $datum->load(['metadata', 'location', 'time', 'rujukan', 'produsen']);

        $metadataList = Metadata::select(
            'metadata_id', 'nama', 'tipe_data', 'satuan_data', 'frekuensi_penerbitan', 'flag_desimal'
        )->where('status', 2)->orderBy('nama')->get();

        $rujukanList  = Rujukan::select('rujukan_id', 'nama_rujukan')->orderBy('nama_rujukan')->get();
        $locationList = Location::select('location_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        $timeList = Waktu::select('time_id', 'decade', 'year', 'semester', 'quarter', 'month')
            ->orderBy('decade', 'desc')->orderBy('year', 'desc')->orderBy('month')->get();

        return view('pages.data.edit', compact('datum', 'metadataList', 'rujukanList', 'locationList', 'timeList'));
    }

    public function update(Request $request, Data $datum)
    {
        $request->validate([
            'metadata_id'  => 'required|integer|exists:metadata,metadata_id',
            'location_id'  => 'required|integer|exists:location,location_id',
            'time_id'      => 'required|integer|exists:time,time_id',
            'rujukan_id'   => 'required|integer|exists:rujukan,rujukan_id',
            'number_value' => 'nullable|numeric',
        ]);

        $oldValues = $datum->only([
            'metadata_id', 'location_id', 'time_id', 'rujukan_id', 'produsen_id', 'number_value',
        ]);
        // Resolve produsen_id from rujukan if not provided explicitly
        $rujukan = Rujukan::where('rujukan_id', $request->rujukan_id)->first();
        $produsenFromRujukan = $rujukan?->produsen_id ?? null;

        $datum->fill([
            'metadata_id' => $request->metadata_id,
            'location_id' => $request->location_id,
            'time_id' => $request->time_id,
            'rujukan_id' => $request->rujukan_id,
            'produsen_id' => $request->filled('produsen_id') ? $request->produsen_id : $produsenFromRujukan,
            'number_value' => $request->number_value,
            'status' => Data::STATUS_PENDING,
            'workflow_status' => Data::WORKFLOW_DRAFT,
            'reviewer_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $datum->save();

        $this->auditTrail->recordUpdated('data', $datum->id, $oldValues, $datum->only([
            'metadata_id', 'location_id', 'time_id', 'rujukan_id', 'produsen_id', 'number_value',
        ]));

        return redirect()->route('data.show', $datum)->with('success', 'Data berhasil diperbarui dan dikembalikan ke status pending.');
    }

    public function show(Data $datum)
    {
        $datum->load([
            'metadata', 'location', 'time', 'user',
            'anomalies.reviews.reviewer',
            'produsen',
        ]);

        // Audit trail histori untuk tab timeline
        $auditHistory = $this->auditTrail->getHistory('data', $datum->id);

        return view('pages.data.show', compact('datum', 'auditHistory'));
    }

    // ═══════════════════════════════════════════════════════════
    // TEMPLATE TAMPILAN (tidak berubah)
    // ═══════════════════════════════════════════════════════════

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'nama_tampilan'      => 'required|max:100',
            'filter_metadata_id' => 'nullable|exists:metadata,metadata_id',
            'filter_wilayah_id'  => 'nullable|exists:location,location_id',
            'filter_year'        => 'nullable|integer|min:1900|max:2100',
            'data_ids'           => 'nullable|array',
            'data_ids.*'         => 'exists:data,id',
        ]);

        $filterParams = array_filter([
            'metadata_id'       => $request->filter_metadata_id,
            'filter_wilayah_id' => $request->filter_wilayah_id,
            'year'              => $request->filter_year,
        ]);

        $limitsService = app(SubscriptionLimitsService::class);
        if (! $limitsService->canCreateTemplate(Auth::user())) {
            return redirect()->route('data.index')
                ->with('error', 'Batas jumlah template untuk paket Anda telah tercapai.');
        }

        $tampilan = Tampilan::create([
            'nama_tampilan' => $request->nama_tampilan,
            'user_id'       => Auth::user()->user_id,
            'filter_params' => $filterParams ?: null,
        ]);

        if ($request->filled('filter_metadata_id')) {
            IsiTampilan::create([
                'tampilan_id' => $tampilan->tampilan_id,
                'metadata_id' => $request->filter_metadata_id,
            ]);
        }
        if ($request->filled('data_ids')) {
            $tampilan->dataItems()->sync($request->data_ids);
        }

        return redirect()
            ->route('data.index', ['template_id' => $tampilan->tampilan_id])
            ->with('success', "Template \"{$request->nama_tampilan}\" berhasil disimpan.");
    }

    public function deleteTemplate(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) abort(403);
        $tampilan->delete();
        return redirect()->route('data.index')->with('success', 'Template berhasil dihapus.');
    }
}