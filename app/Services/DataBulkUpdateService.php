<?php

namespace App\Services;

use App\Imports\DataImport;
use App\Support\ProdusenValueParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DataBulkUpdateService
{
    private const HEADER_ROW = 3;

    private const DATA_ROW = 4;

    private const QUERY_CHUNK_SIZE = 100;

    private array $unitIdByText = [];

    private array $rujukanIds = [];

    private array $produsenIds = [];

    private array $produsenById = [];

    private array $produsenIdsByName = [];

    public function __construct(private readonly AuditTrailService $auditTrail) {}

    public function preview(string $filePath): array
    {
        [$columns, $periods, $rows] = $this->readWorkbook($filePath);
        $timeIds = $this->resolveTimeIds($periods);
        $this->preloadUnitLookup();
        $this->rujukanIds = DB::table('rujukan')->pluck('rujukan_id')->map(fn ($id) => (int) $id)->all();
        foreach (DB::table('produsen_data')->get(['produsen_id', 'nama_produsen']) as $producer) {
            $producerId = (int) $producer->produsen_id;
            $this->produsenIds[] = $producerId;
            $this->produsenById[$producerId] = ['nama' => $producer->nama_produsen];
            $this->produsenIdsByName[$producer->nama_produsen][] = $producerId;
        }
        $candidates = [];
        $fileKeys = [];
        $rowErrors = [];

        foreach ($rows as $rowNumber => $row) {
            $currentRowErrors = [];
            $values = [];
            foreach ($periods as $period) {
                $value = $row[$period['index']] ?? null;
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }
                if (! is_numeric($value) || ! is_finite((float) $value)) {
                    $currentRowErrors[] = "Baris {$rowNumber}, periode {$period['label']}: nilai harus berupa angka.";

                    continue;
                }
                $values[$period['label']] = number_format((float) $value, 2, '.', '');
            }

            if ($values === []) {
                $rowErrors = array_merge($rowErrors, $currentRowErrors);

                continue;
            }

            $metadataId = $this->readId($row[$columns['metadata_id']] ?? null);
            $locationId = $this->readId($row[$columns['location_id']] ?? null);
            $rujukanRaw = $this->getColumnValue($row, $columns, 'rujukan_id');
            $produsenRaw = $this->getColumnValue($row, $columns, 'produsen_id');
            $satuanMetadataRaw = $this->getColumnValue($row, $columns, 'satuan_metadata');
            $satuanRujukanRaw = $this->getColumnValue($row, $columns, 'satuan_rujukan');

            if ($metadataId === null || $locationId === null) {
                $currentRowErrors[] = "Baris {$rowNumber}: metadata_id dan location_id wajib berisi ID valid.";
                $rowErrors = array_merge($rowErrors, $currentRowErrors);

                continue;
            }
            $rujukanText = trim((string) ($rujukanRaw ?? ''));
            $produsenText = trim((string) ($produsenRaw ?? ''));
            $rujukanId = $rujukanText === '' ? null : $this->readOptionalId($rujukanRaw);
            $produsenResolution = $produsenText === ''
                ? ['ids' => [], 'invalid' => []]
                : ProdusenValueParser::resolve($produsenRaw, $this->produsenById, $this->produsenIdsByName);
            $produsenIds = $produsenResolution['ids'];
            $produsenId = $produsenIds[0] ?? null;
            sort($produsenIds);
            if ($rujukanText !== '' && $rujukanId === null) {
                $currentRowErrors[] = "Baris {$rowNumber}: rujukan_id harus berisi ID valid atau dikosongkan.";

            }
            if ($produsenText !== '' && ($produsenIds === [] || $produsenResolution['invalid'] !== [])) {
                $currentRowErrors[] = "Baris {$rowNumber}: produsen_id '".implode('; ', $produsenResolution['invalid'])."' tidak ditemukan. Gunakan ID atau nama produsen terdaftar, pisahkan beberapa produsen dengan ';'.";
            }
            if ($rujukanId !== null && ! in_array($rujukanId, $this->rujukanIds, true)) {
                $currentRowErrors[] = "Baris {$rowNumber}: rujukan_id '{$rujukanId}' tidak ditemukan.";
            }
            if ($produsenId !== null && ! in_array($produsenId, $this->produsenIds, true)) {
                $currentRowErrors[] = "Baris {$rowNumber}: produsen_id '{$produsenId}' tidak ditemukan.";
            }
            $satuanMetadata = $this->resolveUnit($satuanMetadataRaw, $rowNumber, 'satuan_metadata', $currentRowErrors);
            $satuanRujukan = $this->resolveUnit($satuanRujukanRaw, $rowNumber, 'satuan_rujukan', $currentRowErrors);

            if ($currentRowErrors !== []) {
                $rowErrors = array_merge($rowErrors, $currentRowErrors);

                continue;
            }

            $rowCandidates = [];
            foreach ($values as $label => $value) {
                $timeId = $timeIds[$label] ?? null;
                if ($timeId === null) {
                    $currentRowErrors[] = "Baris {$rowNumber}, periode {$label}: periode tidak tersedia pada dimensi waktu.";

                    break;
                }

                $key = $this->key(
                    $metadataId,
                    $locationId,
                    $timeId,
                    $rujukanId,
                    $produsenIds,
                    $satuanMetadata['id'],
                    $satuanRujukan['id'],
                );
                if (isset($fileKeys[$key])) {
                    $currentRowErrors[] = "Baris {$rowNumber}: kombinasi metadata, lokasi, periode, dan nilai referensi yang diminta muncul lebih dari sekali.";

                    break;
                }
                $candidate = [
                    'key' => $key,
                    'row_number' => $rowNumber,
                    'metadata_id' => $metadataId,
                    'location_id' => $locationId,
                    'time_id' => $timeId,
                    'period_label' => $label,
                    'new_value' => $value,
                ];
                if ($rujukanText !== '') {
                    $candidate['rujukan_id'] = $rujukanId;
                }
                if ($produsenText !== '') {
                    $candidate['produsen_id'] = $produsenId;
                    $candidate['produsen_ids'] = $produsenIds;
                }
                if ($satuanMetadata['provided']) {
                    $candidate['satuan_id'] = $satuanMetadata['id'];
                }
                if ($satuanRujukan['provided']) {
                    $candidate['satuan_asal_id'] = $satuanRujukan['id'];
                }
                $rowCandidates[$key] = $candidate;
            }
            if ($currentRowErrors !== []) {
                $rowErrors = array_merge($rowErrors, $currentRowErrors);

                continue;
            }
            foreach ($rowCandidates as $key => $candidate) {
                $fileKeys[$key] = true;
                $candidates[$key] = $candidate;
            }
        }

        $changes = [];
        $matchedKeys = [];
        $byBaseKey = [];
        foreach ($candidates as $candidate) {
            $baseKey = $this->baseKey($candidate['metadata_id'], $candidate['location_id'], $candidate['time_id']);
            $byBaseKey[$baseKey][] = $candidate;
        }

        foreach (array_chunk(array_values($byBaseKey), self::QUERY_CHUNK_SIZE) as $groups) {
            $query = DB::table('data')
                ->leftJoin('metadata', 'data.metadata_id', '=', 'metadata.metadata_id')
                ->leftJoin('location', 'data.location_id', '=', 'location.location_id')
                ->leftJoin('rujukan', 'data.rujukan_id', '=', 'rujukan.rujukan_id')
                ->leftJoin('produsen_data', 'data.produsen_id', '=', 'produsen_data.produsen_id')
                ->leftJoin('satuan as satuan_metadata', 'data.satuan_id', '=', 'satuan_metadata.satuan_id')
                ->leftJoin('satuan as satuan_rujukan', 'data.satuan_asal_id', '=', 'satuan_rujukan.satuan_id')
                ->join('time', 'data.time_id', '=', 'time.time_id')
                ->where(function ($query) use ($groups) {
                    foreach ($groups as $group) {
                        $identity = $group[0];
                        $query->orWhere(function ($query) use ($identity) {
                            $query->where('data.metadata_id', $identity['metadata_id'])
                                ->where('data.location_id', $identity['location_id'])
                                ->where('data.time_id', $identity['time_id']);
                        });
                    }
                })
                ->select([
                    'data.id',
                    'data.metadata_id',
                    'data.location_id',
                    'data.time_id',
                    'data.rujukan_id',
                    'data.produsen_id',
                    'data.satuan_id',
                    'data.satuan_asal_id',
                    'data.number_value',
                    'metadata.nama as nama_metadata',
                    'location.nama_wilayah',
                    'rujukan.nama_rujukan',
                    'produsen_data.nama_produsen',
                    'satuan_metadata.nama_satuan as satuan_metadata_nama',
                    'satuan_rujukan.nama_satuan as satuan_rujukan_nama',
                    'satuan_metadata.simbol as satuan_metadata_simbol',
                    'satuan_rujukan.simbol as satuan_rujukan_simbol',
                    'time.year',
                    'time.semester',
                    'time.quarter',
                    'time.month',
                ]);

            $recordsByBaseKey = [];
            foreach ($query->get() as $record) {
                $baseKey = $this->baseKey((int) $record->metadata_id, (int) $record->location_id, (int) $record->time_id);
                $recordsByBaseKey[$baseKey][] = $record;
            }
            $recordIds = [];
            foreach ($recordsByBaseKey as $baseRecords) {
                foreach ($baseRecords as $record) {
                    $recordIds[] = (int) $record->id;
                }
            }
            if ($recordIds !== []) {
                $producerIdsByRecord = DB::table('data_produsen')
                    ->whereIn('data_id', $recordIds)
                    ->orderBy('produsen_id')
                    ->get(['data_id', 'produsen_id'])
                    ->groupBy('data_id')
                    ->map(fn ($rows) => $rows->pluck('produsen_id')->map(fn ($id) => (int) $id)->all())
                    ->all();
                foreach ($recordsByBaseKey as $baseKey => $baseRecords) {
                    foreach ($baseRecords as $index => $record) {
                        $record->_produsen_ids = $producerIdsByRecord[$record->id] ?? (
                            $record->produsen_id === null ? [] : [(int) $record->produsen_id]
                        );
                        $recordsByBaseKey[$baseKey][$index] = $record;
                    }
                }
            }

            foreach ($groups as $group) {
                $baseKey = $this->baseKey($group[0]['metadata_id'], $group[0]['location_id'], $group[0]['time_id']);
                $records = $recordsByBaseKey[$baseKey] ?? [];
                [$assignments, $matchingErrors] = $this->matchRecords($group, $records);
                $rowErrors = array_merge($rowErrors, $matchingErrors);

                foreach ($assignments as [$candidate, $record]) {
                    $matchedKeys[$candidate['key']] = true;
                    $oldFields = [
                        'number_value' => $this->normalizeStoredValue($record->number_value),
                        'rujukan_id' => $record->rujukan_id === null ? null : (int) $record->rujukan_id,
                        'produsen_id' => $record->produsen_id === null ? null : (int) $record->produsen_id,
                        'produsen_ids' => $record->_produsen_ids ?? [],
                        'satuan_id' => $record->satuan_id === null ? null : (int) $record->satuan_id,
                        'satuan_asal_id' => $record->satuan_asal_id === null ? null : (int) $record->satuan_asal_id,
                    ];
                    $newFields = ['number_value' => $candidate['new_value']];
                    foreach (['rujukan_id', 'produsen_id', 'satuan_id', 'satuan_asal_id'] as $field) {
                        if (array_key_exists($field, $candidate)) {
                            $newFields[$field] = $candidate[$field];
                        }
                    }
                    if (array_key_exists('produsen_ids', $candidate)) {
                        $newFields['produsen_ids'] = $candidate['produsen_ids'];
                    }

                    $fieldChanges = [];
                    foreach ($newFields as $field => $newValue) {
                        if ($oldFields[$field] !== $newValue) {
                            $fieldChanges[$field] = [
                                'old' => $oldFields[$field],
                                'new' => $newValue,
                            ];
                        }
                    }
                    if ($fieldChanges === []) {
                        continue;
                    }

                    $changes[] = [
                        'data_id' => (int) $record->id,
                        'metadata_id' => (int) $record->metadata_id,
                        'location_id' => (int) $record->location_id,
                        'time_id' => (int) $record->time_id,
                        'old_fields' => $oldFields,
                        'new_fields' => $newFields,
                        'old_value' => $oldFields['number_value'],
                        'new_value' => $newFields['number_value'],
                        'field_changes' => $this->labelChanges($fieldChanges, $record),
                        'metadata' => $record->nama_metadata ?? (string) $record->metadata_id,
                        'location' => $record->nama_wilayah ?? (string) $record->location_id,
                        'period' => $this->formatPeriod($record),
                        'reference' => $record->nama_rujukan ?? '-',
                        'producer' => $this->formatProducerNames($record->_produsen_ids ?? []),
                    ];
                }
            }
        }

        return [
            'changes' => $changes,
            'changed_count' => count($changes),
            'unchanged_count' => count($matchedKeys) - count($changes),
            'unmatched_count' => count($candidates) - count($matchedKeys),
            'errors' => $rowErrors,
        ];
    }

    public function apply(array $changes, int $userId): int
    {
        return DB::transaction(function () use ($changes, $userId) {
            $records = [];
            foreach ($changes as $change) {
                $record = DB::table('data')->where('id', $change['data_id'])->lockForUpdate()->first();
                if (
                    ! $record
                    || (int) $record->metadata_id !== $change['metadata_id']
                    || (int) $record->location_id !== $change['location_id']
                    || (int) $record->time_id !== $change['time_id']
                    || $this->normalizeCurrentFields($record) !== $change['old_fields']
                ) {
                    throw ValidationException::withMessages([
                        'token' => 'Data berubah sejak pratinjau dibuat. Silakan unggah ulang file dan periksa perbandingan terbaru.',
                    ]);
                }
                $records[] = [$record, $change];
            }

            foreach ($records as [$record, $change]) {
                $oldValues = [
                    'number_value' => $record->number_value,
                    'rujukan_id' => $record->rujukan_id,
                    'produsen_id' => $record->produsen_id,
                    'satuan_id' => $record->satuan_id,
                    'satuan_asal_id' => $record->satuan_asal_id,
                    'status' => $record->status,
                    'workflow_status' => $record->workflow_status,
                    'reviewer_note' => $record->reviewer_note,
                    'reviewed_by' => $record->reviewed_by,
                    'reviewed_at' => $record->reviewed_at,
                ];
                $oldValues['produsen_ids'] = $change['old_fields']['produsen_ids'];
                $newValues = [
                    ...$change['new_fields'],
                    'status' => 0,
                    'workflow_status' => 'draft',
                    'reviewer_note' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ];

                $dataValues = $newValues;
                unset($dataValues['produsen_ids']);
                DB::table('data')->where('id', $record->id)->update($dataValues);
                if (array_key_exists('produsen_ids', $newValues)) {
                    DB::table('data_produsen')->where('data_id', $record->id)->delete();
                    DB::table('data_produsen')->insert(array_map(
                        fn ($produsenId) => ['data_id' => $record->id, 'produsen_id' => $produsenId],
                        $newValues['produsen_ids']
                    ));
                }
                $this->auditTrail->recordUpdated(
                    'data',
                    (int) $record->id,
                    $oldValues,
                    $newValues,
                    'Bulk update data dari template Excel.',
                    $userId,
                );
            }

            return count($records);
        });
    }

    private function matchRecords(array $candidates, array $records): array
    {
        $remainingCandidates = array_values($candidates);
        $remainingRecords = array_values($records);
        $assignments = [];
        $errors = [];

        foreach ($remainingCandidates as $candidateIndex => $candidate) {
            $matches = array_values(array_filter(
                $remainingRecords,
                fn ($record) => $this->candidateMatchesProvidedFields($candidate, $record)
            ));
            if (count($matches) > 1) {
                $errors[] = "Baris {$candidate['row_number']}, periode {$candidate['period_label']}: cocok dengan beberapa data. Perjelas rujukan/produsen/satuan pada Excel.";
                unset($remainingCandidates[$candidateIndex]);

                continue;
            }
            if (count($matches) === 1) {
                $record = $matches[0];
                $assignments[] = [$candidate, $record];
                $remainingRecords = array_values(array_filter(
                    $remainingRecords,
                    fn ($remaining) => (int) $remaining->id !== (int) $record->id
                ));
                unset($remainingCandidates[$candidateIndex]);
            }
        }

        $remainingCandidates = array_values($remainingCandidates);
        if (count($remainingCandidates) === 1 && count($remainingRecords) === 1) {
            $assignments[] = [$remainingCandidates[0], $remainingRecords[0]];
        } elseif ($remainingCandidates !== [] && $remainingRecords !== []) {
            foreach ($remainingCandidates as $candidate) {
                $errors[] = "Baris {$candidate['row_number']}, periode {$candidate['period_label']}: tidak dapat dipasangkan secara unik karena terdapat beberapa data sumber untuk metadata/lokasi/periode tersebut.";
            }
        }

        return [$assignments, $errors];
    }

    private function candidateMatchesProvidedFields(array $candidate, object $record): bool
    {
        foreach ([
            'rujukan_id' => 'rujukan_id',
            'satuan_id' => 'satuan_id',
            'satuan_asal_id' => 'satuan_asal_id',
        ] as $candidateField => $recordField) {
            if (! array_key_exists($candidateField, $candidate)) {
                continue;
            }
            $recordValue = $record->{$recordField} === null ? null : (int) $record->{$recordField};
            if ($recordValue !== $candidate[$candidateField]) {
                return false;
            }
        }
        if (
            array_key_exists('produsen_ids', $candidate)
            && array_values($record->_produsen_ids ?? []) !== $candidate['produsen_ids']
        ) {
            return false;
        }

        return true;
    }

    private function labelChanges(array $fieldChanges, object $record): array
    {
        $labels = [
            'number_value' => 'Nilai angka',
            'rujukan_id' => 'Rujukan',
            'produsen_id' => 'Produsen',
            'produsen_ids' => 'Daftar produsen',
            'satuan_id' => 'Satuan metadata',
            'satuan_asal_id' => 'Satuan rujukan',
        ];
        $result = [];

        foreach ($fieldChanges as $field => $change) {
            $result[] = [
                'field' => $labels[$field],
                'old' => $this->displayFieldValue($field, $change['old'], $record),
                'new' => $this->displayFieldValue($field, $change['new'], $record),
            ];
        }

        return $result;
    }

    private function displayFieldValue(string $field, mixed $value, object $record): string
    {
        if ($field === 'number_value') {
            return $value === null ? 'Kosong' : number_format((float) $value, 2, '.', '');
        }
        if ($value === null) {
            return 'Kosong';
        }
        if ($field === 'produsen_ids') {
            if ($value === []) {
                return 'Kosong';
            }
            $names = DB::table('produsen_data')
                ->whereIn('produsen_id', $value)
                ->orderBy('produsen_id')
                ->pluck('nama_produsen')
                ->all();

            return implode('; ', $names);
        }

        return match ($field) {
            'rujukan_id' => (int) $value === (int) $record->rujukan_id
                ? ($record->nama_rujukan ?? (string) $value)
                : (string) (DB::table('rujukan')->where('rujukan_id', $value)->value('nama_rujukan') ?? $value),
            'produsen_id' => (int) $value === (int) $record->produsen_id
                ? ($record->nama_produsen ?? (string) $value)
                : (string) (DB::table('produsen_data')->where('produsen_id', $value)->value('nama_produsen') ?? $value),
            'satuan_id' => (int) $value === (int) $record->satuan_id
                ? $this->formatUnit($record->satuan_metadata_nama, $record->satuan_metadata_simbol)
                : $this->formatUnitById((int) $value),
            'satuan_asal_id' => (int) $value === (int) $record->satuan_asal_id
                ? $this->formatUnit($record->satuan_rujukan_nama, $record->satuan_rujukan_simbol)
                : $this->formatUnitById((int) $value),
            default => (string) $value,
        };
    }

    private function formatUnit(?string $name, ?string $symbol): string
    {
        if ($name === null) {
            return 'Kosong';
        }

        return $symbol ? "{$name} ({$symbol})" : $name;
    }

    private function formatUnitById(int $id): string
    {
        $unit = DB::table('satuan')->where('satuan_id', $id)->first(['nama_satuan', 'simbol']);

        return $unit ? $this->formatUnit($unit->nama_satuan, $unit->simbol) : (string) $id;
    }

    private function formatProducerNames(array $ids): string
    {
        if ($ids === []) {
            return '-';
        }

        return DB::table('produsen_data')
            ->whereIn('produsen_id', $ids)
            ->orderBy('produsen_id')
            ->pluck('nama_produsen')
            ->implode('; ');
    }

    private function preloadUnitLookup(): void
    {
        $this->unitIdByText = [];
        foreach (DB::table('satuan')->get(['satuan_id', 'nama_satuan', 'simbol']) as $unit) {
            $this->unitIdByText[strtolower(trim($unit->nama_satuan))] = (int) $unit->satuan_id;
            if ($unit->simbol !== null && trim($unit->simbol) !== '') {
                $this->unitIdByText[strtolower(trim($unit->simbol))] = (int) $unit->satuan_id;
            }
        }
    }

    private function resolveUnit(mixed $value, int $rowNumber, string $column, array &$errors): array
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return ['provided' => false, 'id' => null];
        }
        $unitId = $this->unitIdByText[strtolower($text)] ?? null;
        if ($unitId === null) {
            $errors[] = "Baris {$rowNumber}: {$column} '{$text}' tidak ditemukan di master satuan.";
        }

        return ['provided' => true, 'id' => $unitId];
    }

    private function getColumnValue(array $row, array $columns, string $column): mixed
    {
        $index = $columns[$column] ?? null;

        return $index === null ? null : ($row[$index] ?? null);
    }

    private function baseKey(int $metadataId, int $locationId, int $timeId): string
    {
        return implode('_', [$metadataId, $locationId, $timeId]);
    }

    private function key(
        int $metadataId,
        int $locationId,
        int $timeId,
        ?int $rujukanId,
        array $produsenIds,
        ?int $satuanId,
        ?int $satuanAsalId,
    ): string {
        sort($produsenIds);

        return implode('_', [
            $metadataId,
            $locationId,
            $timeId,
            $rujukanId ?? '',
            implode(',', $produsenIds),
            $satuanId ?? '',
            $satuanAsalId ?? '',
        ]);
    }

    private function normalizeCurrentFields(object $record): array
    {
        $producerIds = DB::table('data_produsen')
            ->where('data_id', $record->id)
            ->orderBy('produsen_id')
            ->pluck('produsen_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($producerIds === [] && $record->produsen_id !== null) {
            $producerIds = [(int) $record->produsen_id];
        }

        return [
            'number_value' => $this->normalizeStoredValue($record->number_value),
            'rujukan_id' => $record->rujukan_id === null ? null : (int) $record->rujukan_id,
            'produsen_id' => $record->produsen_id === null ? null : (int) $record->produsen_id,
            'produsen_ids' => $producerIds,
            'satuan_id' => $record->satuan_id === null ? null : (int) $record->satuan_id,
            'satuan_asal_id' => $record->satuan_asal_id === null ? null : (int) $record->satuan_asal_id,
        ];
    }

    private function readWorkbook(string $filePath): array
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception) {
            throw ValidationException::withMessages([
                'file_excel' => 'File Excel tidak dapat dibaca. Pastikan file tidak rusak dan menggunakan format .xlsx atau .xls.',
            ]);
        }
        $sheet = $spreadsheet->getSheetByName('Data Import') ?? $spreadsheet->getActiveSheet();
        $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headers = [];
        for ($column = 1; $column <= $lastColumn; $column++) {
            $value = trim((string) $sheet->getCell([$column, self::HEADER_ROW])->getValue());
            if ($value !== '') {
                $headers[$column] = strtolower($value);
            }
        }

        $columns = [];
        foreach (['metadata_id', 'location_id', 'rujukan_id', 'produsen_id'] as $required) {
            $column = array_search($required, $headers, true);
            if ($column === false && in_array($required, ['metadata_id', 'location_id'], true)) {
                throw ValidationException::withMessages([
                    'file_excel' => "Format Excel tidak valid: kolom {$required} wajib tersedia pada baris header.",
                ]);
            }
            $columns[$required] = $column === false ? null : $column;
        }
        foreach (['satuan_metadata', 'satuan_rujukan'] as $optional) {
            $column = array_search($optional, $headers, true);
            $columns[$optional] = $column === false ? null : $column;
        }

        $knownFixedHeaders = [
            'metadata_id', 'nama_metadata', 'satuan_metadata', 'satuan_rujukan',
            'location_id', 'nama_wilayah', 'rujukan_id', 'produsen_id',
        ];
        $lastFixedColumn = max(array_keys(array_filter(
            $headers,
            fn ($header) => in_array($header, $knownFixedHeaders, true)
        )));

        $import = new DataImport;
        $periods = [];
        for ($column = $lastFixedColumn + 1; $column <= $lastColumn; $column++) {
            $label = trim((string) $sheet->getCell([$column, self::HEADER_ROW])->getValue());
            if ($label === '') {
                continue;
            }
            if ($import->parseTimeLabel($label) === null) {
                throw ValidationException::withMessages([
                    'file_excel' => "Format Excel tidak valid: header periode '{$label}' tidak dikenali.",
                ]);
            }
            $periods[] = ['label' => $label, 'index' => $column];
        }
        if ($periods === []) {
            throw ValidationException::withMessages([
                'file_excel' => 'File Excel tidak memiliki kolom periode.',
            ]);
        }

        $rows = [];
        for ($rowNumber = self::DATA_ROW; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
            $row = [];
            $hasContent = false;
            for ($column = 1; $column <= $lastColumn; $column++) {
                $value = $sheet->getCell([$column, $rowNumber])->getValue();
                $row[$column] = $value;
                if ($value !== null && trim((string) $value) !== '') {
                    $hasContent = true;
                }
            }
            if ($hasContent) {
                $rows[$rowNumber] = $row;
            }
        }

        return [$columns, $periods, $rows];
    }

    private function resolveTimeIds(array $periods): array
    {
        $import = new DataImport;
        $resolved = [];
        foreach ($periods as $period) {
            $params = $import->parseTimeLabel($period['label']);
            $resolved[$period['label']] = DB::table('time')
                ->where('decade', $params['decade'])
                ->where('year', $params['year'])
                ->where('semester', $params['semester'])
                ->where('quarter', $params['quarter'])
                ->where('month', $params['month'])
                ->value('time_id');
        }

        return $resolved;
    }

    private function readId(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '' || ! is_numeric($value)) {
            return null;
        }
        $integer = (int) $value;

        return $integer > 0 && (float) $value === (float) $integer ? $integer : null;
    }

    private function readOptionalId(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->readId($value);
    }

    private function normalizeStoredValue(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }

    private function formatPeriod(object $record): string
    {
        if ((int) $record->month > 0) {
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            return $months[(int) $record->month - 1].'_'.$record->year;
        }
        if ((int) $record->quarter > 0) {
            return $record->year.'_Q'.$record->quarter;
        }
        if ((int) $record->semester > 0) {
            return $record->year.'_S'.$record->semester;
        }

        return (string) $record->year;
    }
}
