<?php

namespace Tests\Unit\Models;

use App\Models\CheckinDevice;
use App\Models\Election;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected function createDevice(array $attrs = []): CheckinDevice
    {
        return CheckinDevice::create(array_merge([
            'election_id'      => Election::factory()->create()->id,
            'device_label'     => 'Kiosk-TEST',
            'device_token'     => 'TOKEN1234567',
            'token_expired_at' => now()->addSeconds(30),
        ], $attrs));
    }

    // ==========================================
    // TOKEN
    // ==========================================

    public function test_token_valid_when_not_expired()
    {
        $device = $this->createDevice([
            'token_expired_at' => now()->addMinutes(5),
        ]);

        $this->assertTrue($device->isTokenValid());
    }

    public function test_token_invalid_when_expired()
    {
        $device = $this->createDevice([
            'token_expired_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($device->isTokenValid());
    }

    public function test_token_invalid_when_expiry_is_null()
    {
        $device = new CheckinDevice([
            'device_token'     => 'TOKEN1234567',
            'token_expired_at' => null,
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
    }

    public function test_rotate_token_sets_new_expiry()
    {
        $device = $this->createDevice([
            'token_expired_at' => now()->subMinutes(10),
        ]);

        $this->assertFalse($device->isTokenValid());

        $device->rotateToken(30);

        $this->assertTrue($device->fresh()->isTokenValid());
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
        $this->assertTrue($device->last_ping_at->isToday());
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function test_belongs_to_election()
    {
        $election = Election::factory()->create();
        $device = $this->createDevice(['election_id' => $election->id]);

        $this->assertInstanceOf(Election::class, $device->election);
        $this->assertEquals($election->id, $device->election->id);
    }

    public function test_has_many_checkin_logs()
    {
        $device = $this->createDevice();

        $this->assertCount(0, $device->checkinLogs);

        // Cek relasi ada
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $device->checkinLogs()
        );
    }
}
