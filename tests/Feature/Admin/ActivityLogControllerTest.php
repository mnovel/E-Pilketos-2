<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Bikin log dengan created_at custom.
     * Karena model pakai timestamps = false, created_at di-set manual via property.
     */
    protected function makeLog(array $attrs = []): ActivityLog
    {
        $createdAt = $attrs['created_at'] ?? now();
        unset($attrs['created_at']);

        $log = ActivityLog::create(array_merge([
            'action' => 'test.action',
        ], $attrs));

        // Set created_at via property biar tidak kena fillable guard
        $log->created_at = $createdAt;
        $log->save();

        return $log;
    }

    // =========================================================
    // ACCESS
    // =========================================================

    public function test_admin_can_view_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.activity-logs.index');
        $response->assertViewHas('logs');
        $response->assertViewHas('stats');
        $response->assertViewHas('actions');
        $response->assertViewHas('perPage');
    }

    public function test_guest_redirected_from_index()
    {
        $this->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_index()
    {
        $this->actingAs($this->createOperator())
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_voter_cannot_access_index()
    {
        $this->actingAs($this->createVoter())
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    // =========================================================
    // INDEX — CONTENT
    // =========================================================

    public function test_index_shows_logs()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'election.created', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'voter.verified',   'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 2;
        });
    }

    public function test_index_ordered_by_latest()
    {
        $admin = $this->createAdmin();

        $old = $this->makeLog([
            'action'     => 'old.action',
            'created_at' => now()->subDays(2),
        ]);

        $new = $this->makeLog([
            'action'     => 'new.action',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('logs', function ($logs) use ($new, $old) {
            $ids = $logs->pluck('id')->toArray();
            return $ids[0] === $new->id && $ids[1] === $old->id;
        });
    }

    public function test_index_passes_actions_list()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'election.created']);
        $this->makeLog(['action' => 'voter.verified']);
        $this->makeLog(['action' => 'election.created']); // duplikat

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('actions', function ($actions) {
            return $actions->contains('election.created')
                && $actions->contains('voter.verified')
                && $actions->count() === 2; // distinct
        });
    }

    // =========================================================
    // FILTER ROLE
    // =========================================================

    public function test_filter_by_system_role()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'vote.submitted', 'user_id' => null]);
        $this->makeLog(['action' => 'auth.login',     'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['role' => 'system']));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 1
                && $logs->first()->action === 'vote.submitted'
                && $logs->first()->user_id === null;
        });
    }

    public function test_filter_by_admin_role()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        $this->makeLog(['action' => 'x', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'y', 'user_id' => $operator->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['role' => UserRole::ADMIN->value]));

        $response->assertViewHas('logs', function ($logs) use ($admin) {
            return $logs->total() === 1
                && $logs->first()->user_id === $admin->id;
        });
    }

    public function test_filter_by_operator_role()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        $this->makeLog(['action' => 'x', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'y', 'user_id' => $operator->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['role' => UserRole::OPERATOR->value]));

        $response->assertViewHas('logs', function ($logs) use ($operator) {
            return $logs->total() === 1
                && $logs->first()->user_id === $operator->id;
        });
    }

    public function test_filter_by_voter_role()
    {
        $admin = $this->createAdmin();
        $voter = $this->createVoter();

        $this->makeLog(['action' => 'x', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'y', 'user_id' => $voter->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['role' => UserRole::VOTER->value]));

        $response->assertViewHas('logs', function ($logs) use ($voter) {
            return $logs->total() === 1
                && $logs->first()->user_id === $voter->id;
        });
    }

    // =========================================================
    // FILTER ACTION
    // =========================================================

    public function test_filter_by_action()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'election.created', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'voter.verified',   'user_id' => $admin->id]);
        $this->makeLog(['action' => 'election.created', 'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['action' => 'election.created']));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 2
                && $logs->every(fn($l) => $l->action === 'election.created');
        });
    }

    // =========================================================
    // FILTER DATE
    // =========================================================

    public function test_filter_by_date_from()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'old', 'created_at' => now()->subDays(5)]);
        $this->makeLog(['action' => 'mid', 'created_at' => now()->subDays(2)]);
        $this->makeLog(['action' => 'new', 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', [
                'date_from' => now()->subDays(3)->toDateString(),
            ]));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 2;
        });
    }

    public function test_filter_by_date_to()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'old', 'created_at' => now()->subDays(5)]);
        $this->makeLog(['action' => 'mid', 'created_at' => now()->subDays(2)]);
        $this->makeLog(['action' => 'new', 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', [
                'date_to' => now()->subDays(3)->toDateString(),
            ]));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 1;
        });
    }

    public function test_filter_by_both_dates()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'a', 'created_at' => now()->subDays(10)]);
        $this->makeLog(['action' => 'b', 'created_at' => now()->subDays(4)]);
        $this->makeLog(['action' => 'c', 'created_at' => now()->subDay()]);
        $this->makeLog(['action' => 'd', 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', [
                'date_from' => now()->subDays(5)->toDateString(),
                'date_to'   => now()->subDay()->toDateString(),
            ]));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 2; // b + c
        });
    }

    // =========================================================
    // SEARCH
    // =========================================================

    public function test_search_by_action()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'election.created', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'voter.verified',   'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['search' => 'election']));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 1
                && $logs->first()->action === 'election.created';
        });
    }

    public function test_search_by_user_name()
    {
        $admin = $this->createAdmin();

        $budi = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => UserRole::OPERATOR,
        ]);

        $this->makeLog(['action' => 'x', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'y', 'user_id' => $budi->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['search' => 'Budi']));

        $response->assertViewHas('logs', function ($logs) use ($budi) {
            return $logs->total() === 1
                && $logs->first()->user_id === $budi->id;
        });
    }

    public function test_search_by_user_email()
    {
        $admin = $this->createAdmin();

        $target = User::factory()->create([
            'email' => 'spesial@pilketos.test',
            'role'  => UserRole::OPERATOR,
        ]);

        $this->makeLog(['action' => 'x', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'y', 'user_id' => $target->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['search' => 'spesial']));

        $response->assertViewHas('logs', function ($logs) use ($target) {
            return $logs->total() === 1
                && $logs->first()->user_id === $target->id;
        });
    }

    public function test_search_does_not_match_system_logs_by_user_name()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['action' => 'vote.submitted', 'user_id' => null]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['search' => 'Budi']));

        $response->assertViewHas('logs', function ($logs) {
            return $logs->total() === 0;
        });
    }

    // =========================================================
    // COMBINED FILTER
    // =========================================================

    public function test_filter_role_and_action_combined()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        // 3 log: admin/election.created, operator/election.created, admin/voter.verified
        $this->makeLog(['action' => 'election.created', 'user_id' => $admin->id]);
        $this->makeLog(['action' => 'election.created', 'user_id' => $operator->id]);
        $this->makeLog(['action' => 'voter.verified',   'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', [
                'role'   => UserRole::ADMIN->value,
                'action' => 'election.created',
            ]));

        $response->assertViewHas('logs', function ($logs) use ($admin) {
            return $logs->total() === 1
                && $logs->first()->user_id === $admin->id
                && $logs->first()->action === 'election.created';
        });
    }

    // =========================================================
    // PAGINATION
    // =========================================================

    public function test_per_page_default_is_50()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('perPage', 50);
    }

    public function test_per_page_accepts_valid_values()
    {
        $admin = $this->createAdmin();

        foreach ([20, 50, 100, 200] as $perPage) {
            $response = $this->actingAs($admin)
                ->get(route('admin.activity-logs.index', ['per_page' => $perPage]));

            $response->assertViewHas('perPage', $perPage);
        }
    }

    public function test_per_page_rejects_invalid_value()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index', ['per_page' => 9999]));

        $response->assertViewHas('perPage', 50);
    }

    // =========================================================
    // STATS
    // =========================================================

    public function test_stats_total()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['user_id' => $admin->id]);
        $this->makeLog(['user_id' => $admin->id]);
        $this->makeLog(['user_id' => null]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 3;
        });
    }

    public function test_stats_today()
    {
        $admin = $this->createAdmin();

        $this->makeLog(['created_at' => now(), 'user_id' => $admin->id]);
        $this->makeLog(['created_at' => now(), 'user_id' => $admin->id]);
        $this->makeLog(['created_at' => now()->subDays(5), 'user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['today'] === 2;
        });
    }

    public function test_stats_unique_user()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        // 2 log admin, 1 log operator, 1 log system (null)
        $this->makeLog(['user_id' => $admin->id]);
        $this->makeLog(['user_id' => $admin->id]);
        $this->makeLog(['user_id' => $operator->id]);
        $this->makeLog(['user_id' => null]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'));

        $response->assertViewHas('stats', function ($stats) {
            // NULL tidak dihitung oleh COUNT DISTINCT
            return $stats['unique_user'] === 2;
        });
    }

    // =========================================================
    // SHOW
    // =========================================================

    public function test_admin_can_view_show()
    {
        $admin = $this->createAdmin();
        $log = $this->makeLog([
            'action'  => 'election.created',
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.show', $log));

        $response->assertOk();
        $response->assertViewIs('admin.activity-logs.show');
        $response->assertViewHas('log');
    }

    public function test_show_loads_user_relation()
    {
        $admin = $this->createAdmin();
        $log = $this->makeLog([
            'action'  => 'election.created',
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.show', $log));

        $response->assertViewHas('log', function ($l) {
            return $l->relationLoaded('user');
        });
    }

    public function test_show_system_log_with_null_user()
    {
        $admin = $this->createAdmin();
        $log = $this->makeLog([
            'action'  => 'vote.submitted',
            'user_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.activity-logs.show', $log));

        $response->assertOk();
        $response->assertViewHas('log', function ($l) {
            return $l->user === null;
        });
    }

    public function test_show_guest_redirected()
    {
        $log = $this->makeLog(['user_id' => null]);

        $this->get(route('admin.activity-logs.show', $log))
            ->assertRedirect(route('login'));
    }

    public function test_show_operator_forbidden()
    {
        $log = $this->makeLog(['user_id' => null]);

        $this->actingAs($this->createOperator())
            ->get(route('admin.activity-logs.show', $log))
            ->assertForbidden();
    }
}
