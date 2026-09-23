<?php

namespace Tests\Unit\Models;

use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoterTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // QR TOKEN
    // ==========================================

    public function test_qr_token_auto_generated_on_create()
    {
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter()->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertNotNull($voter->qr_token);
        $this->assertStringStartsWith('PLK-', $voter->qr_token);
    }

    public function test_generate_qr_token_is_unique()
    {
        $token1 = Voter::generateQrToken();
        $token2 = Voter::generateQrToken();

        $this->assertNotEquals($token1, $token2);
    }

    public function test_qr_token_length_is_24_characters()
    {
        $token = Voter::generateQrToken();
        $this->assertEquals(24, strlen($token));
    }

    public function test_existing_qr_token_is_not_overwritten()
    {
        $customToken = 'PLK-CUSTOMTOKEN1234567890';

        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter()->id,
            'qr_token'    => $customToken,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertEquals($customToken, $voter->qr_token);
    }

    // ==========================================
    // CHECK-IN
    // ==========================================

    public function test_mark_checked_in_updates_status_and_timestamp()
    {
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter()->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertFalse($voter->checked_in);
        $this->assertNull($voter->checked_in_at);

        $voter->markCheckedIn();

        $voter->refresh();
        $this->assertTrue($voter->checked_in);
        $this->assertNotNull($voter->checked_in_at);
    }

    public function test_mark_checked_in_accepts_operator_id()
    {
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter()->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $operator = $this->createOperator();
        $voter->markCheckedIn($operator->id);

        $this->assertTrue($voter->fresh()->checked_in);
    }

    // ==========================================
    // VOTED
    // ==========================================

    public function test_mark_voted_updates_status_and_timestamp()
    {
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter()->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertFalse($voter->has_voted);
        $this->assertNull($voter->voted_at);

        $voter->markVoted();

        $voter->refresh();
        $this->assertTrue($voter->has_voted);
        $this->assertNotNull($voter->voted_at);
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function test_belongs_to_election()
    {
        $election = Election::factory()->create();
        $voter = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $this->createVoter()->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertInstanceOf(Election::class, $voter->election);
        $this->assertEquals($election->id, $voter->election->id);
    }

    public function test_belongs_to_user()
    {
        $user = $this->createVoter();
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $user->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertInstanceOf(User::class, $voter->user);
        $this->assertEquals($user->id, $voter->user->id);
    }

    public function test_belongs_to_class_room()
    {
        $class = ClassRoom::factory()->create();
        $user = $this->createVoter($class);

        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $user->id,
            'class_id'    => $class->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertInstanceOf(ClassRoom::class, $voter->classRoom);
        $this->assertEquals($class->id, $voter->classRoom->id);
    }

    public function test_belongs_to_session()
    {
        $class = ClassRoom::factory()->create();
        $election = Election::factory()->create();
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $voter = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $this->createVoter($class)->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
            'checked_in'  => false,
            'has_voted'   => false,
        ]);

        $this->assertInstanceOf(ElectionSession::class, $voter->session);
        $this->assertEquals($session->id, $voter->session->id);
    }

    // ==========================================
    // CASTS
    // ==========================================

    public function test_booleans_are_cast_correctly()
    {
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter()->id,
            'checked_in'  => true,
            'has_voted'   => true,
        ]);

        $this->assertIsBool($voter->checked_in);
        $this->assertIsBool($voter->has_voted);
        $this->assertTrue($voter->checked_in);
        $this->assertTrue($voter->has_voted);
    }
}
