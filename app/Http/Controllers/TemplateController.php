<?php

namespace App\Http\Controllers;

use App\Models\Tampilan;
use App\Models\IsiTampilan;
use App\Models\Klasifikasi;
use App\Models\Metadata;
use App\Models\Location;
use App\Models\Data;
use App\Services\SubscriptionLimitsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // INDEX — daftar template milik user (hanya untuk yang login)
    // ═══════════════════════════════════════════════════════════

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('template.create');
        }

        $templates = Tampilan::where('user_id', Auth::user()->user_id)
            ->withCount('isiTampilan')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.template.index', compact('templates'));
    }

    public function restoreState(Request $request)
    {
        $state = session('wilayah_state', []);
        return response()->json(['success' => true, 'state' => $state]);
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE — form pilih jenis template (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function create()
    {
        return view('pages.template.create');
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE METADATA — form template berbasis metadata (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function createByMetadata()
    {
        $provinsiList = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return view('pages.template.create-metadata', compact('provinsiList'));
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE KLASIFIKASI — form template berbasis klasifikasi (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function createByKlasifikasi()
    {
        $klasifikasiList = Klasifikasi::whereHas('metadata', function ($q) {
        $q->where('status', Metadata::STATUS_ACTIVE);
            })
            ->orderBy('nama_klasifikasi')
            ->pluck('nama_klasifikasi');

        // Hanya provinsi untuk cascade awal
        $provinsiList = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return view('pages.template.create-klasifikasi', compact('klasifikasiList', 'provinsiList'));
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE WILAYAH — form template berbasis wilayah (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function createByWilayah()
    {
        $provinsiList = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return view('pages.template.create-wilayah', compact('provinsiList'));
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — cari metadata (dengan filter opsional)
    // ═══════════════════════════════════════════════════════════

    public function searchMetadata(Request $request)
    {
        $q           = trim($request->input('q', ''));
        $klasifikasi = $request->input('klasifikasi', '');
        $locationId  = $request->input('location_id', '');

        $query = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama');

        if ($q !== '') {
            // Prefix match: "jum" → nama yang DIAWALI "jum" (bisa manfaatin index, jauh lebih cepat)
            $query->where('nama', 'like', "{$q}%");
        }

        if ($klasifikasi !== '') {
            $query->whereHas('klasifikasi', fn($q) => $q->where('nama_klasifikasi', $klasifikasi));
        }

        if ($locationId !== '') {
            $query->whereHas('data', function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                ->where('status', Data::STATUS_AVAILABLE);
            });
        }

        $limit = ($q === '' && $klasifikasi === '' && $locationId === '') ? 20 : 30;

        return response()->json(
            $query->with('klasifikasi')->limit($limit)->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan'])
                ->map(fn($m) => array_merge($m->toArray(), ['klasifikasi' => $m->klasifikasi?->nama_klasifikasi]))
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil metadata preview dengan filter waktu/periode
    // Digunakan oleh Template Metadata
    // ═══════════════════════════════════════════════════════════

    public function fetchMetadataPreview(Request $request)
    {
        $request->validate([
            'metadata_ids'   => 'required|array',
            'metadata_ids.*' => 'integer|exists:metadata,metadata_id',
            'location_ids'   => 'nullable|array',
            'location_ids.*' => 'integer|exists:location,location_id',
            // Filter waktu opsional
            'frekuensi'      => 'nullable|string',
            'year_from'      => 'nullable|integer',
            'year_to'        => 'nullable|integer',
            'period_from'    => 'nullable|integer',
            'period_to'      => 'nullable|integer',
        ]);

        $metadataIds = $request->metadata_ids;
        $locationIds = $request->input('location_ids', []);

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->with('klasifikasi')
            ->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan'])
            ->map(fn($m) => array_merge(
                $m->toArray(),
                ['klasifikasi' => $m->klasifikasi?->nama_klasifikasi]
        ));


        $grouped = [
            'dekade'   => [],
            'tahunan'  => [],
            'semester' => [],
            'kuartal'  => [],
            'bulanan'  => [],
        ];

        // foreach ($metadataList as $m) {
        //     $freq = strtolower($m->frekuensi_penerbitan);
        //     if (!isset($grouped[$freq])) continue;

        //     // Cek apakah ada data dengan filter waktu (jika diberikan)
        //     $hasData = $this->checkMetadataHasDataInPeriod(
        //         $m->metadata_id,
        //         $locationIds,
        //         $request
        //     );

        //     if (!$hasData) continue;

        //     $item = $m->toArray();

        //     if (!empty($locationIds)) {
        //         $item['locations'] = Location::whereIn('location_id', $locationIds)
        //             ->select('location_id', 'nama_wilayah')
        //             ->get()
        //             ->map(fn($l) => [
        //                 'location_id'  => $l->location_id,
        //                 'nama_wilayah' => $l->nama_wilayah,
        //                 'has_children' => $this->hasChildrenWithData($l->location_id, $m->metadata_id),
        //             ])->toArray();
        //     } else {
        //         $item['locations'] = [];
        //     }

        //     $grouped[$freq][] = $item;
        // }
        foreach ($metadataList as $m) {
            $freq = strtolower($m['frekuensi_penerbitan'] ?? '');
            if (!isset($grouped[$freq])) continue;

            $hasData = $this->checkMetadataHasDataInPeriod(
                $m['metadata_id'],
                $locationIds,
                $request
            );

            if (!$hasData) continue;

            $item = $m; // sudah array, tidak perlu toArray()

            if (!empty($locationIds)) {
                $item['locations'] = Location::whereIn('location_id', $locationIds)
                    ->select('location_id', 'nama_wilayah')
                    ->get()
                    ->map(fn($l) => [
                        'location_id'  => $l->location_id,
                        'nama_wilayah' => $l->nama_wilayah,
                        'has_children' => $this->hasChildrenWithData($l->location_id, $m['metadata_id']),
                    ])->toArray();
            } else {
                $item['locations'] = [];
            }

            $grouped[$freq][] = $item;
        }

                return response()->json([
                    'success' => true,
                    'grouped' => $grouped,
                ]);
            }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil metadata berdasarkan klasifikasi + location + periode
    // ═══════════════════════════════════════════════════════════

    public function fetchByKlasifikasi(Request $request)
    {
        $request->validate([
            'klasifikasi'    => 'required|string',
            'location_ids'   => 'nullable|array',
            'location_ids.*' => 'integer|exists:location,location_id',
            'frekuensi'      => 'nullable|string',
            'year_from'      => 'nullable|integer',
            'year_to'        => 'nullable|integer',
            'period_from'    => 'nullable|integer',
            'period_to'      => 'nullable|integer',
        ]);

        $klasifikasi = $request->klasifikasi;
        $locationIds = array_map('intval', $request->input('location_ids', []));

        $query = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->whereHas('klasifikasi', fn($q) => $q->where('nama_klasifikasi', $klasifikasi))
            ->orderBy('nama');

        if (!empty($locationIds)) {
            $query->whereHas('data', function ($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds)
                ->where('status', Data::STATUS_AVAILABLE);
            });
        }

        $metadataList = $query->with('klasifikasi')->get([
            'metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan'
        ]);

        $locationMap = !empty($locationIds)
            ? Location::whereIn('location_id', $locationIds)
                ->pluck('nama_wilayah', 'location_id')
            : collect();

        $rows = [];
        $grouped = [
            'dekade'   => [],
            'tahunan'  => [],
            'semester' => [],
            'kuartal'  => [],
            'bulanan'  => [],
        ];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);

            if (!isset($grouped[$freq])) {
                continue;
            }

            // Jika tidak pilih wilayah → tampilkan global
            if (empty($locationIds)) {
                $row = [
                    'metadata_id'          => $m->metadata_id,
                    'nama'                 => $m->nama,
                    'klasifikasi'          => $m->klasifikasi?->nama_klasifikasi,
                    'satuan_data'          => $m->satuan_data,
                    'frekuensi_penerbitan' => $m->frekuensi_penerbitan,
                    'location_id'          => null,
                    'nama_wilayah'         => 'Semua Wilayah',
                    'has_children'         => false,
                    'depth'                => 0,
                ];

                $rows[] = $row;
                $grouped[$freq][] = $row;
                continue;
            }

            // Jika pilih wilayah → buat metadata × wilayah
            foreach ($locationIds as $locId) {
                $checkQuery = Data::where('metadata_id', $m->metadata_id)
                    ->where('location_id', $locId)
                    ->where('status', Data::STATUS_AVAILABLE);

                $checkQuery = $this->applyTimeFilter($checkQuery, $request);

                if (!$checkQuery->exists()) {
                    continue;
                }

                $row = [
                    'metadata_id'          => $m->metadata_id,
                    'nama'                 => $m->nama,
                    'klasifikasi'          => $m->klasifikasi,
                    'satuan_data'          => $m->satuan_data,
                    'frekuensi_penerbitan' => $m->frekuensi_penerbitan,
                    'location_id'          => $locId,
                    'nama_wilayah'         => $locationMap[$locId] ?? '-',
                    'has_children'         => $this->hasChildrenWithData($locId, $m->metadata_id),
                    'depth'                => 0,
                ];

                $rows[] = $row;
                $grouped[$freq][] = $row;
            }
        }

        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'grouped' => $grouped,
            'total'   => count($rows),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil metadata berdasarkan wilayah + periode
    // Mengembalikan flat rows: setiap metadata × setiap lokasi
    // yang punya data, beserta flag has_children per baris
    // ═══════════════════════════════════════════════════════════

    public function fetchByWilayah(Request $request)
    {
        $request->validate([
            'location_ids'   => 'required|array|min:1',
            'location_ids.*' => 'integer|exists:location,location_id',
            'frekuensi'      => 'nullable|string',
            'year_from'      => 'nullable|integer',
            'year_to'        => 'nullable|integer',
            'period_from'    => 'nullable|integer',
            'period_to'      => 'nullable|integer',
        ]);

        $locationIds = array_map('intval', $request->location_ids);

        // Bangun query data dengan filter waktu
        $dataQuery = Data::whereIn('location_id', $locationIds)
            ->where('status', Data::STATUS_AVAILABLE);

        $dataQuery = $this->applyTimeFilter($dataQuery, $request);

        $metadataIds = $dataQuery->distinct()->pluck('metadata_id')->toArray();

        if (empty($metadataIds)) {
            return response()->json([
                'success' => true,
                'rows'    => [],
                'grouped' => ['dekade' => [], 'tahunan' => [], 'semester' => [], 'kuartal' => [], 'bulanan' => []],
                'total'   => 0,
            ]);
        }

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama')
            ->with('klasifikasi')->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan']);

        $locationMap = Location::whereIn('location_id', $locationIds)
            ->pluck('nama_wilayah', 'location_id');

        // Bangun flat rows: metadata × lokasi yang benar-benar punya data
        $rows    = [];
        $grouped = ['dekade' => [], 'tahunan' => [], 'semester' => [], 'kuartal' => [], 'bulanan' => []];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);

            foreach ($locationIds as $locId) {
                $checkQuery = Data::where('metadata_id', $m->metadata_id)
                    ->where('location_id', $locId)
                    ->where('status', Data::STATUS_AVAILABLE);

                $checkQuery = $this->applyTimeFilter($checkQuery, $request);

                if (!$checkQuery->exists()) continue;

                $row = [
                    'metadata_id'          => $m->metadata_id,
                    'nama'                 => $m->nama,
                    'klasifikasi'          => $m->klasifikasi?->nama_klasifikasi,
                    'satuan_data'          => $m->satuan_data,
                    'frekuensi_penerbitan' => $m->frekuensi_penerbitan,
                    'location_id'          => $locId,
                    'nama_wilayah'         => $locationMap[$locId] ?? '-',
                    'has_children'         => $this->hasChildrenWithData($locId, $m->metadata_id),
                    'depth'                => 0,
                ];

                $rows[] = $row;

                if (isset($grouped[$freq])) {
                    $grouped[$freq][] = $row;
                }
            }
        }

        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'grouped' => $grouped,
            'total'   => count($rows),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil direct children dari satu lokasi untuk satu metadata
    // ═══════════════════════════════════════════════════════════

    public function getChildLocations(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'metadata_id' => 'required|integer',
        ]);

        $locationId = (string) $request->location_id;
        $metadataId = (int) $request->metadata_id;

        $childLevel = $this->getDirectChildLevel($locationId);

        if (!$childLevel) {
            return response()->json(['children' => []]);
        }

        // Suffix yang HARUS dimiliki oleh child langsung (bukan turunan lebih dalam)
        // Provinsi  (PP00000000) → child = Kabupaten (PPKK000000): suffix 6 nol, bukan 8 nol
        // Kabupaten (PPKK000000) → child = Kecamatan (PPKKKK0000): suffix 4 nol, bukan 6 nol
        // Kecamatan (PPKKKK0000) → child = Desa (PPKKKKxxxx): tidak ada suffix 4 nol
        $childSuffix = match ($childLevel) {
            'kabupaten' => ['suffix' => '000000', 'not_suffix' => '00000000'],
            'kecamatan' => ['suffix' => '0000',   'not_suffix' => '000000'],
            'desa'      => ['suffix' => null,      'not_suffix' => '0000'],
            default     => null,
        };

        if (!$childSuffix) {
            return response()->json(['children' => []]);
        }

        $prefix = $this->getLocationPrefix($locationId);

        // Cari semua location_id anak langsung yang punya data untuk metadata ini
        $childLocQuery = Location::where('location_id', 'like', $prefix . '%')
            ->where('location_id', '!=', $locationId);

        if ($childSuffix['suffix']) {
            $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) = ?",
                [strlen($childSuffix['suffix']), $childSuffix['suffix']]);
        }

        $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) != ?",
            [strlen($childSuffix['not_suffix']), $childSuffix['not_suffix']]);

        $candidateIds = $childLocQuery->pluck('location_id')->toArray();

        if (empty($candidateIds)) {
            return response()->json(['children' => []]);
        }

        // Filter hanya yang benar-benar punya data untuk metadata ini
        $hasDataIds = Data::where('metadata_id', $metadataId)
            ->where('status', Data::STATUS_AVAILABLE)
            ->whereIn('location_id', $candidateIds)
            ->distinct()
            ->pluck('location_id')
            ->toArray();

        if (empty($hasDataIds)) {
            return response()->json(['children' => []]);
        }

        $children = Location::whereIn('location_id', $hasDataIds)
            ->select('location_id', 'nama_wilayah')
            ->orderBy('nama_wilayah')
            ->get()
            ->map(fn($l) => [
                'location_id'  => (int) $l->location_id,
                'nama_wilayah' => $l->nama_wilayah,
                'has_children' => $this->hasChildrenWithData($l->location_id, $metadataId),
            ]);

        return response()->json(['children' => $children]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Provinsi
    // ═══════════════════════════════════════════════════════════

    public function getProvinsi(Request $request)
    {
        $q = $request->input('q', '');

        $query = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Kabupaten dari Provinsi
    // ═══════════════════════════════════════════════════════════

    public function getKabupaten(Request $request)
    {
        $request->validate(['provinsi_id' => 'required']);

        $prefix = substr((string) $request->provinsi_id, 0, 2);
        $q = $request->input('q', '');

        $query = Location::query()
            ->where('location_id', 'like', $prefix . '%')
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 6) = '000000'")
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 8) != '00000000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Kecamatan dari Kabupaten
    // ═══════════════════════════════════════════════════════════

    public function getKecamatan(Request $request)
    {
        $request->validate(['kabupaten_id' => 'required']);

        $prefix = substr((string) $request->kabupaten_id, 0, 4);
        $q = $request->input('q', '');

        $query = Location::query()
            ->where('location_id', 'like', $prefix . '%')
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 4) = '0000'")
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 6) != '000000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Desa dari Kecamatan
    // ═══════════════════════════════════════════════════════════

    public function getDesa(Request $request)
    {
        $request->validate(['kecamatan_id' => 'required']);

        $prefix = substr((string) $request->kecamatan_id, 0, 6);
        $q = $request->input('q', '');

        $query = Location::query()
            ->where('location_id', 'like', $prefix . '%')
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 4) != '0000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // STORE — simpan template ke DB (harus login)
    // atau kembalikan JSON jika guest (disimpan di localStorage)
    // ═══════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'nama_tampilan'          => 'required|string|max:100',
            'jenis_template'         => 'required|in:metadata,klasifikasi,wilayah',
            'metadata_ids'           => 'required|array|min:1',
            'metadata_ids.*'         => 'integer|exists:metadata,metadata_id',
            // location_ids lama (flat) – masih diterima untuk backward compat
            'location_ids'           => 'nullable|array',
            'location_ids.*'         => 'integer|exists:location,location_id',
            // per-metadata location map (JSON string)
            'metadata_location_ids'  => 'nullable|string',
            'urutan_by'              => 'nullable|array',
            'urutan_by.*'            => 'in:klasifikasi,wilayah',
        ]);
    
        // Decode peta lokasi per metadata (JSON string → array assoc)
        $metaLocMap = [];
        if ($request->filled('metadata_location_ids')) {
            $decoded = json_decode($request->input('metadata_location_ids'), true);
            if (is_array($decoded)) {
                $metaLocMap = $decoded;
            }
        }
    
        // Fallback: kalau tidak ada metadata_location_ids tapi ada location_ids flat,
        // terapkan lokasi yang sama ke semua metadata (backward compat).
        $flatLocationIds = $request->input('location_ids', []);
    
        // Jika tidak login → kembalikan data untuk disimpan di localStorage
        if (!Auth::check()) {
            $templateData = [
                'nama_tampilan'         => $request->nama_tampilan,
                'jenis_template'        => $request->jenis_template,
                'metadata_ids'          => $request->metadata_ids,
                'metadata_location_ids' => $metaLocMap ?: null,
                'location_ids'          => $flatLocationIds,
                'urutan_by'             => $request->input('urutan_by', []),
                'created_at'            => now()->toIso8601String(),
            ];
    
            return response()->json([
                'success'       => true,
                'storage'       => 'local',
                'message'       => "Template \"{$request->nama_tampilan}\" disimpan di browser Anda.",
                'template_data' => $templateData,
                'redirect'      => route('data.index'),
            ]);
        }
    
        $limitsService = app(SubscriptionLimitsService::class);
        if (! $limitsService->canCreateTemplate(Auth::user())) {
            $maxTemplates = $limitsService->getMaxTemplates(Auth::user());

            return redirect()->route('data.index')
                ->with('error', "Anda telah mencapai batas maksimal {$maxTemplates} template. Hapus template lama atau upgrade paket untuk menambah kapasitas.");
        }

        // User login → simpan ke DB
        $urutanBy  = $request->input('urutan_by', []);
        $urutanStr = count($urutanBy) === 2 ? 'keduanya' : ($urutanBy[0] ?? null);
    
        $filterParams = array_filter([
            'jenis_template' => $request->jenis_template,
            'urutan_by'      => $urutanStr,
            'klasifikasi'    => $request->input('klasifikasi', null),
        ], fn($v) => $v !== null && $v !== [] && $v !== '');
    
        $tampilan = Tampilan::create([
            'nama_tampilan' => $request->nama_tampilan,
            'user_id'       => Auth::user()->user_id,
            'filter_params' => $filterParams ?: null,
        ]);
    
        // Simpan isi template — satu baris per metadata, beserta location_ids-nya
        foreach ($request->metadata_ids as $metadataId) {
            // Prioritas: per-metadata map → flat location_ids → null
            if (!empty($metaLocMap) && array_key_exists((string) $metadataId, $metaLocMap)) {
                $locIds = array_map('intval', (array) $metaLocMap[(string) $metadataId]);
                $locIds = array_filter($locIds); // buang 0/null
                $locIds = array_values($locIds);
            } elseif (!empty($flatLocationIds)) {
                $locIds = array_map('intval', $flatLocationIds);
            } else {
                $locIds = [];
            }
    
            IsiTampilan::create([
                'tampilan_id'  => $tampilan->tampilan_id,
                'metadata_id'  => $metadataId,
                'location_ids' => !empty($locIds) ? $locIds : null,
            ]);
        }
    
        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'storage'     => 'database',
                'message'     => "Template \"{$request->nama_tampilan}\" berhasil disimpan.",
                'tampilan_id' => $tampilan->tampilan_id,
                'redirect'    => route('data.index', ['template_id' => $tampilan->tampilan_id]),
            ]);
        }
    
        return redirect()
            ->route('data.index', ['template_id' => $tampilan->tampilan_id])
            ->with('success', "Template \"{$request->nama_tampilan}\" berhasil disimpan.");
    }

    // ═══════════════════════════════════════════════════════════════
    // SHOW GRAFIK — halaman visualisasi grafik
    // ═══════════════════════════════════════════════════════════════

    public function showGrafik(Request $request)
    {
        $metadataId = $request->input('metadata_id');
        $locationId = $request->input('location_id');

        $metadata = Metadata::where('metadata_id', $metadataId)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->firstOrFail();

        // Support special location_id = 0 as "Indonesia" (may be stored as sentinel)
        if ((string) $locationId === '0' || $locationId === 0) {
            $location = (object) ['location_id' => 0, 'nama_wilayah' => 'Indonesia'];
        } else {
            $location = Location::find($locationId);
        }

        // Ambil sumber dari data-rujukan_id-produsen_id, BUKAN dari metadata->produsen_id
        $dataRow = Data::where('metadata_id', $metadataId)
            ->where('location_id', $locationId)
            ->where('status', Data::STATUS_AVAILABLE)
            ->whereNotNull('rujukan_id')
            ->with('rujukan.produsen')
            ->latest('date_inputed')
            ->first();

        $rujukan = $dataRow?->rujukan?->produsen;

        return view('pages.template.grafik', compact('metadata', 'location', 'rujukan'));
    }

    /**
     * AJAX — ambil tahun & periode yang tersedia untuk template + frekuensi
     * GET /template-tampilan/available-periods
     * Query params: tampilan_id, frekuensi
     */
    private function guardLockedTemplate(Tampilan $tampilan): void
    {
        $limitsService = app(SubscriptionLimitsService::class);

        if ($limitsService->isTemplateLocked($tampilan)) {
            if (request()->expectsJson()) {
                abort(response()->json([
                    'success' => false,
                    'locked'  => true,
                    'message' => 'Template ini terkunci karena melebihi kuota paket Anda. Berlangganan untuk membuka kembali.',
                ], 403));
            }

            abort(403, 'Template ini terkunci karena melebihi kuota paket Anda. Silakan berlangganan untuk membuka kembali.');
        }
    }

    public function getAvailablePeriods(Request $request)
    {
        $request->validate([
            'tampilan_id' => 'required|integer|exists:tampilan,tampilan_id',
            'frekuensi'   => 'required|string|in:10tahunan,5tahunan,tahunan,semesteran,kuartal,bulanan',
        ]);
    
        // ── 1. Ambil tampilan (tanpa filter user_id untuk robustness,
        //       sudah dilindungi middleware IsLogin) ────────────────
        $tampilan = Tampilan::where('tampilan_id', $request->tampilan_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->guardLockedTemplate($tampilan);
    
        $metadataIds = IsiTampilan::where('tampilan_id', $tampilan->tampilan_id)
            ->pluck('metadata_id')
            ->toArray();
    
        if (empty($metadataIds)) {
            return response()->json(['years' => [], 'periods' => []]);
        }
    
        $fp          = $tampilan->filter_params ?? [];
        $locationIds = !empty($fp['location_ids'])
            ? array_map('intval', $fp['location_ids'])
            : [];
    
        // ── 2. Map frekuensi UI → nilai di kolom frekuensi_penerbitan ──
        // PENTING: sesuaikan dengan nilai aktual di tabel metadata Anda!
        // Cek dengan: SELECT DISTINCT frekuensi_penerbitan FROM metadata;
        $frekuensiDbMap = [
            '10tahunan'  => ['dekade'],
            '5tahunan'   => ['dekade'],
            'tahunan'    => ['tahunan'],
            'semesteran' => ['semester', 'semesteran'],   // toleransi variasi nilai DB
            'kuartal'    => ['kuartal'],
            'bulanan'    => ['bulanan'],
        ];
    
        $frekuensiDbValues = $frekuensiDbMap[$request->frekuensi] ?? [];
    
        // ── 3. Metadata yang sesuai frekuensi ─────────────────────
        $relevantMetadataIds = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->where(function ($q) use ($frekuensiDbValues) {
                foreach ($frekuensiDbValues as $i => $val) {
                    $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                    $q->$method('LOWER(TRIM(frekuensi_penerbitan)) = ?', [strtolower(trim($val))]);
                }
            })
            ->pluck('metadata_id')
            ->toArray();
    
        // ── DEBUG: jika masih kosong, kembalikan semua metadata tanpa filter frekuensi
        //    (uncomment baris berikut sementara untuk diagnosa)
        // if (empty($relevantMetadataIds)) {
        //     $relevantMetadataIds = $metadataIds;
        // }
    
        if (empty($relevantMetadataIds)) {
            return response()->json([
                'years'   => [],
                'periods' => [],
                '_debug'  => [
                    'metadata_ids_in_template' => $metadataIds,
                    'frekuensi_requested'      => $request->frekuensi,
                    'frekuensi_db_values'      => $frekuensiDbValues,
                    'message'                  => 'Tidak ada metadata dalam template yang cocok dengan frekuensi ini.',
                ],
            ]);
        }
    
        // ── 4. Ambil time_id dari data yang tersedia ───────────────
        $dataQuery = Data::whereIn('metadata_id', $relevantMetadataIds)
            ->where('status', Data::STATUS_AVAILABLE);
    
        if (!empty($locationIds)) {
            $dataQuery->whereIn('location_id', $locationIds);
        }
    
        $timeIds = $dataQuery->distinct()->pluck('time_id')->toArray();
    
        if (empty($timeIds)) {
            return response()->json([
                'years'   => [],
                'periods' => [],
                '_debug'  => [
                    'relevant_metadata_ids' => $relevantMetadataIds,
                    'location_ids'          => $locationIds,
                    'message'               => 'Metadata ditemukan tapi tidak ada data tersedia untuk kombinasi ini.',
                ],
            ]);
        }
    
        // ── 5. Bangun daftar tahun & periode dari tabel waktu ────────
        $times = \App\Models\Waktu::whereIn('time_id', $timeIds)->get();
    
        $years = $times->pluck('year')
            ->filter(fn($v) => $v !== null && $v > 0)
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    
        $periods = collect();
    
        switch ($request->frekuensi) {
            case '10tahunan':
            case '5tahunan':
                // Gunakan kolom decade jika ada, fallback ke year
                $raw = $times->pluck('decade')->filter(fn($v) => $v !== null && $v > 0);
                $periods = $raw->count()
                    ? $raw->unique()->sort()->values()
                    : collect($years);
                break;
    
            case 'semesteran':
                $periods = $times->pluck('semester')
                    ->filter(fn($v) => $v !== null && $v > 0)
                    ->unique()->sort()->values();
                break;
    
            case 'kuartal':
                $periods = $times->pluck('quarter')
                    ->filter(fn($v) => $v !== null && $v > 0)
                    ->unique()->sort()->values();
                break;
    
            case 'bulanan':
                $periods = $times->pluck('month')
                    ->filter(fn($v) => $v !== null && $v > 0)
                    ->unique()->sort()->values();
                break;
    
            case 'tahunan':
            default:
                // Untuk tahunan, periods = years (period_from/to pakai tahun langsung)
                $periods = collect($years);
                break;
        }
    
        return response()->json([
            'years'   => $years,
            'periods' => $periods->toArray(),
        ]);
    }

    /**
     * AJAX — hitung jumlah data yang tersedia per frekuensi untuk template tertentu
     * GET /template-tampilan/freq-counts?tampilan_id=X
     */
    public function getFreqCounts(Request $request)
    {
        $request->validate([
            'tampilan_id' => 'required|integer|exists:tampilan,tampilan_id',
        ]);

        // Boleh diakses tanpa login (template publik), tapi jika ada user_id, filter per user
        $tampilanQuery = Tampilan::where('tampilan_id', $request->tampilan_id);
        if (Auth::check()) {
            $tampilanQuery->where('user_id', Auth::id());
        }
        $tampilan = $tampilanQuery->firstOrFail();

        $this->guardLockedTemplate($tampilan);

        $metadataIds = IsiTampilan::where('tampilan_id', $tampilan->tampilan_id)
            ->pluck('metadata_id')
            ->toArray();

        if (empty($metadataIds)) {
            return response()->json([
                '10tahunan'  => 0,
                'tahunan'    => 0,
                'semesteran' => 0,
                'kuartal'    => 0,
                'bulanan'    => 0,
                'custom'     => 0,
            ]);
        }

        // Map: frekuensi UI → nilai di kolom frekuensi_penerbitan DB
        $frekuensiDbMap = [
            '10tahunan'  => ['dekade'],
            'tahunan'    => ['tahunan'],
            'semesteran' => ['semester', 'semesteran'],
            'kuartal'    => ['kuartal'],
            'bulanan'    => ['bulanan'],
        ];

        $counts = [];

        foreach ($frekuensiDbMap as $uiFreq => $dbValues) {
            // Cari metadata dalam template yang cocok dengan frekuensi ini
            $relevantIds = Metadata::whereIn('metadata_id', $metadataIds)
                ->where('status', Metadata::STATUS_ACTIVE)
                ->where(function ($q) use ($dbValues) {
                    foreach ($dbValues as $i => $val) {
                        $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                        $q->$method('LOWER(TRIM(frekuensi_penerbitan)) = ?', [strtolower(trim($val))]);
                    }
                })
                ->pluck('metadata_id')
                ->toArray();

            if (empty($relevantIds)) {
                $counts[$uiFreq] = 0;
                continue;
            }

            // Cek apakah ada data aktif untuk metadata ini
            $hasData = Data::whereIn('metadata_id', $relevantIds)
                ->where('status', Data::STATUS_AVAILABLE)
                ->exists();

            $counts[$uiFreq] = $hasData ? count($relevantIds) : 0;
        }

        // Custom selalu tersedia jika ada data apapun
        $counts['custom'] = array_sum($counts) > 0 ? 1 : 0;

        return response()->json($counts);
    }
    
    /**
     * AJAX — ambil data tabel pivot berdasarkan template + filter waktu
     * POST /template-tampilan/table-data
     * Body (JSON): tampilan_id, frekuensi, year_from?, year_to?, period_from?, period_to?, page?
     */
    public function fetchTableData(Request $request)
    {
        $request->validate([
            'tampilan_id' => 'required|integer|exists:tampilan,tampilan_id',
            'frekuensi'   => 'required|string|in:10tahunan,5tahunan,tahunan,semesteran,kuartal,bulanan',
            'year_from'   => 'nullable|integer|min:1900|max:2100',
            'year_to'     => 'nullable|integer|min:1900|max:2100',
            'period_from' => 'nullable|integer',
            'period_to'   => 'nullable|integer',
            'page'        => 'nullable|integer|min:1',
        ]);
    
        $tampilan = Tampilan::where('tampilan_id', $request->tampilan_id)
            ->where('user_id', Auth::user()->user_id)
            ->firstOrFail();

        $this->guardLockedTemplate($tampilan);
    
        // Load isi tampilan beserta location_ids per metadata
        $isiList = IsiTampilan::where('tampilan_id', $tampilan->tampilan_id)->get();
    
        if ($isiList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada metadata dalam template ini.',
                'rows'    => [], 'columns' => [], 'total' => 0,
            ]);
        }
    
        // Map: metadata_id → location_ids (array int | null)
        $metaLocationMap = [];
        foreach ($isiList as $isi) {
            $metaLocationMap[$isi->metadata_id] = $isi->location_ids
                ? array_map('intval', $isi->location_ids)
                : [];
        }
    
        $metadataIds = array_keys($metaLocationMap);
    
        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', 2)
            ->orderBy('nama')
            ->with('klasifikasi')->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan']);
    
        if ($metadataList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada metadata aktif dalam template ini.',
                'rows'    => [], 'columns' => [], 'total' => 0,
            ]);
        }
    
        // ── Bangun kolom waktu ────────────────────────────────
        $columns = $this->buildColumns($request->frekuensi, $request);
    
        if (empty($columns)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kolom periode pada rentang yang dipilih.',
                'rows'    => [], 'columns' => [], 'total' => 0,
            ]);
        }
    
        $timeIdMap  = $this->resolveTimeIds($columns, $request->frekuensi);
        $allTimeIds = array_values(array_filter(array_column($timeIdMap, 'time_id')));
    
        // ── Semua location_id unik di seluruh template (keep 0 for Indonesia)
        $allLocationIds = array_unique(array_merge(...array_values($metaLocationMap)));
        $allLocationIds = array_values(array_filter($allLocationIds, fn($v) => $v !== null && $v !== ''));
    
        $locationMap = Location::pluck('nama_wilayah', 'location_id');
    
        // ── Query data — ambil semua sekaligus ────────────────
        $dataQuery = Data::with(['rujukan:rujukan_id,produsen_id', 'rujukan.produsen:produsen_id,nama_produsen'])
            ->whereIn('metadata_id', $metadataIds)
            ->where('status', Data::STATUS_AVAILABLE);
    
        if (!empty($allTimeIds)) {
            $dataQuery->whereIn('time_id', $allTimeIds);
        }
    
        if (!empty($allLocationIds)) {
            $dataQuery->whereIn('location_id', $allLocationIds);
        }

        // Pastikan diambil berdasarkan tanggal input terbaru dulu sehingga
        // sumber (produsen) yang dipilih adalah yang terbaru, konsisten dengan grafik.
        $dataQuery->orderByDesc('date_inputed');

        $allData = $dataQuery->get(['id', 'metadata_id', 'location_id', 'time_id', 'number_value', 'rujukan_id', 'date_inputed']);
    
        // Index: [metadata_id][location_id][time_id] = value
        $dataIndex    = [];
        $rujukanIndex = [];
    
        
        foreach ($allData as $d) {
            // Karena $allData sudah di-orderByDesc('date_inputed'), kita ingin
            // "first-wins" — baris terbaru harus tetap dipakai jika ada bentrokan.
            if (!isset($dataIndex[$d->metadata_id][$d->location_id][$d->time_id])) {
                $dataIndex[$d->metadata_id][$d->location_id][$d->time_id] = $d->number_value;
            }

            $namaProdusen = $d->rujukan?->produsen?->nama_produsen;
            if ($namaProdusen && !isset($rujukanIndex[$d->metadata_id][$d->location_id][$d->time_id])) {
                $rujukanIndex[$d->metadata_id][$d->location_id][$d->time_id] = $namaProdusen;
            }
        }
    
        // ── Build flat rows — per metadata × lokasi yang dipilih ──
        $rows = [];
    
        foreach ($metadataList as $m) {
            $mId    = $m->metadata_id;
            $locIds = $metaLocationMap[$mId] ?? [];
    
            if (empty($locIds)) {
                // Tidak ada filter lokasi → 1 baris "Semua Wilayah"
                $locsToShow = [null];
            } else {
                $locsToShow = $locIds;
            }
    
            foreach ($locsToShow as $locId) {
                $locNama     = $locId ? ($locationMap[$locId] ?? 'Tidak diketahui') : 'Semua Wilayah';
                $lokasiLevel = $locId ? $this->detectLokasiLevel((string) $locId) : 0;
    
                // Nilai per kolom
                $values = [];
                foreach ($columns as $col) {
                $timeId = $timeIdMap[$col['label']]['time_id'] ?? null;
                
                // SEBELUM (bug): ($timeId && $locId) → null kalau locId null
                // SESUDAH (fix): cek timeId saja, locId null berarti "Semua Wilayah"
                if ($timeId === null) {
                    $values[$col['label']] = null;
                } elseif ($locId !== null) {
                    $values[$col['label']] = $dataIndex[$mId][$locId][$timeId] ?? null;
                } else {
                    // locId null = "Semua Wilayah", ambil nilai pertama yang ada
                    $values[$col['label']] = isset($dataIndex[$mId])
                        ? collect($dataIndex[$mId])
                            ->map(fn($times) => $times[$timeId] ?? null)
                            ->filter(fn($v) => $v !== null)
                            ->first()
                        : null;
                }
            }
    
                // Sumber — pilih produsen yang relevan.
                $rujukan = '-';
                if ($locId && isset($rujukanIndex[$mId][$locId])) {
                    // Untuk lokasi spesifik: ambil produsen pertama yang ada (first-wins)
                    $rujukan = collect($rujukanIndex[$mId][$locId])->filter()->first() ?? '-';
                } elseif (isset($rujukanIndex[$mId])) {
                    // Untuk 'Semua Wilayah' (locId null) — ambil produsen pertama dari
                    // semua lokasi yang punya rujukan, mempertahankan ordering dari rujukanIndex
                    $rujukan = collect($rujukanIndex[$mId])
                        ->map(fn($times) => collect($times)->filter()->first())
                        ->filter()
                        ->first() ?? '-';
                }
    
                $rows[] = [
                    'metadata_id'  => $mId,
                    'nama'         => $m->nama,
                    'lokasi'       => $locNama,
                    'klasifikasi'  => $m->klasifikasi?->nama_klasifikasi,
                    'satuan'       => $m->satuan_data,
                    'location_id'  => $locId,
                    'lokasi_level' => $lokasiLevel,
                    'sumber'       => $rujukan,
                    'values'       => $values,
                ];
            }
        }
    
        // ── Paginate ──────────────────────────────────────────
        $perPage = 20;
        $page    = max(1, (int) ($request->page ?? 1));
        $total   = count($rows);
        $paged   = array_slice($rows, ($page - 1) * $perPage, $perPage);
    
        return response()->json([
            'success'      => true,
            'columns'      => $columns,
            'rows'         => $paged,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / max($perPage, 1)),
            'frekuensi'    => $request->frekuensi,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // GUEST — freq counts tanpa tampilan_id
    // POST /template-tampilan/freq-counts-guest
    // Body: { metadata_ids: [1,2,3], metadata_location_ids: {"1":[10,11]} }
    // ═══════════════════════════════════════════════════════════

    public function getFreqCountsGuest(Request $request)
    {
        $request->validate([
            'metadata_ids'   => 'required|array|min:1',
            'metadata_ids.*' => 'integer|exists:metadata,metadata_id',
            'metadata_location_ids' => 'nullable|string',
        ]);

        $metadataIds = $request->metadata_ids;

        $metaLocMap = [];
        if ($request->filled('metadata_location_ids')) {
            $decoded = json_decode($request->input('metadata_location_ids'), true);
            if (is_array($decoded)) {
                $metaLocMap = $decoded;
            }
        }

        // Kumpulkan semua location_ids yang relevan
        $allLocationIds = [];
        foreach ($metadataIds as $mId) {
            $locs = $metaLocMap[(string)$mId] ?? [];
            foreach ($locs as $lid) {
                $allLocationIds[] = (int)$lid;
            }
        }
        $allLocationIds = array_unique(array_filter($allLocationIds));

        $frekuensiDbMap = [
            '10tahunan'  => ['dekade'],
            'tahunan'    => ['tahunan'],
            'semesteran' => ['semester', 'semesteran'],
            'kuartal'    => ['kuartal'],
            'bulanan'    => ['bulanan'],
        ];

        $counts = [];

        foreach ($frekuensiDbMap as $uiFreq => $dbValues) {
            $relevantIds = Metadata::whereIn('metadata_id', $metadataIds)
                ->where('status', Metadata::STATUS_ACTIVE)
                ->where(function ($q) use ($dbValues) {
                    foreach ($dbValues as $i => $val) {
                        $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                        $q->$method('LOWER(TRIM(frekuensi_penerbitan)) = ?', [strtolower(trim($val))]);
                    }
                })
                ->pluck('metadata_id')
                ->toArray();

            if (empty($relevantIds)) {
                $counts[$uiFreq] = 0;
                continue;
            }

            $dataQuery = Data::whereIn('metadata_id', $relevantIds)
                ->where('status', Data::STATUS_AVAILABLE);

            if (!empty($allLocationIds)) {
                $dataQuery->whereIn('location_id', $allLocationIds);
            }

            $counts[$uiFreq] = $dataQuery->exists() ? count($relevantIds) : 0;
        }

        $counts['custom'] = array_sum($counts) > 0 ? 1 : 0;

        return response()->json($counts);
    }

    // ═══════════════════════════════════════════════════════════
    // GUEST — fetch table data tanpa tampilan_id
    // POST /template-tampilan/table-data-guest
    // Body: { metadata_ids, metadata_location_ids, frekuensi, year_from, ... }
    // ═══════════════════════════════════════════════════════════

    public function fetchTableDataGuest(Request $request)
    {
        $request->validate([
            'metadata_ids'          => 'required|array|min:1',
            'metadata_ids.*'        => 'integer|exists:metadata,metadata_id',
            'metadata_location_ids' => 'nullable|string',
            'frekuensi'             => 'required|string|in:10tahunan,5tahunan,tahunan,semesteran,kuartal,bulanan',
            'year_from'             => 'nullable|integer|min:1900|max:2100',
            'year_to'               => 'nullable|integer|min:1900|max:2100',
            'period_from'           => 'nullable|integer',
            'period_to'             => 'nullable|integer',
            'page'                  => 'nullable|integer|min:1',
        ]);

        // Decode metadata_location_ids
        $metaLocMap = [];
        if ($request->filled('metadata_location_ids')) {
            $decoded = json_decode($request->input('metadata_location_ids'), true);
            if (is_array($decoded)) {
                $metaLocMap = $decoded;
            }
        }

        $metadataIds = $request->metadata_ids;

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama')
            ->with('klasifikasi')
            ->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan']);

        if ($metadataList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada metadata aktif.',
                'rows' => [], 'columns' => [], 'total' => 0,
            ]);
        }

        $columns = $this->buildColumns($request->frekuensi, $request);

        if (empty($columns)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kolom periode pada rentang yang dipilih.',
                'rows' => [], 'columns' => [], 'total' => 0,
            ]);
        }

        $timeIdMap  = $this->resolveTimeIds($columns, $request->frekuensi);
        $allTimeIds = array_values(array_filter(array_column($timeIdMap, 'time_id')));

        // Kumpulkan semua location_ids unik (keep 0 for Indonesia)
        $allLocationIds = [];
        foreach ($metadataIds as $mId) {
            $locs = $metaLocMap[(string)$mId] ?? [];
            foreach ($locs as $lid) {
                $allLocationIds[] = (int)$lid;
            }
        }
        $allLocationIds = array_values(array_unique($allLocationIds));
        $allLocationIds = array_values(array_filter($allLocationIds, fn($v) => $v !== null && $v !== ''));

        $locationMap = Location::pluck('nama_wilayah', 'location_id');

        $dataQuery = Data::with(['rujukan:rujukan_id,produsen_id', 'rujukan.produsen:produsen_id,nama_produsen'])
            ->whereIn('metadata_id', $metadataIds)
            ->where('status', Data::STATUS_AVAILABLE);

        if (!empty($allTimeIds)) {
            $dataQuery->whereIn('time_id', $allTimeIds);
        }
        if (!empty($allLocationIds)) {
            $dataQuery->whereIn('location_id', $allLocationIds);
        }

        // Urutkan berdasarkan tanggal input paling baru agar sumber merefleksikan data terbaru
        $dataQuery->orderByDesc('date_inputed');

        $allData = $dataQuery->get(['id', 'metadata_id', 'location_id', 'time_id', 'number_value', 'rujukan_id', 'date_inputed']);

        $dataIndex    = [];
        $rujukanIndex = [];

        foreach ($allData as $d) {
            if (!isset($dataIndex[$d->metadata_id][$d->location_id][$d->time_id])) {
                $dataIndex[$d->metadata_id][$d->location_id][$d->time_id] = $d->number_value;
            }

            $namaProdusen = $d->rujukan?->produsen?->nama_produsen;
            if ($namaProdusen && !isset($rujukanIndex[$d->metadata_id][$d->location_id][$d->time_id])) {
                $rujukanIndex[$d->metadata_id][$d->location_id][$d->time_id] = $namaProdusen;
            }
        }

        $rows = [];

        foreach ($metadataList as $m) {
            $mId    = $m->metadata_id;
            $locIds = array_map('intval', $metaLocMap[(string)$mId] ?? []);

            $locsToShow = empty($locIds) ? [null] : $locIds;

            foreach ($locsToShow as $locId) {
                $locNama     = $locId ? ($locationMap[$locId] ?? 'Tidak diketahui') : 'Semua Wilayah';
                $lokasiLevel = $locId ? $this->detectLokasiLevel((string)$locId) : 0;

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
                                ->map(fn($times) => $times[$timeId] ?? null)
                                ->filter(fn($v) => $v !== null)
                                ->first()
                            : null;
                    }
                }

                $rujukan = '-';
                if ($locId && isset($rujukanIndex[$mId][$locId])) {
                    $rujukan = collect($rujukanIndex[$mId][$locId])->filter()->first() ?? '-';
                } elseif (isset($rujukanIndex[$mId])) {
                    $rujukan = collect($rujukanIndex[$mId])
                        ->map(fn($times) => collect($times)->filter()->first())
                        ->filter()
                        ->first() ?? '-';
                }

                $rows[] = [
                    'metadata_id'  => $mId,
                    'nama'         => $m->nama,
                    'lokasi'       => $locNama,
                    'klasifikasi'  => $m->klasifikasi?->nama_klasifikasi,
                    'satuan'       => $m->satuan_data,
                    'location_id'  => $locId,
                    'lokasi_level' => $lokasiLevel,
                    'sumber'       => $rujukan,
                    'values'       => $values,
                ];
            }
        }

        $perPage = 20;
        $page    = max(1, (int)($request->page ?? 1));
        $total   = count($rows);
        $paged   = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'success'      => true,
            'columns'      => $columns,
            'rows'         => $paged,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / max($perPage, 1)),
            'frekuensi'    => $request->frekuensi,
        ]);
    }

    private function buildColumns(string $frekuensi, Request $request): array
    {
        $columns = [];

        switch ($frekuensi) {

            // ── 10 Tahunan: 1 kolom per dekade ──────────────────────
            case '10tahunan':
                $from = (int) ($request->period_from ?? $request->year_from ?? 2010);
                $to   = (int) ($request->period_to   ?? $request->year_to   ?? 2020);
                // Bulatkan ke kelipatan 10
                $from = (int) floor($from / 10) * 10;
                $to   = (int) floor($to   / 10) * 10;
                for ($d = $from; $d <= $to; $d += 10) {
                    $columns[] = ['label' => (string) $d, 'meta' => ['decade' => $d]];
                }
                break;

            // ── 5 Tahunan: 1 kolom per periode 5 tahun ───────────────
            case '5tahunan':
                $from = (int) ($request->period_from ?? $request->year_from ?? 2010);
                $to   = (int) ($request->period_to   ?? $request->year_to   ?? 2020);
                $from = (int) floor($from / 5) * 5;
                $to   = (int) floor($to   / 5) * 5;
                for ($d = $from; $d <= $to; $d += 5) {
                    $columns[] = ['label' => (string) $d, 'meta' => ['year_5' => $d]];
                }
                break;

            // ── Tahunan: 1 kolom per tahun ───────────────────────────
            case 'tahunan':
                $from = (int) ($request->period_from ?? $request->year_from ?? 2020);
                $to   = (int) ($request->period_to   ?? $request->year_to   ?? 2021);
                for ($y = $from; $y <= $to; $y++) {
                    $columns[] = ['label' => (string) $y, 'meta' => ['year' => $y]];
                }
                break;

            // ── Semesteran: S1/YYYY, S2/YYYY ─────────────────────────
            case 'semesteran':
                $yFrom = (int) ($request->year_from   ?? 2021);
                $yTo   = (int) ($request->year_to     ?? 2022);
                $pFrom = (int) ($request->period_from ?? 1);
                $pTo   = (int) ($request->period_to   ?? 2);
                for ($y = $yFrom; $y <= $yTo; $y++) {
                    $sStart = ($y === $yFrom) ? $pFrom : 1;
                    $sEnd   = ($y === $yTo)   ? $pTo   : 2;
                    for ($s = $sStart; $s <= $sEnd; $s++) {
                        $columns[] = [
                            'label' => "S{$s}/{$y}",
                            'meta'  => ['year' => $y, 'semester' => $s],
                        ];
                    }
                }
                break;

            // ── Kuartal: Q1/YYYY … Q4/YYYY ───────────────────────────
            case 'kuartal':
                $yFrom = (int) ($request->year_from   ?? 2021);
                $yTo   = (int) ($request->year_to     ?? 2022);
                $pFrom = (int) ($request->period_from ?? 1);
                $pTo   = (int) ($request->period_to   ?? 4);
                for ($y = $yFrom; $y <= $yTo; $y++) {
                    $qStart = ($y === $yFrom) ? $pFrom : 1;
                    $qEnd   = ($y === $yTo)   ? $pTo   : 4;
                    for ($q = $qStart; $q <= $qEnd; $q++) {
                        $columns[] = [
                            'label' => "Q{$q}/{$y}",
                            'meta'  => ['year' => $y, 'quarter' => $q],
                        ];
                    }
                }
                break;

            // ── Bulanan: Jan/YYYY … Des/YYYY ─────────────────────────
            case 'bulanan':
                $yFrom = (int) ($request->year_from   ?? 2021);
                $yTo   = (int) ($request->year_to     ?? 2022);
                $pFrom = (int) ($request->period_from ?? 1);
                $pTo   = (int) ($request->period_to   ?? 12);
                for ($y = $yFrom; $y <= $yTo; $y++) {
                    $mStart = ($y === $yFrom) ? $pFrom : 1;
                    $mEnd   = ($y === $yTo)   ? $pTo   : 12;
                    for ($mo = $mStart; $mo <= $mEnd; $mo++) {
                        $columns[] = [
                            'label' => $this->namaBulan($mo) . "/{$y}",
                            'meta'  => ['year' => $y, 'month' => $mo],
                        ];
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
            $meta = $col['meta'];

            $query = \Illuminate\Support\Facades\DB::table('time');

            switch ($frekuensi) {
                case '10tahunan':
                    // Hanya row dekade murni: year=0, semester=0, quarter=0, month=0
                    $query->where('decade', $meta['decade'])
                        ->where('year',     0)
                        ->where('semester', 0)
                        ->where('quarter',  0)
                        ->where('month',    0);
                    break;

                case 'tahunan':
                    // Hanya row tahunan murni: semester=0, quarter=0, month=0
                    $query->where('year',     $meta['year'])
                        ->where('semester', 0)
                        ->where('quarter',  0)
                        ->where('month',    0);
                    break;

                case 'semesteran':
                    // Hanya row semester murni: quarter=0, month=0
                    $query->where('year',     $meta['year'])
                        ->where('semester', $meta['semester'])
                        ->where('quarter',  0)
                        ->where('month',    0);
                    break;

                case 'kuartal':
                    // Hanya row kuartal murni: month=0
                    $query->where('year',    $meta['year'])
                        ->where('quarter', $meta['quarter'])
                        ->where('month',   0);
                    break;

                case 'bulanan':
                    // Row bulanan: semua kolom terisi
                    $query->where('year',  $meta['year'])
                        ->where('month', $meta['month']);
                    break;
            }

            $timeIds = $query->pluck('time_id')->toArray();

            $result[$col['label']] = [
                'time_id'  => $timeIds[0] ?? null,
                'time_ids' => $timeIds,
            ];
        }

        return $result;
    }

    private function detectLokasiLevel(string $locationId): int
    {
        if (str_ends_with($locationId, '00000000')) return 0; // Provinsi
        if (str_ends_with($locationId, '000000'))   return 1; // Kabupaten
        if (str_ends_with($locationId, '0000'))     return 2; // Kecamatan
        return 3;                                             // Desa
    }
    
    // ─── Private helpers (tambahkan setelah method fetchTableData) ───────────────
    
    private function buildPivotRow(
        Metadata $m,
        ?int $locationId,
        string $lokasiNama,
        array $columns,
        array $dataIndex,
        array $rujukanIndex
    ): array {
        $locKey  = $locationId ?? 0;
        $values  = [];
    
        foreach ($columns as $col) {
            $values[$col['label']] = $dataIndex[$m->metadata_id][$locKey][$col['time_id']] ?? null;
        }

        $rujukan = '-';

        if (isset($rujukanIndex[$m->metadata_id][$locKey])) {
            // ambil salah satu rujukan dari time yang ada
            $rujukan = collect($rujukanIndex[$m->metadata_id][$locKey])
                ->filter()
                ->first() ?? '-';
        }
    
        return [
            'metadata_id' => $m->metadata_id,
            'nama'        => $m->nama,
            'klasifikasi' => $m->klasifikasi,
            'satuan'      => $m->satuan_data,
            'lokasi'      => $lokasiNama,
            'location_id' => $locationId,
            'sumber'      => $rujukan,
            'values'      => $values,
        ];
    }
    
    private function buildTimeLabel(\App\Models\Waktu $t, string $frekuensi): string
    {
        return match ($frekuensi) {
            '10tahunan', '5tahunan' => (string) ($t->decade ?? $t->year),
            'tahunan'               => (string) $t->year,
            'semesteran'            => "S{$t->semester}/{$t->year}",
            'kuartal'               => "Q{$t->quarter}/{$t->year}",
            'bulanan'               => $this->namaBulan((int) $t->month) . "/{$t->year}",
            default                 => (string) $t->year,
        };
    }
    
    private function namaBulan(int $bulan): string
    {
        return ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][$bulan] ?? (string) $bulan;
    }
    // ═══════════════════════════════════════════════════════════
    // DELETE
    // ═══════════════════════════════════════════════════════════

    public function destroy(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $tampilan->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Template berhasil dihapus.']);
        }

        return redirect()->route('data.index')->with('success', 'Template berhasil dihapus.');
    }

     // ═══════════════════════════════════════════════════════════
    // EDIT — halaman form edit template
    // ═══════════════════════════════════════════════════════════
    public function edit(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $this->guardLockedTemplate($tampilan);

        $tampilan->load('isiTampilan.metadata');

        $existingMetadataIds = $tampilan->isiTampilan->pluck('metadata_id')->toArray();

        $existingMetadata = Metadata::whereIn('metadata_id', $existingMetadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->with('klasifikasi')
            ->orderBy('nama')
            ->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan'])
            ->map(fn($m) => array_merge(
                $m->toArray(),
                ['klasifikasi' => $m->klasifikasi?->nama_klasifikasi]
            ));

        $fp     = $tampilan->filter_params ?? [];
        $jenis  = $fp['jenis_template'] ?? 'metadata';

        // ── Map metadata_id => location_ids (per-metadata, dari isi_tampilan) ──
        $metaLocMap = [];
        foreach ($tampilan->isiTampilan as $isi) {
            $metaLocMap[(string) $isi->metadata_id] = $isi->location_ids ?? [];
        }

        // ── Kumpulkan semua location_id unik yang dipakai (untuk badge/info) ──
        $allLocationIds = collect($metaLocMap)->flatten()->unique()->filter()->values()->all();

        $existingLocations = !empty($allLocationIds)
            ? Location::whereIn('location_id', $allLocationIds)
                ->select('location_id', 'nama_wilayah')
                ->get()
            : collect();

        // ── Untuk jenis klasifikasi: ambil nama klasifikasi tersimpan ──
        $selectedKlasifikasi = $fp['klasifikasi'] ?? null;

        // FIX: fallback — baca dari metadata pertama jika fp['klasifikasi'] kosong
        if (!$selectedKlasifikasi && $jenis === 'klasifikasi') {
            $firstMetadata = $tampilan->isiTampilan->first()?->metadata;
            if ($firstMetadata && $firstMetadata->klasifikasi_id) {
                $firstMetadata->load('klasifikasi');
                $selectedKlasifikasi = $firstMetadata->klasifikasi?->nama_klasifikasi;
            }
        }

        // ── Daftar klasifikasi (untuk dropdown, dipakai jenis klasifikasi) ──
        $klasifikasiList = Klasifikasi::whereHas('metadata', function ($q) {
                $q->where('status', Metadata::STATUS_ACTIVE);
            })
            ->orderBy('nama_klasifikasi')
            ->pluck('nama_klasifikasi');

        // ── Semua lokasi (untuk cascade JS, sama seperti create-*) ──
        $allMetadata = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->with('klasifikasi')
            ->orderBy('nama')
            ->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan'])
            ->map(fn($m) => array_merge(
                $m->toArray(),
                ['klasifikasi' => $m->klasifikasi?->nama_klasifikasi]
            ));

        return view('pages.template.edit', compact(
            'tampilan',
            'existingMetadata',
            'existingLocations',
            'allMetadata',
            'fp',
            'jenis',
            'metaLocMap',
            'selectedKlasifikasi',
            'klasifikasiList',
        ));
    }
 
    // ═══════════════════════════════════════════════════════════
    // UPDATE — simpan perubahan template
    // ═══════════════════════════════════════════════════════════
    public function update(Request $request, Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $this->guardLockedTemplate($tampilan);

        $request->validate([
            'nama_tampilan'         => 'required|string|max:100',
            'metadata_ids'          => 'required|array|min:1',
            'metadata_ids.*'        => 'integer|exists:metadata,metadata_id',
            'metadata_location_ids' => 'nullable|string',
            'location_ids'          => 'nullable|array',
            'location_ids.*'        => 'integer|exists:location,location_id',
            'klasifikasi'           => 'nullable|string',
            'urutan_by'             => 'nullable|array',
            'urutan_by.*'           => 'in:klasifikasi,wilayah',
        ]);

        $metaLocMap = [];
        if ($request->filled('metadata_location_ids')) {
            $decoded = json_decode($request->input('metadata_location_ids'), true);
            if (is_array($decoded)) {
                $metaLocMap = $decoded;
            }
        }

        $flatLocationIds = $request->input('location_ids', []);

        // ── Update filter_params: pertahankan jenis_template lama, update klasifikasi/urutan_by jika dikirim ──
        $fp = $tampilan->filter_params ?? [];

        $jenisTemplate = $fp['jenis_template'] ?? null;
        if ($jenisTemplate === 'klasifikasi' && $request->filled('klasifikasi')) {
            $fp['klasifikasi'] = $request->input('klasifikasi');
        } elseif ($request->filled('klasifikasi')) {
            $fp['klasifikasi'] = $request->input('klasifikasi');
        }

        if ($request->has('urutan_by')) {
            $urutanBy  = $request->input('urutan_by', []);
            $fp['urutan_by'] = count($urutanBy) === 2 ? 'keduanya' : ($urutanBy[0] ?? null);
        }

        $tampilan->update([
            'nama_tampilan' => $request->nama_tampilan,
            'filter_params' => $fp ?: null,
        ]);

        // Ganti seluruh isi tampilan
        IsiTampilan::where('tampilan_id', $tampilan->tampilan_id)->delete();

        foreach ($request->metadata_ids as $metadataId) {
            if (!empty($metaLocMap) && array_key_exists((string) $metadataId, $metaLocMap)) {
                $locIds = array_map('intval', (array) $metaLocMap[(string) $metadataId]);
                $locIds = array_filter($locIds);
                $locIds = array_values($locIds);
            } elseif (!empty($flatLocationIds)) {
                $locIds = array_map('intval', $flatLocationIds);
            } else {
                $locIds = [];
            }

            IsiTampilan::create([
                'tampilan_id'  => $tampilan->tampilan_id,
                'metadata_id'  => $metadataId,
                'location_ids' => !empty($locIds) ? $locIds : null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => "Template \"{$tampilan->nama_tampilan}\" berhasil diperbarui.",
                'tampilan_id' => $tampilan->tampilan_id,
                'redirect'    => route('data.index', ['template_id' => $tampilan->tampilan_id]),
            ]);
        }

        return redirect()
            ->route('data.index', ['template_id' => $tampilan->tampilan_id])
            ->with('success', "Template \"{$tampilan->nama_tampilan}\" berhasil diperbarui.");
    }

    // ═══════════════════════════════════════════════════════════
    // SHOW — detail isi template (AJAX untuk panel data index)
    // ═══════════════════════════════════════════════════════════

    public function show(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $this->guardLockedTemplate($tampilan);

        $tampilan->load('isiTampilan.metadata');

        $metadataIds = $tampilan->isiTampilan->pluck('metadata_id')->toArray();
        $fp          = $tampilan->filter_params ?? [];
        $locationIds = $fp['location_ids'] ?? [];

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama')
            ->with('klasifikasi')
            ->get(['metadata_id', 'nama', 'klasifikasi_id', 'satuan_data', 'frekuensi_penerbitan']);

        $grouped = [
            'dekade'   => [],
            'tahunan'  => [],
            'semester' => [],
            'kuartal'  => [],
            'bulanan'  => [],
        ];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);
            if (!isset($grouped[$freq])) continue;

            $item = $m->toArray();
            $item['locations'] = !empty($locationIds)
                ? Location::whereIn('location_id', $locationIds)
                    ->select('location_id', 'nama_wilayah')
                    ->get()
                    ->map(fn($l) => [
                        'location_id'  => $l->location_id,
                        'nama_wilayah' => $l->nama_wilayah,
                        'has_children' => $this->hasChildrenWithData($l->location_id, $m->metadata_id),
                    ])->toArray()
                : [];

            $grouped[$freq][] = $item;
        }

        return response()->json([
            'success'       => true,
            'tampilan_id'   => $tampilan->tampilan_id,
            'nama_tampilan' => $tampilan->nama_tampilan,
            'filter_params' => $fp,
            'grouped'       => $grouped,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil data aktual dengan filter waktu
    // ═══════════════════════════════════════════════════════════

    public function fetchData(Request $request)
    {
        $request->validate([
            'metadata_id'  => 'required|integer|exists:metadata,metadata_id',
            // allow integer including 0 as sentinel for Indonesia
            'location_id'  => 'required|integer',
            'frekuensi'    => 'required|string',
            'year_from'    => 'nullable|integer',
            'year_to'      => 'nullable|integer',
            'period_from'  => 'nullable|integer',
            'period_to'    => 'nullable|integer',
        ]);

        $query = Data::with(['time', 'location'])
            ->where('metadata_id', $request->metadata_id)
            ->where('status', Data::STATUS_AVAILABLE);

        // Jika location_id diberikan dan bukan 0 (sentinel untuk Semua Wilayah/Indonesia),
        // batasi query berdasarkan location_id. Jika location_id == 0 atau kosong,
        // artinya ambil dari semua lokasi (Semua Wilayah).
        $locId = $request->input('location_id');
        if ($locId !== null && (string) $locId !== '' && (int) $locId !== 0) {
            $query->where('location_id', (int) $locId);
        }

        $query = $this->applyTimeFilter($query, $request);

        // Honoring per_page param (client may request large page to fetch all)
        $perPage = max(1, (int) ($request->input('per_page', 10)));
        $page    = max(1, (int) ($request->input('page', 1)));

        // Ambil semua baris yang cocok, urutkan berdasarkan tanggal input terbaru
        // sehingga ketika kita melakukan unique('time_id') kita mendapatkan baris
        // terbaru untuk setiap time_id.
        $all = $query->with('time')
            ->orderByDesc('date_inputed')
            ->get(['id', 'metadata_id', 'location_id', 'time_id', 'number_value', 'date_inputed']);

        // Unique by time_id — first-wins (karena sudah ordered desc)
        $uniqueByTime = $all->unique('time_id')->values();

        // Sort ascending by time so chart shows timeline left→right chronologically.
        $sorted = $uniqueByTime->sortBy(function ($d) {
            $t = $d->time;
            if (!$t) return 0;
            if (!empty($t->decade)) return (int) $t->decade;
            // use composite keys for semester/quarter/month
            if (!empty($t->year) && !empty($t->semester)) return $t->year * 100 + $t->semester;
            if (!empty($t->year) && !empty($t->quarter))  return $t->year * 100 + $t->quarter;
            if (!empty($t->year) && !empty($t->month))    return $t->year * 100 + $t->month;
            return (int) ($t->year ?? 0);
        })->values();

        $total = $sorted->count();

        $paged = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => $paged->all(),
            'total'   => $total,
            'pages'   => (int) ceil($total / $perPage),
            'current' => $page,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Terapkan filter waktu ke query berdasarkan frekuensi.
     * Mendukung dekade, tahunan, semester, kuartal, bulanan.
     */
    private function applyTimeFilter($query, Request $request)
    {
        $frekuensi  = strtolower($request->input('frekuensi', ''));
        $yearFrom   = $request->input('year_from');
        $yearTo     = $request->input('year_to');
        $periodFrom = $request->input('period_from');
        $periodTo   = $request->input('period_to');

        if (!$frekuensi) return $query;

        if ($frekuensi === 'dekade') {
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('decade', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('decade', '<=', $periodTo));
        } elseif ($frekuensi === 'tahunan') {
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('year', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('year', '<=', $periodTo));
        } elseif ($frekuensi === 'semester') {
            if ($yearFrom)   $query->whereHas('time', fn($q) => $q->where('year', '>=', $yearFrom));
            if ($yearTo)     $query->whereHas('time', fn($q) => $q->where('year', '<=', $yearTo));
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('semester', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('semester', '<=', $periodTo));
        } elseif ($frekuensi === 'kuartal') {
            if ($yearFrom)   $query->whereHas('time', fn($q) => $q->where('year', '>=', $yearFrom));
            if ($yearTo)     $query->whereHas('time', fn($q) => $q->where('year', '<=', $yearTo));
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('quarter', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('quarter', '<=', $periodTo));
        } elseif ($frekuensi === 'bulanan') {
            if ($yearFrom)   $query->whereHas('time', fn($q) => $q->where('year', '>=', $yearFrom));
            if ($yearTo)     $query->whereHas('time', fn($q) => $q->where('year', '<=', $yearTo));
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('month', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('month', '<=', $periodTo));
        }

        return $query;
    }

    /**
     * Cek apakah metadata punya data dalam rentang periode yang diminta.
     * Jika tidak ada filter periode, return true (tampilkan semua).
     */
    private function checkMetadataHasDataInPeriod(int $metadataId, array $locationIds, Request $request): bool
    {
        $frekuensi  = strtolower($request->input('frekuensi', ''));
        $yearFrom   = $request->input('year_from');
        $yearTo     = $request->input('year_to');
        $periodFrom = $request->input('period_from');
        $periodTo   = $request->input('period_to');

        // Jika tidak ada filter waktu sama sekali, tampilkan semua
        $hasTimeFilter = $frekuensi && ($yearFrom || $yearTo || $periodFrom || $periodTo);
        if (!$hasTimeFilter) return true;

        $query = Data::where('metadata_id', $metadataId)
            ->where('status', Data::STATUS_AVAILABLE);

        if (!empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        $query = $this->applyTimeFilter($query, $request);

        return $query->exists();
    }

    private function hasChildrenWithData(int $locationId, int $metadataId): bool
    {
        $locStr     = (string) $locationId;
        $childLevel = $this->getDirectChildLevel($locStr);

        if (!$childLevel) return false;

        $childSuffix = match ($childLevel) {
            'kabupaten' => ['suffix' => '000000', 'not_suffix' => '00000000'],
            'kecamatan' => ['suffix' => '0000',   'not_suffix' => '000000'],
            'desa'      => ['suffix' => null,      'not_suffix' => '0000'],
            default     => null,
        };

        if (!$childSuffix) return false;

        $prefix = $this->getLocationPrefix($locStr);

        $childLocQuery = Location::where('location_id', 'like', $prefix . '%')
            ->where('location_id', '!=', $locStr);

        if ($childSuffix['suffix']) {
            $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) = ?",
                [strlen($childSuffix['suffix']), $childSuffix['suffix']]);
        }

        $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) != ?",
            [strlen($childSuffix['not_suffix']), $childSuffix['not_suffix']]);

        $candidateIds = $childLocQuery->pluck('location_id')->toArray();

        if (empty($candidateIds)) return false;

        return Data::where('metadata_id', $metadataId)
            ->where('status', Data::STATUS_AVAILABLE)
            ->whereIn('location_id', $candidateIds)
            ->exists();
    }

    /**
     * Menentukan level anak langsung dari suatu location_id.
     * Format location_id 10 digit: PP KK KKK DDD (misal 5102130005)
     *   Provinsi  (PP00000000) → Kabupaten
     *   Kabupaten (PPKK000000) → Kecamatan
     *   Kecamatan (PPKKKCC0000) → Desa — di sini kita pakai string length
     *
     * Karena location_id adalah bigint, kita cast ke string untuk cek suffix.
     */
    private function getDirectChildLevel(string $locationId): ?string
    {
        $len = strlen($locationId);

        // Provinsi: 10 digit, 8 digit terakhir = '00000000'
        if ($len >= 8 && substr($locationId, -8) === '00000000') {
            return 'kabupaten';
        }

        // Kabupaten: 6 digit terakhir = '000000', bukan 8 nol
        if ($len >= 6 && substr($locationId, -6) === '000000') {
            return 'kecamatan';
        }

        // Kecamatan: 4 digit terakhir = '0000', bukan 6 nol
        if ($len >= 4 && substr($locationId, -4) === '0000') {
            return 'desa';
        }

        return null; // Desa, tidak ada anak
    }

    /**
     * Panjang string location_id untuk level anak:
     *   kabupaten → 10 digit (sama dengan provinsi)  ← PPKK000000
     *   kecamatan → 10 digit                          ← PPKKKCC0000
     *   desa      → 10 digit                          ← PPKKKCCNNN
     *
     * Sebenarnya semua 10 digit, yang membedakan adalah suffix zero-nya.
     * Kita gunakan pendekatan prefix match + suffix check.
     */
    private function getChildLevelLength(string $level): int
    {
        return 10; // Semua location_id 10 digit
    }

    /**
     * Dapatkan prefix yang benar untuk mencari anak lokasi.
     */
    private function getLocationPrefix(string $locationId): string
    {
        $childLevel = $this->getDirectChildLevel($locationId);

        return match ($childLevel) {
            'kabupaten' => substr($locationId, 0, 2),  // PP
            'kecamatan' => substr($locationId, 0, 4),  // PPKK
            'desa'      => substr($locationId, 0, 6),  // PPKKKK (atau sesuai format)
            default     => $locationId,
        };
    }
}