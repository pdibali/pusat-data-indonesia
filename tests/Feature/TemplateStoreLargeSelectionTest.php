<?php

namespace Tests\Feature;

use App\Models\IsiTampilan;
use App\Models\ProdusenData;
use App\Models\Tampilan;
use App\Models\User;
use App\Services\SubscriptionLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TemplateStoreLargeSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_preview_accepts_large_selection_and_guest_wilayah_store_preserves_it(): void
    {
        ['user' => $user, 'location_id' => $locationId, 'metadata_ids' => $metadataIds] = $this->createLargeSelection();

        $this->postJson(route('template.fetch_preview'), [
            'metadata_ids' => $metadataIds,
            'location_ids' => [$locationId],
        ])->assertOk()
            ->assertJsonPath('success', true);

        $metadataLocationIds = array_fill_keys(
            array_map('strval', $metadataIds),
            [$locationId]
        );

        $response = $this->postJson(route('template.store'), [
            'nama_tampilan' => 'Template Badung',
            'jenis_template' => 'wilayah',
            'metadata_ids' => $metadataIds,
            'metadata_location_ids' => json_encode($metadataLocationIds),
        ]);

        $response->assertOk()
            ->assertJsonPath('storage', 'local')
            ->assertJsonCount(1201, 'template_data.metadata_ids')
            ->assertJsonPath("template_data.metadata_location_ids.{$metadataIds[0]}.0", $locationId);
    }

    public function test_authenticated_classification_store_persists_metadata_locations_and_classification(): void
    {
        ['user' => $user, 'location_id' => $locationId, 'metadata_ids' => $metadataIds] = $this->createLargeSelection();

        $metadataLocationIds = array_fill_keys(
            array_map('strval', $metadataIds),
            [$locationId]
        );

        $this->mock(SubscriptionLimitsService::class, function ($mock): void {
            $mock->shouldReceive('canCreateTemplate')->once()->andReturnTrue();
        });

        $response = $this->actingAs($user)->postJson(route('template.store'), [
            'nama_tampilan' => 'Template Klasifikasi',
            'jenis_template' => 'klasifikasi',
            'klasifikasi' => 'Klasifikasi Uji',
            'metadata_ids' => $metadataIds,
            'metadata_location_ids' => json_encode($metadataLocationIds),
        ]);

        $response->assertOk()
            ->assertJsonPath('storage', 'database');

        $items = IsiTampilan::where('tampilan_id', $response->json('tampilan_id'))->get();

        $this->assertCount(1201, $items);
        $this->assertTrue($items->every(
            fn (IsiTampilan $item): bool => $item->location_ids === [$locationId]
        ));
        $this->assertSame(
            'Klasifikasi Uji',
            Tampilan::find($response->json('tampilan_id'))->filter_params['klasifikasi']
        );
    }

    public function test_guest_classification_template_keeps_large_selection_and_sort_order(): void
    {
        ['location_id' => $locationId, 'metadata_ids' => $metadataIds] = $this->createLargeSelection();

        $metadataLocationIds = array_fill_keys(
            array_map('strval', $metadataIds),
            [$locationId]
        );

        $this->postJson(route('template.store'), [
            'nama_tampilan' => 'Template Klasifikasi',
            'jenis_template' => 'klasifikasi',
            'klasifikasi' => 'Klasifikasi Uji',
            'metadata_ids' => $metadataIds,
            'metadata_location_ids' => json_encode($metadataLocationIds),
            'urutan_by' => ['klasifikasi', 'wilayah'],
        ])->assertOk()
            ->assertJsonPath('storage', 'local')
            ->assertJsonCount(1201, 'template_data.metadata_ids')
            ->assertJsonPath("template_data.metadata_location_ids.{$metadataIds[0]}.0", $locationId)
            ->assertJsonPath('template_data.urutan_by.0', 'klasifikasi')
            ->assertJsonPath('template_data.urutan_by.1', 'wilayah');
    }

    public function test_guest_metadata_template_keeps_large_selection_and_sort_order(): void
    {
        ['location_id' => $locationId, 'metadata_ids' => $metadataIds] = $this->createLargeSelection();

        $metadataLocationIds = array_fill_keys(
            array_map('strval', $metadataIds),
            [$locationId]
        );

        $this->postJson(route('template.store'), [
            'nama_tampilan' => 'Template Metadata',
            'jenis_template' => 'metadata',
            'metadata_ids' => $metadataIds,
            'metadata_location_ids' => json_encode($metadataLocationIds),
            'urutan_by' => ['wilayah'],
        ])->assertOk()
            ->assertJsonPath('storage', 'local')
            ->assertJsonCount(1201, 'template_data.metadata_ids')
            ->assertJsonPath("template_data.metadata_location_ids.{$metadataIds[0]}.0", $locationId)
            ->assertJsonPath('template_data.urutan_by.0', 'wilayah');
    }

    public function test_guest_table_data_keeps_large_selection_scoped_to_selected_location(): void
    {
        ['user' => $user, 'location_id' => $locationId, 'metadata_ids' => $metadataIds] = $this->createLargeSelection();
        $otherLocationId = DB::table('location')->insertGetId([
            'nama_wilayah' => 'Provinsi Nusa Tenggara Timur',
        ]);
        $timeId = DB::table('time')->insertGetId([
            'decade' => 0,
            'year' => 2025,
            'semester' => 0,
            'quarter' => 0,
            'month' => 0,
        ]);
        $producerId = DB::table('produsen_data')->value('produsen_id');
        $referenceId = DB::table('rujukan')->insertGetId([
            'nama_rujukan' => 'Rujukan Uji',
            'produsen_id' => $producerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('data')->insert([
            [
                'user_id' => $user->user_id,
                'metadata_id' => $metadataIds[0],
                'location_id' => $locationId,
                'rujukan_id' => $referenceId,
                'time_id' => $timeId,
                'number_value' => 100,
                'status' => 1,
                'date_inputed' => now(),
                'produsen_id' => $producerId,
            ],
            [
                'user_id' => $user->user_id,
                'metadata_id' => $metadataIds[0],
                'location_id' => $otherLocationId,
                'rujukan_id' => $referenceId,
                'time_id' => $timeId,
                'number_value' => 999,
                'status' => 1,
                'date_inputed' => now(),
                'produsen_id' => $producerId,
            ],
        ]);

        $metadataLocationIds = array_fill_keys(
            array_map('strval', $metadataIds),
            [$locationId]
        );

        $response = $this->postJson(route('template.table_data_guest'), [
            'metadata_ids' => $metadataIds,
            'metadata_location_ids' => json_encode($metadataLocationIds),
            'frekuensi' => 'tahunan',
            'year_from' => 2025,
            'year_to' => 2025,
        ]);

        $response->assertOk()
            ->assertJsonPath('total', 1201)
            ->assertJsonPath('rows.0.location_id', $locationId)
            ->assertJsonPath('rows.0.values.2025', '100.00');
        $this->assertTrue(
            collect($response->json('rows'))->every(
                fn (array $row): bool => $row['location_id'] === $locationId
            )
        );
    }

    private function createLargeSelection(): array
    {
        $user = User::factory()->create();
        $producer = ProdusenData::create(['nama_produsen' => 'Produsen Uji']);
        $classificationId = DB::table('klasifikasi')->insertGetId([
            'nama_klasifikasi' => 'Klasifikasi Uji',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $locationId = DB::table('location')->insertGetId([
            'nama_wilayah' => 'Kabupaten Badung',
        ]);

        foreach (array_chunk(range(1, 1201), 100) as $batch) {
            $metadata = [];
            foreach ($batch as $number) {
                $metadata[] = [
                    'nama' => "Metadata Uji {$number}",
                    'konsep' => 'Konsep',
                    'definisi' => 'Definisi',
                    'klasifikasi_id' => $classificationId,
                    'metodologi' => 'Metodologi',
                    'penjelasan_metodologi' => 'Penjelasan',
                    'tipe_data' => 'Angka',
                    'satuan_data' => 'Persen',
                    'tahun_mulai_data' => '2025',
                    'frekuensi_penerbitan' => 'tahunan',
                    'sumber_metadata_pertama' => $producer->produsen_id,
                    'tag' => 'Uji',
                    'date_inputed' => now(),
                    'user_id' => $user->user_id,
                    'status' => 2,
                ];
            }

            DB::table('metadata')->insert($metadata);
        }

        $metadataIds = DB::table('metadata')->orderBy('metadata_id')->pluck('metadata_id')->all();

        return [
            'user' => $user,
            'location_id' => $locationId,
            'metadata_ids' => $metadataIds,
        ];
    }
}
