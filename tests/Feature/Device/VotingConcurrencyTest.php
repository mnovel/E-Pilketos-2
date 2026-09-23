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

class VotingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    /**
     * Skenario: voter scan QR dua kali dari device berbeda.
     * Sistem harus cegah vote ganda.
     */
    public function test_voter_cannot_vote_from_two_devices()
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(10)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ]);

        $candidates = Candidate::factory()->count(2)->create([
            'election_id' => $election->id,
        ]);

        $voterRecord = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
            'checked_in'  => true,
        ]);

        $operator = $this->createOperator();

        // Device 1 & 2
        $device1 = VotingDevice::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'device_label'      => 'Bilik-1',
            'device_token'      => 'TOKEN1',
            'token_expired_at'  => now()->addMinutes(5),
            'assigned_voter_id' => $voterRecord->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);

        $device2 = VotingDevice::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'device_label'      => 'Bilik-2',
            'device_token'      => 'TOKEN2',
            'token_expired_at'  => now()->addMinutes(5),
            'assigned_voter_id' => $voterRecord->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);

        // Vote dari device 1
        $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device1->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        // Coba vote lagi dari device 2
        $response = $this->actingAs($operator)->postJson(route('device.voting.submit'), [
            'device_id'    => $device2->id,
            'candidate_id' => $candidates->first()->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Anda sudah memilih.',
        ]);

        // Hanya 1 vote tersimpan
        $this->assertEquals(1, Vote::count());
    }

    /**
     * Skenario: operator dari IP berbeda vote untuk voter yang sama.
     * Harus tetap dicegah (data integrity, bukan hanya client-side).
     */
    public function test_data_integrity_prevents_double_vote()
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(10)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ]);

        $candidate = Candidate::factory()->create(['election_id' => $election->id]);

        $voterRecord = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
            'checked_in'  => true,
            'has_voted'   => true,   // ← SUDAH VOTE
            'voted_at'    => now(),
        ]);

        $operator1 = $this->createOperator();
        $operator2 = $this->createOperator();

        $device1 = VotingDevice::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'device_label'      => 'Bilik-1',
            'device_token'      => 'TOKEN1',
            'token_expired_at'  => now()->addMinutes(5),
            'assigned_voter_id' => $voterRecord->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);

        $device2 = VotingDevice::create([
            'election_id'       => $election->id,
            'session_id'        => $session->id,
            'device_label'      => 'Bilik-2',
            'device_token'      => 'TOKEN2',
            'token_expired_at'  => now()->addMinutes(5),
            'assigned_voter_id' => $voterRecord->id,
            'assigned_at'       => now(),
            'status'            => DeviceStatus::ASSIGNED,
        ]);

        // Operator 1 coba vote
        $r1 = $this->actingAs($operator1)->postJson(route('device.voting.submit'), [
            'device_id'    => $device1->id,
            'candidate_id' => $candidate->id,
        ]);

        // Operator 2 coba vote
        $r2 = $this->actingAs($operator2)->postJson(route('device.voting.submit'), [
            'device_id'    => $device2->id,
            'candidate_id' => $candidate->id,
        ]);

        // Dua-duanya gagal
        $r1->assertStatus(422);
        $r2->assertStatus(422);
        $this->assertEquals(0, Vote::count());
    }
}
