<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\VoterStatus;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoterDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_voter_dashboard_loads()
    {
        $voter = $this->createVoter();

        $response = $this->actingAs($voter)->get(route('voter.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('voter.dashboard');
        $response->assertViewHas('user');
        $response->assertViewHas('isVerified');
    }

    public function test_verified_voter_flag_is_true()
    {
        $voter = $this->createVoter();
        $voter->update(['status' => VoterStatus::VERIFIED]);

        $response = $this->actingAs($voter)->get(route('voter.dashboard'));

        $response->assertViewHas('isVerified', true);
    }

    public function test_unverified_voter_flag_is_false()
    {
        $voter = User::factory()->pending()->create();
        $voter->assignRole('voter');

        $response = $this->actingAs($voter)->get(route('voter.dashboard'));

        $response->assertViewHas('isVerified', false);
    }

    public function test_dashboard_shows_sessions_for_voter_class()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->active()->create();

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'status'      => SessionStatus::SCHEDULED,
        ]);

        $response = $this->actingAs($voter)->get(route('voter.dashboard'));

        $response->assertViewHas('sessions', function ($sessions) use ($session) {
            return $sessions->contains('id', $session->id);
        });
    }

    public function test_dashboard_does_not_show_sessions_from_other_class()
    {
        $voterClass = ClassRoom::factory()->create();
        $otherClass = ClassRoom::factory()->create();
        $voter = $this->createVoter($voterClass);

        $election = Election::factory()->active()->create();

        ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $otherClass->id,
            'status'      => SessionStatus::SCHEDULED,
        ]);

        $response = $this->actingAs($voter)->get(route('voter.dashboard'));

        $response->assertViewHas('sessions', function ($sessions) {
            return $sessions->isEmpty();
        });
    }

    public function test_dashboard_shows_voter_record_if_exists()
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);

        $election = Election::factory()->active()->create();

        $voterRecord = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $response = $this->actingAs($voterUser)->get(route('voter.dashboard'));

        $response->assertViewHas('voter', function ($v) use ($voterRecord) {
            return $v && $v->id === $voterRecord->id;
        });
    }

    public function test_admin_cannot_access_voter_dashboard()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('voter.dashboard'));

        $response->assertForbidden();
    }
}
