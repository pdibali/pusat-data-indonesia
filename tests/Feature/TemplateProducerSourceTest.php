<?php

namespace Tests\Feature;

use App\Models\IsiTampilan;
use App\Models\ProdusenData;
use App\Models\Tampilan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TemplateProducerSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_template_table_uses_the_producer_assigned_to_data(): void
    {
        $ids = $this->createDataWithDifferentReferenceAndDataProducers();

        $response = $this->postJson(route('template.table_data_guest'), [
            'metadata_ids' => [$ids['metadata']],
            'metadata_location_ids' => json_encode([$ids['metadata'] => [$ids['location']]]),
            'frekuensi' => 'tahunan',
            'year_from' => 2025,
            'year_to' => 2025,
        ]);

        $response->assertOk()
            ->assertJsonPath('rows.0.sumber', 'PT PLN (Persero) Unit Induk Distribusi Bali')
            ->assertJsonPath('rows.0.sumber_rujukan', 'Rujukan Statistik');
    }

    public function test_chart_displays_the_producer_assigned_to_data(): void
    {
        $ids = $this->createDataWithDifferentReferenceAndDataProducers();

        $response = $this->get(route('template.grafik', [
            'metadata_id' => $ids['metadata'],
            'location_id' => $ids['location'],
        ]));

        $response->assertOk()
            ->assertSeeText('PT PLN (Persero) Unit Induk Distribusi Bali')
            ->assertDontSeeText('Badan Pusat Statistik Provinsi Bali');
    }

    public function test_pdf_excel_and_json_exports_use_the_producer_assigned_to_data(): void
    {
        $ids = $this->createDataWithDifferentReferenceAndDataProducers();
        $template = Tampilan::create([
            'nama_tampilan' => 'Template Uji',
            'user_id' => $ids['user']->user_id,
        ]);
        IsiTampilan::create([
            'tampilan_id' => $template->tampilan_id,
            'metadata_id' => $ids['metadata'],
            'location_ids' => [$ids['location']],
        ]);
        $payload = [
            'tampilan_id' => $template->tampilan_id,
            'frekuensi' => 'tahunan',
            'year_from' => 2025,
            'year_to' => 2025,
        ];
        $this->actingAs($ids['user']);

        $pdfResponse = $this->post(route('template.export.pdf'), $payload);
        $pdfResponse->assertOk()
            ->assertViewHas('grouped', fn ($grouped) => $grouped[$ids['metadata']]['sumber']
                === 'PT PLN (Persero) Unit Induk Distribusi Bali')
            ->assertViewHas('grouped', function ($grouped) use ($ids) {
                $labels = collect($grouped[$ids['metadata']]['metadata_fields'])->pluck('label');

                return !$labels->contains('Sumber Rujukan')
                    && !$labels->contains('Wilayah (Lokasi Data)');
            })
            ->assertDontSeeText('Produsen Data');

        $this->postJson(route('template.export.json'), $payload)
            ->assertOk()
            ->assertJsonPath('data.0.sumber_produsen', 'PT PLN (Persero) Unit Induk Distribusi Bali')
            ->assertJsonPath('data.0.sumber_rujukan', 'Rujukan Statistik')
            ->assertJsonPath('data.0.wilayah', 'Kabupaten Badung')
            ->assertJsonMissingPath('informasi_data.0.produsen_data')
            ->assertJsonMissingPath('informasi_data.0.sumber_rujukan')
            ->assertJsonMissingPath('informasi_data.0.rujukan')
            ->assertJsonMissingPath('informasi_data.0.wilayah_lokasi_data')
            ->assertJsonMissing(['label' => 'Produsen Data'])
            ->assertJsonMissing(['label' => 'Sumber Rujukan'])
            ->assertJsonMissing(['label' => 'Wilayah (Lokasi Data)']);

        $excelResponse = $this->post(route('template.export.excel'), $payload);
        $excelResponse->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'template-export-');
        try {
            file_put_contents($path, $excelResponse->streamedContent());
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheetByName('Data');
            $informasiDataSheet = $spreadsheet->getSheetByName('Informasi Data');
            $producerColumn = null;
            $referenceColumn = null;
            foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
                if ($cell->getValue() === 'Sumber Produsen') {
                    $producerColumn = $cell->getColumn();
                }
                if ($cell->getValue() === 'Sumber Rujukan') {
                    $referenceColumn = $cell->getColumn();
                }
            }
            $this->assertNotNull($producerColumn);
            $this->assertNotNull($referenceColumn);
            $this->assertSame(
                'PT PLN (Persero) Unit Induk Distribusi Bali',
                $sheet->getCell($producerColumn.'3')->getValue()
            );
            $this->assertSame('Rujukan Statistik', $sheet->getCell($referenceColumn.'3')->getValue());
            $informasiDataLabels = array_column(
                $informasiDataSheet->rangeToArray('A1:A30', null, true, false),
                0
            );
            $this->assertNotContains('Produsen Data', $informasiDataLabels);
            $this->assertNotContains('Sumber Rujukan', $informasiDataLabels);
            $this->assertNotContains('Wilayah (Lokasi Data)', $informasiDataLabels);
        } finally {
            unlink($path);
        }
    }

    private function createDataWithDifferentReferenceAndDataProducers(): array
    {
        $user = User::factory()->create();
        $metadataProducer = ProdusenData::create(['nama_produsen' => 'Badan Pusat Statistik Provinsi Bali']);
        $dataProducer = ProdusenData::create(['nama_produsen' => 'PT PLN (Persero) Unit Induk Distribusi Bali']);
        $classificationId = DB::table('klasifikasi')->insertGetId([
            'nama_klasifikasi' => 'Energi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $metadataId = DB::table('metadata')->insertGetId([
            'nama' => 'Jumlah Tenaga Listrik Terjual',
            'konsep' => 'Konsep',
            'definisi' => 'Definisi',
            'klasifikasi_id' => $classificationId,
            'metodologi' => 'Metodologi',
            'penjelasan_metodologi' => 'Penjelasan',
            'tipe_data' => 'Angka',
            'satuan_data' => 'Kwh',
            'tahun_mulai_data' => '2025',
            'frekuensi_penerbitan' => 'tahunan',
            'sumber_metadata_pertama' => $metadataProducer->produsen_id,
            'tag' => 'Energi',
            'date_inputed' => now(),
            'user_id' => $user->user_id,
            'status' => 2,
        ]);
        $locationId = DB::table('location')->insertGetId(['nama_wilayah' => 'Kabupaten Badung']);
        $timeId = DB::table('time')->insertGetId([
            'decade' => 0,
            'year' => 2025,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);
        $referenceId = DB::table('rujukan')->insertGetId([
            'nama_rujukan' => 'Rujukan Statistik',
            'produsen_id' => $metadataProducer->produsen_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('data')->insert([
            'user_id' => $user->user_id,
            'metadata_id' => $metadataId,
            'location_id' => $locationId,
            'rujukan_id' => $referenceId,
            'time_id' => $timeId,
            'number_value' => 100,
            'status' => 1,
            'date_inputed' => now(),
            'produsen_id' => $dataProducer->produsen_id,
        ]);

        return [
            'metadata' => $metadataId,
            'location' => $locationId,
            'user' => $user,
        ];
    }
}
