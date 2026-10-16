<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Http\Middleware\EnsureSingleActiveSession;
use Database\Seeders\GroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GroupSeeder::class);
    }

    public function test_authenticated_html_page_visits_are_logged_without_query_strings(): void
    {
        $user = User::factory()->create([
            'group_id' => 1,
            'name' => 'Activity Test User',
        ]);

        $this->actingAs($user)
            ->get('/data?token=must-not-be-recorded')
            ->assertOk();

        $this->assertDatabaseHas('user_activity_logs', [
            'user_id' => $user->user_id,
            'user_name' => 'Activity Test User',
            'method' => 'GET',
            'request_path' => '/data',
            'status_code' => 200,
        ]);

        $this->assertDatabaseMissing('user_activity_logs', [
            'request_path' => '/data?token=must-not-be-recorded',
        ]);
    }

    public function test_activity_log_view_is_restricted_to_administrators(): void
    {
        $customer = User::factory()->create([
            'group_id' => 3,
        ]);

        $this->withoutMiddleware(EnsureSingleActiveSession::class);

        $this->actingAs($customer)
            ->get(route('admin.activity_logs.index'))
            ->assertForbidden();
    }

    public function test_pengelola_cannot_view_or_delete_activity_logs(): void
    {
        $pengelola = User::factory()->create([
            'group_id' => 2,
        ]);

        $this->withoutMiddleware(EnsureSingleActiveSession::class);

        $this->actingAs($pengelola)
            ->get(route('admin.activity_logs.index'))
            ->assertForbidden();

        $this->actingAs($pengelola)
            ->delete(route('admin.activity_logs.destroy_older_than'), ['days' => 14])
            ->assertForbidden();
    }

    public function test_reviewers_can_view_activity_logs(): void
    {
        $reviewer = User::factory()->create([
            'group_id' => 1,
        ]);

        UserActivityLog::create([
            'user_id' => $reviewer->user_id,
            'user_name' => $reviewer->name,
            'method' => 'GET',
            'route_name' => 'data.index',
            'request_path' => '/data',
            'status_code' => 200,
            'duration_ms' => 20,
        ]);

        $this->actingAs($reviewer)
            ->get(route('admin.activity_logs.index'))
            ->assertOk()
            ->assertSee($reviewer->name)
            ->assertSee('/data');
    }

    public function test_reviewers_can_filter_activity_logs_by_selected_day_range(): void
    {
        $reviewer = User::factory()->create([
            'group_id' => 1,
        ]);

        foreach ([
            ['path' => '/recent-page', 'created_at' => now()->subDays(6)],
            ['path' => '/older-page', 'created_at' => now()->subDays(8)],
        ] as $entry) {
            UserActivityLog::create([
                'user_id' => $reviewer->user_id,
                'user_name' => $reviewer->name,
                'method' => 'GET',
                'route_name' => 'data.index',
                'request_path' => $entry['path'],
                'status_code' => 200,
                'duration_ms' => 20,
                'created_at' => $entry['created_at'],
            ]);
        }

        $this->actingAs($reviewer)
            ->get(route('admin.activity_logs.index', ['days' => 7]))
            ->assertOk()
            ->assertSee('/recent-page')
            ->assertDontSee('/older-page');
    }

    public function test_ajax_filter_returns_matching_logs_and_pagination_data_as_json(): void
    {
        $reviewer = User::factory()->create([
            'group_id' => 1,
        ]);

        UserActivityLog::create([
            'user_id' => $reviewer->user_id,
            'user_name' => $reviewer->name,
            'method' => 'GET',
            'route_name' => 'data.index',
            'request_path' => '/today-page',
            'status_code' => 200,
            'duration_ms' => 20,
            'created_at' => now(),
        ]);

        UserActivityLog::create([
            'user_id' => $reviewer->user_id,
            'user_name' => $reviewer->name,
            'method' => 'GET',
            'route_name' => 'data.index',
            'request_path' => '/old-page',
            'status_code' => 200,
            'duration_ms' => 20,
            'created_at' => now()->subDays(3),
        ]);

        $this->actingAs($reviewer)
            ->getJson(route('admin.activity_logs.index', ['days' => 1]))
            ->assertOk()
            ->assertJsonPath('days', 1)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('rows.0.request_path', '/today-page');
    }

    public function test_reviewer_can_delete_activity_logs_older_than_selected_retention(): void
    {
        $reviewer = User::factory()->create([
            'group_id' => 1,
        ]);

        foreach ([
            ['path' => '/keep-recent', 'created_at' => now()->subDays(13)],
            ['path' => '/delete-old', 'created_at' => now()->subDays(15)],
            ['path' => '/keep-at-30-days', 'created_at' => now()->subDays(29)],
            ['path' => '/delete-over-30-days', 'created_at' => now()->subDays(31)],
        ] as $entry) {
            UserActivityLog::create([
                'user_id' => $reviewer->user_id,
                'user_name' => $reviewer->name,
                'method' => 'GET',
                'route_name' => 'data.index',
                'request_path' => $entry['path'],
                'status_code' => 200,
                'duration_ms' => 20,
                'created_at' => $entry['created_at'],
            ]);
        }

        $this->actingAs($reviewer)
            ->delete(route('admin.activity_logs.destroy_older_than'), ['days' => 14])
            ->assertRedirect();

        $this->assertDatabaseHas('user_activity_logs', ['request_path' => '/keep-recent']);
        $this->assertDatabaseMissing('user_activity_logs', ['request_path' => '/delete-old']);
        $this->assertDatabaseMissing('user_activity_logs', ['request_path' => '/keep-at-30-days']);
        $this->assertDatabaseMissing('user_activity_logs', ['request_path' => '/delete-over-30-days']);
    }

    public function test_reviewer_can_delete_only_logs_older_than_30_days(): void
    {
        $reviewer = User::factory()->create([
            'group_id' => 1,
        ]);

        foreach ([
            ['path' => '/keep-under-30-days', 'created_at' => now()->subDays(29)],
            ['path' => '/delete-over-30-days', 'created_at' => now()->subDays(31)],
        ] as $entry) {
            UserActivityLog::create([
                'user_id' => $reviewer->user_id,
                'user_name' => $reviewer->name,
                'method' => 'GET',
                'route_name' => 'data.index',
                'request_path' => $entry['path'],
                'status_code' => 200,
                'duration_ms' => 20,
                'created_at' => $entry['created_at'],
            ]);
        }

        $this->actingAs($reviewer)
            ->delete(route('admin.activity_logs.destroy_older_than'), ['days' => 30])
            ->assertRedirect();

        $this->assertDatabaseHas('user_activity_logs', ['request_path' => '/keep-under-30-days']);
        $this->assertDatabaseMissing('user_activity_logs', ['request_path' => '/delete-over-30-days']);
    }

    public function test_non_administrators_cannot_delete_activity_logs(): void
    {
        $customer = User::factory()->create([
            'group_id' => 3,
        ]);

        $this->withoutMiddleware(EnsureSingleActiveSession::class);

        $this->actingAs($customer)
            ->delete(route('admin.activity_logs.destroy_older_than'), ['days' => 14])
            ->assertForbidden();
    }
}
