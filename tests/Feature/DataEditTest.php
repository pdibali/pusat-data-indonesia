<?php

namespace Tests\Feature;

use App\Models\Data;
use App\Models\Group;
use App\Models\Klasifikasi;
use App\Models\Location;
use App\Models\Metadata;
use App\Models\ProdusenData;
use App\Models\Rujukan;
use App\Models\User;
use App\Models\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'produsen_id' => $produsen->produsen_id,
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

        $datum = Data::create([
            'user_id' => $user->user_id,
            'metadata_id' => $metadata->metadata_id,
            'location_id' => $location->location_id,
            'time_id' => $time->time_id,
            'rujukan_id' => $rujukan->rujukan_id,
            'number_value' => 100,
            'status' => Data::STATUS_PENDING,
            'workflow_status' => Data::WORKFLOW_DRAFT,
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

        $datum->status = Data::STATUS_PENDING;
        $datum->save();

        $response = $this->actingAs($user)->get(route('data.edit', $datum));
        $response->assertOk();
        $response->assertSee('Edit Data');

        $updateResponse = $this->actingAs($user)->put(route('data.update', $datum), [
            'metadata_id' => $metadata->metadata_id,
            'location_id' => $location->location_id,
            'time_id' => $time->time_id,
            'rujukan_id' => $rujukan->rujukan_id,
            'number_value' => 125.5,
        ]);

        $updateResponse->assertRedirect(route('data.show', $datum));
        $this->assertDatabaseHas('data', ['id' => $datum->id, 'number_value' => '125.50']);
    }
}
