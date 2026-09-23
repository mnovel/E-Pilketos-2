<?php

namespace Tests\Feature\Device;

use App\Enums\DeviceStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Vote;
use App\Models\Voter;
use App\Models\VotingDevice;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Helper: setup skenario voting lengkap.
     */
    protected function setupVotingScenario(): array
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);

        $election = Election::factory()->active()->create();
        $session = ElectionSession::factory()->active()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $candidates = Candidate::factory()->count(3)->create([
            'election_id' => $election->id,
        ]);

        $voterRecord = Voter::create([
            'election_id'   => $election->id,
            'user_id'       => $voterUser->id,
            'class_id'      => $class->id,
            'session_id'    => $session->id,
            'checked_in'    => true,
            'checked_in_at' => now(),
            'has_voted'     => false,
        ]);

        return compact('class', 'voterUser', 'voterRecord', 'election', 'session', 'candidates');
    }

    /**
     * Helper: bikin device yang sudah di-assign ke voter.
     */
    protected function assignDeviceToVoter(Voter $voter, Election $election, ElectionSession $session): VotingDevice
    {
        return VotingDevice::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'device_label'      => 'Bilik-TEST',
            'device_token'      => 'VOTETOKEN' . rand(1000, 9999),
            'token_expired_at'  => now()->addMinutes(5),
            'assigned_voter_id' => $voter->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);
    }

    // ==========================================
    // TEST
    // ==========================================

    public function test_voter_can_submit_vote()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupVotingScenario();

        // ✅ Login sebagai operator (yang pegang kiosk)
        $operator = $this->createOperator();

        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)
            ->postJson(route('device.voting.submit'), [
                'device_id'    => $device->id,
                'candidate_id' => $candidates->first()->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        // Vote tersimpan
        $this->assertEquals(1, Vote::count());

        // Voter ditandai sudah vote
        $voterRecord->refresh();
        $this->assertTrue($voterRecord->has_voted);
        $this->assertNotNull($voterRecord->voted_at);

        // Device reset ke IDLE
        $device->refresh();
        $this->assertEquals(DeviceStatus::IDLE, $device->status);
        $this->assertNull($device->assigned_voter_id);
    }

    public function test_vote_is_anonymous()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupVotingScenario();

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $this->actingAs($operator)
            ->postJson(route('device.voting.submit'), [
                'device_id'    => $device->id,
                'candidate_id' => $candidates->first()->id,
            ]);

        // ✅ Vote TIDAK punya kolom user_id / voter_id
        $vote = Vote::first();
        $this->assertNotNull($vote);
        $this->assertArrayNotHasKey('user_id', $vote->getAttributes());
        $this->assertArrayNotHasKey('voter_id', $vote->getAttributes());
    }

    public function test_voter_cannot_vote_twice()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupVotingScenario();

        // Tandai sudah vote
        $voterRecord->update(['has_voted' => true, 'voted_at' => now()]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)
            ->postJson(route('device.voting.submit'), [
                'device_id'    => $device->id,
                'candidate_id' => $candidates->first()->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);

        // Tidak ada vote baru
        $this->assertEquals(0, Vote::count());
    }

    public function test_vote_logged_as_anonymous_system_action()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupVotingScenario();

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $this->actingAs($operator)
            ->postJson(route('device.voting.submit'), [
                'device_id'    => $device->id,
                'candidate_id' => $candidates->first()->id,
            ]);

        // ✅ Log dengan user_id NULL (anonim)
        $this->assertDatabaseHas('activity_logs', [
            'action'  => 'vote.submitted',
            'user_id' => null,
        ]);
    }
}
