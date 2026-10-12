<?php

namespace App\Services;

use App\Models\Data;
use App\Models\Location;
use App\Models\Metadata;
// use App\Models\Waktu;
use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;

/**
 * DataExportService
 *
 * Membangun struktur data "pivot" yang dipakai bersama oleh
 * ExcelExport, PDF, dan JSON API:
 *
 *   Baris  = semua kecamatan milik kabupaten asal data
 *   Kolom  = semua tahun (atau periode) unik dari data yang difilter
 *   Nilai  = number_value untuk (kecamatan, tahun) yang ada, '-' jika kosong
 *   Terakhir = baris total kabupaten (jumlah angka saja)
 *
 * Format output (array):
 * [
 *   'metadata'       => Metadata model,
 *   'metadata_fields'=> ordered label/value pairs for the export metadata section,
 *   'kabupaten'      => 'Kabupaten Gianyar',
 *   'produsen'       => 'BPS Kabupaten Gianyar / ...',
 *   'satuan'         => 'Jiwa',
 *   'years'          => [2021, 2022, 2023, 2024, 2025],   // label kolom
 *   'period_type'    => 'tahunan',                         // tahunan|quarter|semester|bulanan
 *   'year_range'     => '2021–2025',
 *   'districts'      => [                                  // baris kecamatan
 *       ['name' => 'Sukawati', 'values' => ['-', 30, 56, 0, 0]],
 *       ...
 *   ],
 *   'totals'         => ['-', 30, 56, 10.67, 27.56],      // baris total
 *   'total_label'    => 'Kabupaten Gianyar',
 * ]
 */
class DataExportService
{
    // ── Peta bulan singkat → nomor ──────────────────────────────
    private const MONTH_MAP = [
        'Jan'=>1,'Feb'=>2,'Mar'=>3,'Apr'=>4,'Mei'=>5,'Jun'=>6,
        'Jul'=>7,'Agu'=>8,'Sep'=>9,'Okt'=>10,'Nov'=>11,'Des'=>12,
    ];

    // ── Peta nomor bulan → nama singkat Bahasa Indonesia ───────
    private const MONTH_LABEL = [
        1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
        7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
    ];

    // ══════════════════════════════════════════════════════════
    // ENTRY POINT
    // ══════════════════════════════════════════════════════════

    /**
     * Bangun payload pivot dari filter yang sama dengan data.index.
     *
     * @param  int         $metadataId  wajib
     * @param  string|null $kabupaten   opsional (jika null: ambil semua)
     * @param  int|null    $year        opsional filter tahun
     * @return array|null  null jika tidak ada data
     */
    public function build(int $metadataId, ?string $kabupaten = null, ?int $year = null): ?array
    {
        // ── 1. Ambil metadata ──────────────────────────────────
        $metadata = Metadata::with(['produsen', 'klasifikasi'])->find($metadataId);
        if (!$metadata) return null;

        // ── 2. Query data dengan filter ────────────────────────
        $query = Data::with(['location', 'time', 'rujukan'])
            ->where('status', Data::STATUS_AVAILABLE)
            ->where('metadata_id', $metadataId);

        if ($kabupaten) {
            $query->whereHas('location', fn($q) => $q->where('kabupaten', $kabupaten));
        }
        if ($year) {
            $query->whereHas('time', fn($q) => $q->where('year', $year));
        }

        $rows = $query->get();
        if ($rows->isEmpty()) return null;

        // ── 3. Tentukan kabupaten (dari data pertama jika tidak difilter) ──
        $kabNama = $kabupaten
            ?? $rows->first()->location?->kabupaten
            ?? 'Kabupaten';

        // ── 4. Ambil SEMUA kecamatan milik kabupaten tersebut ──
        $allDistricts = Location::where('kabupaten', $kabNama)
            ->where('kecamatan', '!=', 'ALL')
            ->select('kecamatan')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan')
            ->toArray();

        // ── 5. Tentukan kolom waktu (label unik, urut) ─────────
        [$years, $periodType] = $this->extractPeriodColumns($rows);

        // ── 6. Buat lookup: kecamatan → tahun_label → nilai ───
        // Jika satu kecamatan punya beberapa desa, ambil yang pertama ada nilainya
        $lookup = [];
        foreach ($rows as $row) {
            $kec   = $row->location?->kecamatan;
            $label = $this->buildPeriodLabel($row->time, $periodType);
            if ($kec && $label && $row->number_value !== null) {
                // Jika belum ada, isi. Jika sudah ada, tambahkan (agregat kecamatan)
                $lookup[$kec][$label] = ($lookup[$kec][$label] ?? 0) + (float)$row->number_value;
            }
        }

        // ── 7. Bangun baris tabel ──────────────────────────────
        $districtRows = [];
        foreach ($allDistricts as $kec) {
            $values = [];
            foreach ($years as $y) {
                $val = $lookup[$kec][$y] ?? null;
                $values[] = $val !== null ? $val : '-';
            }
            $districtRows[] = ['name' => $kec, 'values' => $values];
        }

        // ── 8. Baris total (jumlah semua angka per kolom) ──────
        $totals = [];
        foreach ($years as $yi => $y) {
            $sum     = null;
            $hasData = false;
            foreach ($districtRows as $dr) {
                $v = $dr['values'][$yi];
                if ($v !== '-') {
                    $sum    = ($sum ?? 0) + $v;
                    $hasData = true;
                }
            }
            $totals[] = $hasData ? $sum : '-';
        }

        // ── 9. Rentang tahun ───────────────────────────────────
        $numericYears = array_filter($rows->map(fn($r) => $r->time?->year)->toArray());
        $yearRange    = empty($numericYears)
            ? ''
            : min($numericYears) . '–' . max($numericYears);

        $references = $rows
            ->pluck('rujukan')
            ->filter()
            ->unique('rujukan_id')
            ->map(fn ($reference) => $reference->nama_rujukan)
            ->implode("\n");

        $metadataFields = [
            ['label' => 'Nama Data', 'value' => $metadata->nama],
            ['label' => 'Konsep', 'value' => $metadata->konsep],
            ['label' => 'Definisi', 'value' => $metadata->definisi],
            ['label' => 'Metodologi', 'value' => $metadata->metodologi],
            ['label' => 'Penjelasan Metode', 'value' => $metadata->penjelasan_metodologi],
            ['label' => 'Produsen Data', 'value' => $metadata->produsen?->nama_produsen],
            ['label' => 'Tipe Data', 'value' => $metadata->tipe_data],
            ['label' => 'Frekuensi Penerbitan', 'value' => $metadata->frekuensi_penerbitan],
            ['label' => 'Wilayah (Lokasi Data)', 'value' => $kabNama],
            ['label' => 'Satuan', 'value' => $metadata->satuan_data],
            ['label' => 'Klasifikasi', 'value' => $metadata->klasifikasi?->nama_klasifikasi],
            ['label' => 'Asumsi', 'value' => $metadata->asumsi],
            ['label' => 'Rujukan', 'value' => $references],
        ];

        $metadataFields = array_map(
            fn(array $field) => [
                'label' => $field['label'],
                'value' => filled($field['value']) ? (string) $field['value'] : '-',
            ],
            $metadataFields
        );

        return [
            'metadata'    => $metadata,
            'metadata_fields' => $metadataFields,
            'kabupaten'   => $kabNama,
            'produsen'    => $metadata->produsen?->nama_produsen ?? '-',
            'satuan'      => $metadata->satuan_data ?? '',
            'years'       => $years,
            'period_type' => $periodType,
            'year_range'  => $yearRange,
            'districts'   => $districtRows,
            'totals'      => $totals,
            'total_label' => $kabNama,
        ];
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    /**
     * Ekstrak label kolom periode unik dari koleksi data
     * dan deteksi jenis periode.
     *
     * @return [string[] $labels, string $periodType]
     */
    private function extractPeriodColumns(Collection $rows): array
    {
        // Kumpulkan semua Waktu unik
        $times = $rows->map(fn($r) => $r->time)
                      ->filter()
                      ->unique('time_id')
                      ->sortBy(fn($t) => [$t->year, $t->quarter, $t->month, $t->day]);

        // Tentukan jenis periode dari data pertama
        $first = $times->first();
        if (!$first) return [[], 'tahunan'];

        $periodType = 'tahunan';
        if ($first->month > 0)                                  $periodType = 'bulanan';
        elseif ($first->quarter > 0 && $first->month === 0)     $periodType = 'quarter';

        // Semester: quarter 1 dan 3, month = 0 → deteksi dari nama kolom asli
        // (tidak bisa dibedakan dari tabel time saja; anggap quarter)

        $labels = [];
        foreach ($times as $t) {
            $labels[] = $this->buildPeriodLabel($t, $periodType);
        }

        return [array_values(array_unique($labels)), $periodType];
    }

    /**
     * Buat label kolom dari model Waktu.
     */
    public function buildPeriodLabel(?object $time, string $periodType): ?string
    {
        if (!$time) return null;

        switch ($periodType) {
            case 'bulanan':
                $bulan = self::MONTH_LABEL[$time->month] ?? 'Bln' . $time->month;
                return $bulan . '_' . $time->year;

            case 'quarter':
                return $time->year . '_Q' . $time->quarter;

            case 'tahunan':
            default:
                return (string)$time->year;
        }
    }

    /**
     * Format nilai untuk tampilan (bilangan Indonesia, '-' tetap '-').
     */
    public static function formatValue($val, int $decimals = 2): string
    {
        if ($val === '-' || $val === null) return '-';
        $n = (float)$val;
        return $n == (int)$n
            ? number_format($n, 0, ',', '.')
            : number_format($n, $decimals, ',', '.');
    }
}