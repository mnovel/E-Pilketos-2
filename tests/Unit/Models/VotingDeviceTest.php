<?php

namespace Tests\Unit\Models;

use App\Enums\DeviceStatus;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Voter;
use App\Models\VotingDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VotingDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected function createDevice(array $attrs = []): VotingDevice
    {
        return VotingDevice::create(array_merge([
            'election_id'      => Election::factory()->create()->id,
            'device_label'     => 'Bilik-TEST',
            'device_token'     => 'TOKEN1234567',
            'token_expired_at' => now()->addSeconds(30),
            'status'           => DeviceStatus::IDLE,
        ], $attrs));
    }

    // ==========================================
    // TOKEN
    // ==========================================

    public function test_token_is_valid_when_not_expired()
    {
        $device = $this->createDevice([
            'token_expired_at' => now()->addMinutes(5),
        ]);

        $this->assertTrue($device->isTokenValid());
    }

    public function test_token_is_invalid_when_expired()
    {
        $device = $this->createDevice([
            'token_expired_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($device->isTokenValid());
    }

    public function test_rotate_token_generates_new_token()
    {
        $device = $this->createDevice(['device_token' => 'OLD1234567']);
        $oldToken = $device->device_token;

        $device->rotateToken(60);

        $device->refresh();
        $this->assertNotEquals($oldToken, $device->device_token);
        $this->assertEquals(12, strlen($device->device_token));
    }

    public function test_rotate_token_sets_expiry()
    {
        $device = $this->createDevice();
        $device->rotateToken(120);

        $device->refresh();
        $this->assertTrue($device->token_expired_at->isFuture());
        $this->assertTrue($device->token_expired_at->diffInSeconds(now()) <= 120);
    }

    // ==========================================
    // STATUS
    // ==========================================

    public function test_is_idle_when_status_idle()
    {
        $device = $this->createDevice(['status' => DeviceStatus::IDLE]);
        $this->assertTrue($device->isIdle());
        $this->assertFalse($device->isAssigned());
    }

    public function test_is_assigned_when_status_assigned()
    {
        $device = $this->createDevice(['status' => DeviceStatus::ASSIGNED]);
        $this->assertTrue($device->isAssigned());
        $this->assertFalse($device->isIdle());
    }

    // ==========================================
    // ASSIGN
    // ==========================================

    public function test_assign_to_voter()
    {
        $class = ClassRoom::factory()->create();
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter($class)->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->createDevice();
        $device->assignTo($voter);

        $device->refresh();
        $this->assertEquals($voter->id, $device->assigned_voter_id);
        $this->assertNotNull($device->assigned_at);
        $this->assertEquals(DeviceStatus::ASSIGNED, $device->status);
    }

    // ==========================================
    // RESET
    // ==========================================

    public function test_reset_to_idle_clears_assignment()
    {
        $class = ClassRoom::factory()->create();
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter($class)->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->createDevice();
        $device->assignTo($voter);

        $device->resetToIdle();

        $device->refresh();
        $this->assertNull($device->assigned_voter_id);
        $this->assertNull($device->assigned_at);
        $this->assertEquals(DeviceStatus::IDLE, $device->status);
    }

    public function test_reset_to_idle_rotates_token()
    {
        $device = $this->createDevice(['device_token' => 'OLD1234567']);
        $oldToken = $device->device_token;

        $device->resetToIdle();

        $device->refresh();
        $this->assertNotEquals($oldToken, $device->device_token);
    }

    // ==========================================
    // PING
    // ==========================================

    public function test_ping_updates_last_ping_at()
    {
        $device = $this->createDevice();
        $this->assertNull($device->last_ping_at);

        $device->ping();

        $device->refresh();
        $this->assertNotNull($device->last_ping_at);
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function test_belongs_to_election()
    {
        $election = Election::factory()->create();
        $device = $this->createDevice(['election_id' => $election->id]);

        $this->assertInstanceOf(Election::class, $device->election);
    }

    public function test_belongs_to_session()
    {
        $class = ClassRoom::factory()->create();
        $election = Election::factory()->create();
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->createDevice([
            'election_id' => $election->id,
            'session_id'  => $session->id,
        ]);

        $this->assertInstanceOf(ElectionSession::class, $device->session);
        $this->assertEquals($session->id, $device->session->id);
    }

    public function test_belongs_to_assigned_voter()
    {
        $class = ClassRoom::factory()->create();
        $voter = Voter::create([
            'election_id' => Election::factory()->create()->id,
            'user_id'     => $this->createVoter($class)->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->createDevice();
        $device->assignTo($voter);

        $this->assertInstanceOf(Voter::class, $device->fresh()->assignedVoter);
        $this->assertEquals($voter->id, $device->fresh()->assignedVoter->id);
    }
}
