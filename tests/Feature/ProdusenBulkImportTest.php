<?php

namespace Tests\Feature;

use App\Imports\DataImport;
use App\Imports\ProdusenBulkImport;
use App\Models\ProdusenData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProdusenBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private array $workbookPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->workbookPaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_bulk_import_adds_all_rows_with_optional_fields(): void
    {
        $path = $this->makeWorkbook([
            ['nama_produsen', 'nama_contact_person', 'kontak', 'email', 'alamat', 'status'],
            ['Dinas Statistik Bali', 'Ibu Made', '081234567890', 'made@example.test', 'Denpasar', 'aktif'],
            ['Badan Data Bali', '', '', '', 'Bali', ''],
        ]);

        $result = (new ProdusenBulkImport)->import($path);

        $this->assertSame([], $result['errors']);
        $this->assertSame(2, $result['imported']);
        $this->assertDatabaseHas('produsen_data', [
            'nama_produsen' => 'Dinas Statistik Bali',
            'nama_contact_person' => 'Ibu Made',
            'status' => 1,
        ]);
        $this->assertDatabaseHas('produsen_data', [
            'nama_produsen' => 'Badan Data Bali',
            'status' => 1,
        ]);
    }

    public function test_bulk_import_rejects_the_whole_file_when_any_name_is_duplicate(): void
    {
        ProdusenData::create(['nama_produsen' => 'Produsen Terdaftar', 'status' => 1]);
        $path = $this->makeWorkbook([
            ['nama_produsen', 'email'],
            ['Produsen Baru', 'baru@example.test'],
            ['Produsen Terdaftar', 'lama@example.test'],
        ]);

        $result = (new ProdusenBulkImport)->import($path);

        $this->assertSame(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertDatabaseMissing('produsen_data', ['nama_produsen' => 'Produsen Baru']);
    }

    public function test_excel_upload_shows_preview_before_confirming_import(): void
    {
        $this->withoutMiddleware();
        $this->actingAs(User::factory()->create());
        $this->withViewErrors([])->get(route('admin.produsen.index'))
            ->assertOk()
            ->assertSee('Bulk Input Excel');
        $this->withViewErrors([])->get(route('admin.produsen.import'))->assertOk();
        $this->get(route('admin.produsen.import.template'))->assertOk();

        $path = $this->makeWorkbook([
            ['nama_produsen', 'email'],
            ['Produsen dari Upload', 'upload@example.test'],
        ]);

        $response = $this->post(route('admin.produsen.import.preview'), [
            'file_excel' => UploadedFile::fake()->createWithContent(
                'produsen.xlsx',
                file_get_contents($path)
            ),
        ]);

        $response->assertOk()
            ->assertSee('Pratinjau Produsen')
            ->assertSee('Produsen dari Upload')
            ->assertSee('Konfirmasi dan Simpan');
        $response->assertViewHas('token');
        $this->assertDatabaseMissing('produsen_data', [
            'nama_produsen' => 'Produsen dari Upload',
        ]);

        $token = $response->viewData('token');
        $confirmResponse = $this->post(route('admin.produsen.import.confirm'), ['token' => $token]);

        $confirmResponse->assertRedirect(route('admin.produsen.index'));
        $confirmResponse->assertSessionHas('success');
        $this->assertDatabaseHas('produsen_data', [
            'nama_produsen' => 'Produsen dari Upload',
            'email' => 'upload@example.test',
        ]);
    }

    public function test_invalid_excel_preview_displays_problems_and_cannot_be_confirmed(): void
    {
        $this->withoutMiddleware();
        $this->actingAs(User::factory()->create());
        $path = $this->makeWorkbook([
            ['nama_produsen', 'email'],
            ['Produsen Baru', 'email-tidak-valid'],
            ['', 'lain@example.test'],
        ]);

        $response = $this->withViewErrors([])->post(route('admin.produsen.import.preview'), [
            'file_excel' => UploadedFile::fake()->createWithContent(
                'produsen.xlsx',
                file_get_contents($path)
            ),
        ]);

        $response->assertOk()
            ->assertSee('Pratinjau Produsen')
            ->assertSee('Masalah yang ditemukan')
            ->assertDontSee('Konfirmasi dan Simpan');
        $response->assertViewHas('token', null);
        $this->assertDatabaseMissing('produsen_data', ['nama_produsen' => 'Produsen Baru']);
    }

    public function test_data_import_accepts_an_exact_registered_producer_name(): void
    {
        ProdusenData::create(['nama_produsen' => 'Dinas Data Bali', 'status' => 1]);
        $path = $this->makeDataImportWorkbook('Dinas Data Bali');

        $result = (new DataImport)->import($path);

        $this->assertTrue($result['success']);
        $this->assertArrayNotHasKey('invalid_produsen_rows', $result);
    }

    public function test_data_import_blocks_blank_or_unregistered_producers_before_importing(): void
    {
        ProdusenData::create(['nama_produsen' => 'Dinas Data Bali', 'status' => 1]);

        foreach ([null, 'Produsen Tidak Terdaftar', 'dinas data bali'] as $value) {
            $path = $this->makeDataImportWorkbook($value);

            $preview = (new DataImport)->preview($path);
            $result = (new DataImport)->import($path);

            $this->assertSame(1, $preview['invalid_produsen_count']);
            $this->assertFalse($result['success']);
            $this->assertSame(0, $result['imported']);
            $this->assertNotEmpty($result['invalid_produsen_rows']);
        }
    }

    private function makeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        return $this->saveWorkbook($spreadsheet);
    }

    private function makeDataImportWorkbook(?string $producer): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Import');
        $sheet->fromArray([
            [],
            [],
            ['metadata_id', 'nama_metadata', 'location_id', 'nama_wilayah', 'produsen_id'],
            [1, 'Metadata Uji', 1, 'Wilayah Uji', $producer],
        ], null, 'A1');

        return $this->saveWorkbook($spreadsheet);
    }

    private function saveWorkbook(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'produsen-import-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->workbookPaths[] = $path;

        return $path;
    }
}
