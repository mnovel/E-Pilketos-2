<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ElectionStatus;
use App\Enums\UserRole;
use App\Enums\VoterStatus;
use App\Models\CheckinDevice;
use App\Models\Election;
use App\Models\User;
use App\Models\VotingDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_loads()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    public function test_dashboard_shows_correct_voter_stats()
    {
        $admin = $this->createAdmin();

        User::factory()->count(3)->pending()->create(['role' => UserRole::VOTER]);
        User::factory()->count(5)->voter()->create(['role' => UserRole::VOTER]);
        User::factory()->count(2)->rejected()->create(['role' => UserRole::VOTER]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_voters'] === 10
                && $stats['pending_voters'] === 3
                && $stats['verified_voters'] === 5
                && $stats['rejected_voters'] === 2;
        });
    }

    public function test_operator_cannot_access_admin_dashboard()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_voter_cannot_access_admin_dashboard()
    {
        $voter = $this->createVoter();

        $response = $this->actingAs($voter)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_redirected_to_login()
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    // ==========================================
    // LIVE STATS
    // ==========================================

    public function test_live_stats_returns_json()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.live-stats'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'active_election',
            'updated_at',
        ]);
    }

    public function test_live_stats_returns_null_active_election_when_none()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.live-stats'));

        $response->assertJson(['active_election' => null]);
    }

    public function test_live_stats_returns_active_election_data()
    {
        $admin = $this->createAdmin();

        $election = Election::factory()->active()->create([
            'title'    => 'Pemilihan Live',
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.live-stats'));

        $response->assertJsonPath('active_election.id', $election->id);
        $response->assertJsonPath('active_election.title', 'Pemilihan Live');
        $response->assertJsonStructure([
            'active_election' => [
                'id',
                'title',
                'tahun_ajaran',
                'total_voters',
                'checked_in',
                'has_voted',
                'active_sessions',
                'checkin_online',
                'checkin_total',
                'voting_online',
                'voting_total',
                'participation_pct',
            ],
        ]);
    }

    public function test_live_stats_counts_online_devices()
    {
        $admin = $this->createAdmin();

        $election = Election::factory()->active()->create();

        // 2 checkin online (baru ping)
        CheckinDevice::create([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-A',
            'device_token'     => 'TOKEN-A',
            'token_expired_at' => now()->addMinutes(5),
            'last_ping_at'     => now(),
        ]);
        CheckinDevice::create([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-B',
            'device_token'     => 'TOKEN-B',
            'token_expired_at' => now()->addMinutes(5),
            'last_ping_at'     => now(),
        ]);

        // 1 checkin offline (lama tidak ping)
        CheckinDevice::create([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-OFF',
            'device_token'     => 'TOKEN-OFF',
            'token_expired_at' => now()->addMinutes(5),
            'last_ping_at'     => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.live-stats'));

        $response->assertJsonPath('active_election.checkin_online', 2);
        $response->assertJsonPath('active_election.checkin_total', 3);
    }
}
