<?php

namespace App\Exports;

use App\Models\ProdusenData;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProdusenExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID Produsen',
            'Nama Produsen',
            'Contact Person',
            'Kontak',
            'Email',
            'Alamat',
            'Status',
        ];
    }

    /**
     * @param ProdusenData $produsen
     */
    public function map($produsen): array
    {
        return [
            $produsen->produsen_id,
            $produsen->nama_produsen,
            $produsen->nama_contact_person,
            $produsen->kontak,
            $produsen->email,
            $produsen->alamat,
            $produsen->status === 1 ? 'Aktif' : 'Nonaktif',
        ];
    }
}
