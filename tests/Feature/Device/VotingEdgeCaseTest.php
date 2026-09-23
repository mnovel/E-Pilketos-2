<?php

namespace Tests\Feature\Device;

use App\Enums\DeviceStatus;
use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Vote;
use App\Models\Voter;
use App\Models\VotingDevice;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    protected function setupScenario(array $electionOverrides = [], array $sessionOverrides = []): array
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);

        $election = Election::factory()->create(array_merge([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ], $electionOverrides));

        $session = ElectionSession::factory()->create(array_merge([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(10)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ], $sessionOverrides));

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

    protected function assignDeviceToVoter(Voter $voter, Election $election, ElectionSession $session): VotingDevice
    {
        return VotingDevice::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'device_label'      => 'Bilik-TEST',
            'device_token'      => 'TOKEN' . rand(1000, 9999),
            'token_expired_at'  => now()->addMinutes(5),
            'assigned_voter_id' => $voter->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);
    }

    // ==========================================
    // EDGE CASE 1: SESSION SUDAH CLOSED
    // ==========================================

    public function test_voter_cannot_vote_when_session_already_closed()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $session->update(['status' => SessionStatus::CLOSED, 'closed_at' => now()]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Sesi kelas Anda sudah berakhir.',
        ]);

        $this->assertEquals(0, Vote::count());
        $this->assertFalse($voterRecord->fresh()->has_voted);
    }

    // ==========================================
    // EDGE CASE 2: SESSION EXPIRED
    // ==========================================

    public function test_voter_cannot_vote_when_session_time_expired()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $session->update([
            'waktu_mulai'   => now()->subHours(2)->format('H:i'),
            'waktu_selesai' => now()->subHour()->format('H:i'),
        ]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 3: ELECTION CLOSED
    // ==========================================

    public function test_voter_cannot_vote_when_election_closed()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $election->update(['status' => ElectionStatus::CLOSED]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 4: CANDIDATE BUKAN DARI ELECTION INI
    // ==========================================

    public function test_voter_cannot_vote_candidate_from_other_election()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupScenario();

        $otherElection = Election::factory()->create(['status' => ElectionStatus::ACTIVE]);
        $otherCandidate = Candidate::factory()->create([
            'election_id' => $otherElection->id,
        ]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $otherCandidate->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 5: BELUM CHECK-IN
    // ==========================================

    public function test_voter_cannot_vote_without_checkin()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $voterRecord->update([
            'checked_in'    => false,
            'checked_in_at' => null,
        ]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Anda belum check-in.',
        ]);

        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 6: DEVICE IDLE
    // ==========================================

    public function test_cannot_vote_when_device_is_idle()
    {
        [
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $operator = $this->createOperator();

        $device = VotingDevice::create([
            'election_id'      => $election->id,
            'session_id'       => $session->id,
            'device_label'     => 'Bilik-TEST',
            'device_token'     => 'TOKEN' . rand(1000, 9999),
            'token_expired_at' => now()->addMinutes(5),
            'status'           => DeviceStatus::IDLE,
        ]);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 7: VOTER SUDAH VOTE
    // ==========================================

    public function test_voter_cannot_vote_twice_even_with_new_device()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $voterRecord->update(['has_voted' => true, 'voted_at' => now()]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Anda sudah memilih.',
        ]);

        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 8: DEVICE DARI ELECTION LAIN
    // ==========================================

    public function test_cannot_vote_with_device_from_other_election()
    {
        [
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        $otherElection = Election::factory()->create(['status' => ElectionStatus::ACTIVE]);
        $otherDevice = VotingDevice::create([
            'election_id'      => $otherElection->id,
            'device_label'     => 'Bilik-OTHER',
            'device_token'     => 'OTHER' . rand(1000, 9999),
            'token_expired_at' => now()->addMinutes(5),
            'status'           => DeviceStatus::ASSIGNED,
        ]);

        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $otherDevice->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }

    // ==========================================
    // EDGE CASE 9: KELAS VOTER ≠ KELAS SESI
    // ==========================================

    public function test_voter_from_different_class_cannot_vote_in_session()
    {
        [
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'candidates'  => $candidates,
        ] = $this->setupScenario();

        // ✅ Pindahkan voter ke kelas lain (tidak sesuai session)
        $otherClass = ClassRoom::factory()->create();
        $voterRecord->update(['class_id' => $otherClass->id]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        // ✅ Sekarang di-ENFORCE: voter kelas lain tidak bisa vote di sesi ini
        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Kelas Anda tidak sesuai dengan sesi ini. Hubungi panitia.',
        ]);

        $this->assertEquals(0, Vote::count());
        $this->assertFalse($voterRecord->fresh()->has_voted);
    }

    // ==========================================
    // EDGE CASE 10: ELECTION BELUM MULAI
    // ==========================================

    public function test_voter_cannot_vote_when_election_not_started()
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(3),
        ]);

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
            'status'      => SessionStatus::ACTIVE,
        ]);

        $candidate = Candidate::factory()->create(['election_id' => $election->id]);

        $voterRecord = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
            'checked_in'  => true,
        ]);

        $operator = $this->createOperator();
        $device = $this->assignDeviceToVoter($voterRecord, $election, $session);

        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device->id,
            'candidate_id' => $candidate->id,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }
}
