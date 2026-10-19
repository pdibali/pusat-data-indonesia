<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProdusenBulkImport
{
    private const HEADERS = [
        'nama_produsen' => ['nama_produsen', 'nama produsen'],
        'nama_contact_person' => ['nama_contact_person', 'nama contact person', 'contact person'],
        'kontak' => ['kontak', 'contact', 'telepon'],
        'email' => ['email'],
        'alamat' => ['alamat', 'address'],
        'status' => ['status'],
    ];

    public function preview(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $lastRow = $sheet->getHighestDataRow();

        $columns = [];
        for ($column = 1; $column <= $lastColumn; $column++) {
            $heading = trim((string) $sheet->getCell([$column, 1])->getValue());
            $normalizedHeading = mb_strtolower($heading);

            foreach (self::HEADERS as $field => $aliases) {
                if (in_array($normalizedHeading, $aliases, true)) {
                    $columns[$field] = $column;
                    break;
                }
            }
        }

        if (! isset($columns['nama_produsen'])) {
            return [
                'rows' => [],
                'valid_count' => 0,
                'errors' => ['Header wajib "nama_produsen" tidak ditemukan pada baris pertama.'],
            ];
        }

        $rows = [];
        $errors = [];

        for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $row = [];
            foreach ($columns as $field => $column) {
                $value = $sheet->getCell([$column, $rowNumber])->getValue();
                $row[$field] = is_string($value) ? trim($value) : $value;
            }

            if (empty(array_filter($row, fn ($value) => $value !== null && trim((string) $value) !== ''))) {
                continue;
            }

            $row['nama_produsen'] = trim((string) ($row['nama_produsen'] ?? ''));
            $row['nama_contact_person'] = $this->nullableString($row['nama_contact_person'] ?? null);
            $row['kontak'] = $this->nullableString($row['kontak'] ?? null);
            $row['email'] = $this->nullableString($row['email'] ?? null);
            $row['alamat'] = $this->nullableString($row['alamat'] ?? null);
            $row['status'] = $this->parseStatus($row['status'] ?? null);

            $validator = Validator::make($row, [
                'nama_produsen' => 'required|string|max:100',
                'nama_contact_person' => 'nullable|string|max:100',
                'kontak' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:100',
                'alamat' => 'nullable|string|max:255',
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $row['_errors'][] = $message;
                }
            }

            $row['_excel_row'] = $rowNumber;
            $rows[] = $row;
        }

        if (empty($rows) && empty($errors)) {
            $errors[] = 'Tidak ada data produsen yang ditemukan pada file.';
        }

        $names = array_column($rows, 'nama_produsen');
        $seenNames = [];
        foreach ($names as $index => $name) {
            $rowNumber = $rows[$index]['_excel_row'];
            if (isset($seenNames[$name])) {
                $rows[$index]['_errors'][] = "Nama produsen '$name' duplikat di dalam file.";
            }
            $seenNames[$name] = true;
        }

        if (! empty($names)) {
            $registeredNames = DB::table('produsen_data')
                ->whereIn('nama_produsen', array_values(array_unique($names)))
                ->pluck('nama_produsen')
                ->all();
            $registeredNames = array_fill_keys($registeredNames, true);

            foreach ($rows as $index => $row) {
                if (isset($registeredNames[$row['nama_produsen']])) {
                    $rows[$index]['_errors'][] = "Nama produsen '{$row['nama_produsen']}' sudah terdaftar.";
                }
            }
        }

        foreach ($rows as $row) {
            foreach ($row['_errors'] ?? [] as $message) {
                $errors[] = "Baris {$row['_excel_row']}: {$message}";
            }
        }

        return [
            'rows' => $rows,
            'valid_count' => count(array_filter($rows, fn ($row) => empty($row['_errors']))),
            'errors' => $errors,
        ];
    }

    public function store(array $rows): int
    {
        $names = array_values(array_unique(array_column($rows, 'nama_produsen')));
        $now = now();

        return DB::transaction(function () use ($rows, $names, $now): int {
            $registeredNames = DB::table('produsen_data')
                ->whereIn('nama_produsen', $names)
                ->pluck('nama_produsen')
                ->all();

            if (! empty($registeredNames)) {
                throw new \DomainException(
                    'Nama produsen sudah terdaftar: '.implode(', ', $registeredNames)
                );
            }

            $records = array_map(function (array $row) use ($now): array {
                unset($row['_excel_row'], $row['_errors']);

                return $row + ['created_at' => $now, 'updated_at' => $now];
            }, $rows);

            foreach (array_chunk($records, 500) as $batch) {
                DB::table('produsen_data')->insert($batch);
            }

            return count($records);
        });
    }

    public function import(string $filePath): array
    {
        $preview = $this->preview($filePath);
        if (! empty($preview['errors'])) {
            return ['imported' => 0, 'errors' => $preview['errors']];
        }

        return ['imported' => $this->store($preview['rows']), 'errors' => []];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function parseStatus(mixed $value): ?int
    {
        $value = mb_strtolower(trim((string) ($value ?? '')));
        if ($value === '') {
            return 1;
        }

        return match ($value) {
            '1', 'aktif' => 1,
            '0', 'nonaktif' => 0,
            default => null,
        };
    }
}
