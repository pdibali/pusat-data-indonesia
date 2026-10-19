<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProdusenImportTemplate implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'nama_produsen',
            'nama_contact_person',
            'kontak',
            'email',
            'alamat',
            'status',
        ];
    }
}
