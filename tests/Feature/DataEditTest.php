<?php

namespace Tests\Feature;

use App\Imports\DataImport;
use App\Models\Data;
use App\Models\Group;
use App\Models\Klasifikasi;
use App\Models\Location;
use App\Models\Metadata;
use App\Models\ProdusenData;
use App\Models\Rujukan;
use App\Models\Satuan;
use App\Models\User;
use App\Models\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DataEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_edit_page_and_update_data(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create(['group_id' => $group->group_id]);

        $produsen = ProdusenData::create([
            'nama_produsen' => 'Produsen Uji',
            'status' => 1,
        ]);

        $klasifikasi = Klasifikasi::create([
            'nama_klasifikasi' => 'Klasifikasi Uji',
            'icon' => 'fas fa-test',
            'status' => 1,
        ]);

        $metadata = Metadata::create([
            'nama' => 'Metadata Uji',
            'konsep' => 'Konsep uji',
            'definisi' => 'Definisi uji',
            'asumsi' => 'Asumsi uji',
            'klasifikasi_id' => $klasifikasi->klasifikasi_id,
            'metodologi' => 'Metodologi uji',
            'penjelasan_metodologi' => 'Penjelasan metodologi uji',
            'status' => Metadata::STATUS_ACTIVE,
            'tipe_data' => 'angka',
            'satuan_data' => 'orang',
            'tahun_mulai_data' => '2024',
            'frekuensi_penerbitan' => 'Tahunan',
            'tag' => 'uji',
            'flag_desimal' => 0,
            'sumber_metadata_pertama' => $produsen->produsen_id,
            'date_inputed' => now(),
            'user_id' => $user->user_id,
        ]);

        $location = Location::create([
            'location_id' => '51000000',
            'nama_wilayah' => 'Provinsi Bali',
            'status' => 1,
        ]);

        $time = Waktu::create([
            'decade' => 2020,
            'year' => 2024,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);

        $rujukan = Rujukan::create([
            'nama_rujukan' => 'Rujukan Uji',
            'produsen_id' => $produsen->produsen_id,
            'status' => 1,
        ]);

        $manualProducer = ProdusenData::create([
            'nama_produsen' => 'Produsen Pilihan',
            'status' => 1,
        ]);
        $newProducer = ProdusenData::create([
            'nama_produsen' => 'Produsen Bulk Baru',
            'status' => 1,
        ]);
        $newReference = Rujukan::create([
            'nama_rujukan' => 'Rujukan Bulk Baru',
            'produsen_id' => $newProducer->produsen_id,
            'status' => 1,
        ]);
        $oldMetadataUnit = Satuan::create([
            'nama_satuan' => 'Unit Metadata Lama',
            'simbol' => 'uml',
            'nilai_konversi' => 1,
        ]);
        $newMetadataUnit = Satuan::create([
            'nama_satuan' => 'Unit Metadata Baru',
            'simbol' => 'umb',
            'nilai_konversi' => 1,
        ]);
        $oldReferenceUnit = Satuan::create([
            'nama_satuan' => 'Unit Rujukan Lama',
            'simbol' => 'url',
            'nilai_konversi' => 1,
        ]);
        $newReferenceUnit = Satuan::create([
            'nama_satuan' => 'Unit Rujukan Baru',
            'simbol' => 'urb',
            'nilai_konversi' => 1,
        ]);
        $unchangedTime = Waktu::create([
            'decade' => 2020,
            'year' => 2023,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);
        $blankTime = Waktu::create([
            'decade' => 2020,
            'year' => 2022,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);

        $datum = Data::create([
            'user_id' => $user->user_id,
            'metadata_id' => $metadata->metadata_id,
            'location_id' => $location->location_id,
            'time_id' => $time->time_id,
            'rujukan_id' => $rujukan->rujukan_id,
            'produsen_id' => $manualProducer->produsen_id,
            'satuan_id' => $oldMetadataUnit->satuan_id,
            'satuan_asal_id' => $oldReferenceUnit->satuan_id,
            'number_value' => 100,
            'status' => Data::STATUS_PENDING,
            'workflow_status' => Data::WORKFLOW_DRAFT,
            'date_inputed' => now(),
        ]);
        $unchangedDatum = Data::create([
            'user_id' => $user->user_id,
            'metadata_id' => $metadata->metadata_id,
            'location_id' => $location->location_id,
            'time_id' => $unchangedTime->time_id,
            'rujukan_id' => $rujukan->rujukan_id,
            'produsen_id' => $manualProducer->produsen_id,
            'number_value' => 85,
            'status' => Data::STATUS_AVAILABLE,
            'workflow_status' => Data::WORKFLOW_APPROVED,
            'date_inputed' => now(),
        ]);
        $blankDatum = Data::create([
            'user_id' => $user->user_id,
            'metadata_id' => $metadata->metadata_id,
            'location_id' => $location->location_id,
            'time_id' => $blankTime->time_id,
            'rujukan_id' => $rujukan->rujukan_id,
            'produsen_id' => $manualProducer->produsen_id,
            'number_value' => 55,
            'status' => Data::STATUS_AVAILABLE,
            'workflow_status' => Data::WORKFLOW_APPROVED,
            'date_inputed' => now(),
        ]);

        $datum->status = Data::STATUS_AVAILABLE;
        $datum->save();

        $metadataResponse = $this->getJson(route('metadata.detail_api', $metadata->metadata_id));
        $metadataResponse
            ->assertOk()
            ->assertJsonPath('nama', 'Metadata Uji')
            ->assertJsonPath('konsep', 'Konsep uji')
            ->assertJsonPath('definisi', 'Definisi uji')
            ->assertJsonPath('asumsi', 'Asumsi uji')
            ->assertJsonPath('wilayah_data.0', 'Provinsi Bali')
            ->assertJsonPath('rujukan.0', 'Rujukan Uji');

        $createResponse = $this->actingAs($user)->get(route('data.create'));
        $createResponse
            ->assertOk()
            ->assertSee('Bulk Update Data')
            ->assertSee('name="rujukan_id"', false)
            ->assertSee('name="produsen_id"', false)
            ->assertSee('value="'.$produsen->produsen_id.'"', false);
        $this->actingAs($user)->get(route('data.index'))
            ->assertOk()
            ->assertDontSee('Bulk Update Data');
        $this->assertGreaterThan(
            strpos($createResponse->getContent(), 'name="rujukan_id"'),
            strpos($createResponse->getContent(), 'name="produsen_id"')
        );

        $templateResponse = $this->actingAs($user)->get(route('metadata.template', [
            'produsen_id' => $produsen->produsen_id,
            'rentang' => '5-tahun',
            'tahun_awal' => 2021,
        ]));
        $templateResponse->assertOk();
        $templatePath = tempnam(sys_get_temp_dir(), 'metadata-template-');
        try {
            file_put_contents($templatePath, $templateResponse->streamedContent());
            $templateSheet = IOFactory::createReader('Xlsx')->load($templatePath)->getActiveSheet();
            $this->assertSame('rujukan_id', $templateSheet->getCell('G3')->getValue());
            $this->assertSame('produsen_id', $templateSheet->getCell('H3')->getValue());
            $this->assertSame(
                ['2021', '2022', '2023', '2024', '2025'],
                array_map(
                    fn ($column) => (string) $templateSheet->getCell($column.'3')->getValue(),
                    ['I', 'J', 'K', 'L', 'M']
                )
            );
            $this->assertSame($produsen->produsen_id, (int) $templateSheet->getCell('H4')->getValue());
        } finally {
            unlink($templatePath);
        }

        $importTime = Waktu::create([
            'decade' => 2020,
            'year' => 2025,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ([
            'A3' => 'metadata_id',
            'B3' => 'nama_metadata',
            'C3' => 'satuan_metadata',
            'D3' => 'satuan_rujukan',
            'E3' => 'location_id',
            'F3' => 'nama_wilayah',
            'G3' => 'rujukan_id',
            'H3' => 'produsen_id',
            'I3' => '2025',
            'A4' => $metadata->metadata_id,
            'B4' => $metadata->nama,
            'E4' => $location->location_id,
            'F4' => $location->nama_wilayah,
            'G4' => $rujukan->rujukan_id,
            'H4' => $manualProducer->produsen_id,
            'I4' => 99,
        ] as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $excelPath = tempnam(sys_get_temp_dir(), 'data-import-');
        try {
            (new Xlsx($spreadsheet))->save($excelPath);
            $preview = (new DataImport)->preview($excelPath);
        } finally {
            unlink($excelPath);
        }

        $this->assertSame(1, $preview['valid']);
        $this->assertSame($importTime->time_id, $preview['rows'][0]['time_id']);
        $this->assertSame($manualProducer->produsen_id, $preview['rows'][0]['produsen_id']);

        $datum->status = Data::STATUS_PENDING;
        $datum->save();

        $response = $this->actingAs($user)->get(route('data.edit', $datum));
        $response->assertOk();
        $response->assertSee('Edit Data');
        $response->assertSee('value="'.$manualProducer->produsen_id.'" selected', false);
        $response->assertDontSee('otomatis dari rujukan');

        $bulkTime = Waktu::create([
            'decade' => 2020,
            'year' => 2025,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);
        $bulkSpreadsheet = new Spreadsheet;
        $bulkSheet = $bulkSpreadsheet->getActiveSheet();
        foreach ([
            'A3' => 'metadata_id',
            'B3' => 'nama_metadata',
            'C3' => 'satuan_metadata',
            'D3' => 'satuan_rujukan',
            'E3' => 'location_id',
            'F3' => 'nama_wilayah',
            'G3' => 'rujukan_id',
            'H3' => 'produsen_id',
            'I3' => '2022',
            'J3' => '2023',
            'K3' => '2024',
            'L3' => '2025',
            'A4' => $metadata->metadata_id,
            'B4' => $metadata->nama,
            'C4' => $newMetadataUnit->nama_satuan,
            'D4' => $newReferenceUnit->simbol,
            'E4' => $location->location_id,
            'F4' => $location->nama_wilayah,
            'G4' => $newReference->rujukan_id,
            'H4' => $newProducer->produsen_id,
            'K4' => 123.5,
            'L4' => 77,
        ] as $cell => $value) {
            $bulkSheet->setCellValue($cell, $value);
        }
        $bulkPath = tempnam(sys_get_temp_dir(), 'data-bulk-update-');
        try {
            (new Xlsx($bulkSpreadsheet))->save($bulkPath);
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $bulkPath,
                'bulk-update.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            );
            $previewResponse = $this->actingAs($user)->post(route('data.bulk_update.preview'), [
                'file_excel' => $uploadedFile,
            ]);
            $previewResponse
                ->assertOk()
                ->assertSee('100.00')
                ->assertSee('123.50')
                ->assertSee('2024')
                ->assertSee('Rujukan Bulk Baru')
                ->assertSee('Produsen Bulk Baru')
                ->assertSee('Unit Metadata Lama')
                ->assertSee('Unit Metadata Baru')
                ->assertSee('Unit Rujukan Lama')
                ->assertSee('Unit Rujukan Baru')
                ->assertSee('1'); // The 2025 record is unmatched and should be skipped.
            $this->assertDatabaseHas('data', ['id' => $datum->id, 'number_value' => '100.00']);
            $this->assertDatabaseHas('data', ['id' => $blankDatum->id, 'number_value' => '55.00']);

            preg_match('/name="token" value="([^"]+)"/', $previewResponse->getContent(), $matches);
            $this->assertNotEmpty($matches[1] ?? null);
            $applyResponse = $this->actingAs($user)->post(route('data.bulk_update.apply'), [
                'token' => $matches[1],
            ]);
            $applyResponse->assertRedirect(route('data.bulk_update'));
            $this->assertDatabaseHas('data', [
                'id' => $datum->id,
                'number_value' => '123.50',
                'rujukan_id' => $newReference->rujukan_id,
                'produsen_id' => $newProducer->produsen_id,
                'satuan_id' => $newMetadataUnit->satuan_id,
                'satuan_asal_id' => $newReferenceUnit->satuan_id,
                'status' => Data::STATUS_PENDING,
                'workflow_status' => Data::WORKFLOW_DRAFT,
            ]);
            $this->assertDatabaseHas('data', [
                'id' => $unchangedDatum->id,
                'number_value' => '85.00',
                'status' => Data::STATUS_AVAILABLE,
                'workflow_status' => Data::WORKFLOW_APPROVED,
            ]);
            $this->assertDatabaseHas('data', [
                'id' => $blankDatum->id,
                'number_value' => '55.00',
                'status' => Data::STATUS_AVAILABLE,
                'workflow_status' => Data::WORKFLOW_APPROVED,
            ]);
            $this->assertSame(2025, $bulkTime->year);

            $unchangedDatum->rujukan_id = $newReference->rujukan_id;
            $unchangedDatum->save();
            $referenceFilterResponse = $this->actingAs($user)->get(route('data.approval', [
                'status' => Data::STATUS_AVAILABLE,
                'filter_rujukan' => $rujukan->rujukan_id,
            ]));
            $referenceFilterResponse
                ->assertOk()
                ->assertSee('id="rujukanSelect"', false)
                ->assertSee('row-'.$blankDatum->id, false)
                ->assertDontSee('row-'.$unchangedDatum->id, false);

            $producerFilterResponse = $this->actingAs($user)->get(route('data.approval', [
                'status' => Data::STATUS_AVAILABLE,
                'filter_produsen' => 'Produsen Pilihan',
            ]));
            $producerFilterResponse
                ->assertOk()
                ->assertSee('row-'.$blankDatum->id, false)
                ->assertSee('row-'.$unchangedDatum->id, false);

            $yearFilterResponse = $this->actingAs($user)->get(route('data.approval', [
                'status' => Data::STATUS_AVAILABLE,
                'filter_tahun' => 2022,
                'filter_granularitas' => 'tahunan',
            ]));
            $yearFilterResponse
                ->assertOk()
                ->assertSee('row-'.$blankDatum->id, false)
                ->assertDontSee('row-'.$unchangedDatum->id, false);
        } finally {
            unlink($bulkPath);
        }

        $updateResponse = $this->actingAs($user)->put(route('data.update', $datum), [
            'metadata_id' => $metadata->metadata_id,
            'location_id' => $location->location_id,
            'time_id' => $time->time_id,
            'rujukan_id' => $rujukan->rujukan_id,
            'produsen_id' => $manualProducer->produsen_id,
            'number_value' => 125.5,
        ]);

        $updateResponse->assertRedirect(route('data.show', $datum));
        $this->assertDatabaseHas('data', ['id' => $datum->id, 'number_value' => '125.50']);
    }
}
