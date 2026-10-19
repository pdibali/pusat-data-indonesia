<?php

namespace Tests\Feature;

use App\Exports\ProdusenExport;
use App\Models\ProdusenData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProdusenExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_produsen_can_be_exported_with_the_current_search_and_sort(): void
    {
        $this->withoutMiddleware();
        Excel::fake();

        $first = ProdusenData::create([
            'nama_produsen' => 'Dinas Data Bali',
            'nama_contact_person' => 'Ibu Sari',
            'kontak' => '08123456789',
            'email' => 'sari@example.test',
            'alamat' => 'Denpasar',
            'status' => 1,
        ]);
        ProdusenData::create([
            'nama_produsen' => 'Dinas Data Nonaktif',
            'status' => 0,
        ]);
        $expectedIds = [$first->produsen_id];
        for ($index = 1; $index <= 11; $index++) {
            $expectedIds[] = ProdusenData::create([
                'nama_produsen' => "Dinas Data {$index}",
                'status' => 1,
            ])->produsen_id;
        }

        $response = $this->get(route('admin.produsen.export', [
            'search' => 'Dinas Data',
            'sort' => 'terlama',
        ]));

        $response->assertOk();
        Excel::assertDownloaded('produsen.xlsx', function (ProdusenExport $export) use ($first, $expectedIds) {
            $this->assertSame([
                'ID Produsen',
                'Nama Produsen',
                'Contact Person',
                'Kontak',
                'Email',
                'Alamat',
                'Status',
            ], $export->headings());
            $this->assertSame(
                $expectedIds,
                $export->query()->pluck('produsen_id')->all()
            );
            $this->assertSame([
                $first->produsen_id,
                'Dinas Data Bali',
                'Ibu Sari',
                '08123456789',
                'sari@example.test',
                'Denpasar',
                'Aktif',
            ], $export->map($first));

            return true;
        });
    }
}
