<?php

namespace Tests\Feature;

use App\Models\ProdusenData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdusenNameLengthTest extends TestCase
{
    use RefreshDatabase;

    public function test_produsen_names_longer_than_100_characters_can_be_created_and_updated(): void
    {
        $this->actingAs(User::factory()->create());
        $name = str_repeat('D', 255);

        $response = $this->post(route('admin.produsen.store'), [
            'nama_produsen' => $name,
        ]);

        $response->assertRedirect(route('admin.produsen.index'));
        $this->assertDatabaseHas('produsen_data', ['nama_produsen' => $name]);

        $produsen = ProdusenData::where('nama_produsen', $name)->firstOrFail();
        $updatedName = str_repeat('K', 255);

        $response = $this->put(route('admin.produsen.update', $produsen), [
            'nama_produsen' => $updatedName,
        ]);

        $response->assertRedirect(route('admin.produsen.index'));
        $this->assertDatabaseHas('produsen_data', ['nama_produsen' => $updatedName]);
    }

    public function test_produsen_names_longer_than_255_characters_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('admin.produsen.store'), [
            'nama_produsen' => str_repeat('D', 256),
        ]);

        $response->assertSessionHasErrors('nama_produsen');
        $this->assertDatabaseCount('produsen_data', 0);
    }
}
