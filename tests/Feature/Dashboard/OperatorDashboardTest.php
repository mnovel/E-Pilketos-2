<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_dashboard_loads()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('operator.dashboard');
    }

    public function test_dashboard_shows_active_sessions()
    {
        $operator = $this->createOperator();

        $election = Election::factory()->active()->create();
        $class = ClassRoom::factory()->create();

        $activeSession = ElectionSession::factory()->active()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('activeSessions', function ($sessions) use ($activeSession) {
            return $sessions->contains('id', $activeSession->id);
        });
    }

    public function test_dashboard_shows_today_sessions()
    {
        $operator = $this->createOperator();

        $election = Election::factory()->active()->create();
        $class = ClassRoom::factory()->create();

        $todaySession = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'tanggal'     => now()->toDateString(),
        ]);

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('todaySessions', function ($sessions) use ($todaySession) {
            return $sessions->contains('id', $todaySession->id);
        });
    }

    public function test_dashboard_stats_calculated_correctly()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertViewHas('stats', function ($stats) {
            return isset($stats['total_sessions_today'])
                && isset($stats['total_active'])
                && isset($stats['total_checkin_today'])
                && isset($stats['total_voted_today']);
        });
    }

    public function test_admin_cannot_access_operator_dashboard()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('operator.dashboard'));

        $response->assertForbidden();
    }

    public function test_voter_cannot_access_operator_dashboard()
    {
        $voter = $this->createVoter();

        $response = $this->actingAs($voter)->get(route('operator.dashboard'));

        $response->assertForbidden();
    }
}
