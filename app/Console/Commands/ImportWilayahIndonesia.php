<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportWilayahIndonesia extends Command
{
    protected $signature = 'import:wilayah
        {--provinsi=* : Kode provinsi tertentu saja, misal: --provinsi=51 --provinsi=52. Kosongkan untuk semua provinsi.}';

    protected $description = 'Import wilayah (provinsi/kabupaten/kecamatan/desa) seluruh Indonesia dari API SIPEDAS';

    protected int $errorCount = 0;

    public function handle()
    {
        $tahun = date('Y');

        $this->info("Mengambil daftar provinsi tahun {$tahun}...");

        $provinsiList = $this->fetchApi('list_pro', ['thn' => $tahun]);

        if (empty($provinsiList)) {
            $this->error('Gagal mengambil daftar provinsi. Cek koneksi atau endpoint API.');
            return self::FAILURE;
        }

        $filterProvinsi = $this->option('provinsi');

        if (!empty($filterProvinsi)) {
            $provinsiList = array_filter(
                $provinsiList,
                fn ($kode) => in_array((string) $kode, $filterProvinsi, true),
                ARRAY_FILTER_USE_KEY
            );
            $this->info('Mode filter: hanya provinsi ' . implode(', ', $filterProvinsi));
        }

        $this->info('Total provinsi yang akan diimport: ' . count($provinsiList));

        foreach ($provinsiList as $kodeProv => $namaProv) {
            $this->importProvinsi($tahun, (string) $kodeProv, $namaProv);
        }

        $this->newLine();
        if ($this->errorCount > 0) {
            $this->warn("⚠ Import selesai dengan {$this->errorCount} kegagalan request. Cek log untuk detail, lalu jalankan ulang command ini (aman diulang / idempotent) untuk melengkapi data yang gagal.");
        } else {
            $this->info('✅ Import wilayah Indonesia selesai tanpa error.');
        }

        return self::SUCCESS;
    }

    protected function importProvinsi(string $tahun, string $kodeProv, string $namaProv): void
    {
        $kodeProv = str_pad($kodeProv, 2, '0', STR_PAD_LEFT);
        $provId   = $kodeProv . '00000000';
        $provNama = 'Provinsi ' . ucwords(strtolower($namaProv));

        DB::table('location')->updateOrInsert(
            ['location_id' => $provId],
            ['nama_wilayah' => $provNama]
        );

        $this->info("✔ {$provNama}");

        $kabupaten = $this->fetchApi('list_kab', [
            'thn' => $tahun,
            'lvl' => 12,
            'pro' => $kodeProv,
        ], 'output');

        foreach ($kabupaten as $kodeKab => $namaKab) {
            $this->importKabupaten($tahun, $kodeProv, (string) $kodeKab, $namaKab);
        }
    }

    protected function importKabupaten(string $tahun, string $kodeProv, string $kodeKab, string $namaKab): void
    {
        $kodeKab = str_pad($kodeKab, 2, '0', STR_PAD_LEFT);
        $kabId   = $kodeProv . $kodeKab . '000000';

        $namaKabFormatted = ucwords(strtolower($namaKab));

        // Standar BPS: kode kabupaten >= 71 berarti "Kota", selain itu "Kabupaten"
        $prefix  = ((int) $kodeKab >= 71) ? 'Kota ' : 'Kabupaten ';
        $kabNama = $prefix . $namaKabFormatted;

        DB::table('location')->updateOrInsert(
            ['location_id' => $kabId],
            ['nama_wilayah' => $kabNama]
        );

        $this->info("  ✔ {$kabNama}");

        $kecamatan = $this->fetchApi('list_kec', [
            'thn' => $tahun,
            'lvl' => 13,
            'pro' => $kodeProv,
            'kab' => $kodeKab,
        ], 'output');

        foreach ($kecamatan as $kodeKec => $namaKec) {
            $this->importKecamatan($tahun, $kodeProv, $kodeKab, (string) $kodeKec, $namaKec);
        }
    }

    protected function importKecamatan(string $tahun, string $kodeProv, string $kodeKab, string $kodeKec, string $namaKec): void
    {
        $kodeKec = str_pad($kodeKec, 3, '0', STR_PAD_LEFT);
        $kecId   = $kodeProv . $kodeKab . $kodeKec . '000';
        $kecNama = 'Kecamatan ' . ucwords(strtolower($namaKec));

        DB::table('location')->updateOrInsert(
            ['location_id' => $kecId],
            ['nama_wilayah' => $kecNama]
        );

        $this->info("    ↳ {$kecNama}");

        $desa = $this->fetchApi('list_des', [
            'thn' => $tahun,
            'pro' => $kodeProv,
            'kab' => $kodeKab,
            'kec' => $kodeKec,
        ]);

        $rows = [];
        foreach ($desa as $kodeDes => $namaDes) {
            $desaId = $kodeProv . $kodeKab . $kodeKec . str_pad($kodeDes, 3, '0', STR_PAD_LEFT);
            $rows[] = [
                'location_id'  => $desaId,
                'nama_wilayah' => 'Desa ' . ucwords(strtolower($namaDes)),
            ];
        }

        // Batch upsert per kecamatan, jauh lebih ringan daripada 1 query per desa
        if (!empty($rows)) {
            DB::table('location')->upsert($rows, ['location_id'], ['nama_wilayah']);

            if (app()->environment('local')) {
                foreach ($rows as $row) {
                    $this->info("       - {$row['nama_wilayah']}");
                }
            }
        }
    }

    /**
     * Panggil endpoint SIPEDAS dengan retry + timeout, dan skip (bukan crash)
     * kalau satu request gagal supaya import provinsi lain tetap lanjut.
     */
    protected function fetchApi(string $endpoint, array $params, ?string $wrapKey = null): array
    {
        try {
            $response = Http::timeout(15)
                ->retry(3, 500)
                ->get("https://sipedas.pertanian.go.id/api/wilayah/{$endpoint}", $params);

            if (!$response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $json = $response->json();

            return $wrapKey ? ($json[$wrapKey] ?? []) : ($json ?? []);
        } catch (\Throwable $e) {
            $this->errorCount++;
            $this->warn("  ✗ Gagal ambil {$endpoint} (" . http_build_query($params) . "): {$e->getMessage()}");
            return [];
        }
    }
}