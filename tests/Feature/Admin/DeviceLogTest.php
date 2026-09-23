<?php

namespace Tests\Feature\Admin;

use App\Enums\DeviceStatus;
use App\Models\CheckinDevice;
use App\Models\Election;
use App\Models\VotingDevice;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    protected function createCheckinDevice(array $attrs = []): CheckinDevice
    {
        return CheckinDevice::create(array_merge([
            'election_id'      => Election::factory()->create()->id,
            'device_label'     => 'Kiosk-TEST',
            'device_token'     => 'TOKEN' . rand(1000, 9999),
            'token_expired_at' => now()->addMinutes(5),
        ], $attrs));
    }

    protected function createVotingDevice(array $attrs = []): VotingDevice
    {
        return VotingDevice::create(array_merge([
            'election_id'      => Election::factory()->create()->id,
            'device_label'     => 'Bilik-TEST',
            'device_token'     => 'TOKEN' . rand(1000, 9999),
            'token_expired_at' => now()->addMinutes(5),
            'status'           => DeviceStatus::IDLE,
        ], $attrs));
    }

    // ==========================================
    // DESTROY CHECKIN
    // ==========================================

    public function test_admin_can_delete_checkin_device()
    {
        $admin = $this->createAdmin();
        $device = $this->createCheckinDevice();
        $deviceId = $device->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.device-logs.destroy-checkin', $device));

        $response->assertRedirect();
        $this->assertDatabaseMissing('checkin_devices', ['id' => $deviceId]);
    }

    public function test_deleting_online_checkin_device_logs_activity()
    {
        $admin = $this->createAdmin();
        $device = $this->createCheckinDevice(['last_ping_at' => now()]);

        $this->actingAs($admin)->delete(route('admin.device-logs.destroy-checkin', $device));

        $this->assertDatabaseHas('activity_logs', ['action' => 'device.checkin_deleted']);
    }

    public function test_deleting_offline_checkin_device_does_not_log()
    {
        $admin = $this->createAdmin();
        $device = $this->createCheckinDevice(['last_ping_at' => now()->subMinutes(10)]);

        $this->actingAs($admin)->delete(route('admin.device-logs.destroy-checkin', $device));

        // Hapus device offline = rutinitas, tidak perlu log
        $this->assertDatabaseMissing('activity_logs', ['action' => 'device.checkin_deleted']);
    }

    // ==========================================
    // DESTROY VOTING
    // ==========================================

    public function test_admin_can_delete_voting_device()
    {
        $admin = $this->createAdmin();
        $device = $this->createVotingDevice();
        $deviceId = $device->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.device-logs.destroy-voting', $device));

        $response->assertRedirect();
        $this->assertDatabaseMissing('voting_devices', ['id' => $deviceId]);
    }

    public function test_deleting_assigned_voting_device_logs_activity()
    {
        $admin = $this->createAdmin();
        $device = $this->createVotingDevice([
            'status'       => DeviceStatus::ASSIGNED,
            'last_ping_at' => now()->subMinutes(10),   // offline
        ]);

        $this->actingAs($admin)->delete(route('admin.device-logs.destroy-voting', $device));

        // Assigned = tidak boleh dihapus tanpa log
        $this->assertDatabaseHas('activity_logs', ['action' => 'device.voting_deleted']);
    }

    // ==========================================
    // PURGE OFFLINE
    // ==========================================

    public function test_purge_removes_stale_devices()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->create();

        // 3 device stale (> 10 menit)
        for ($i = 0; $i < 3; $i++) {
            $this->createCheckinDevice([
                'election_id'  => $election->id,
                'last_ping_at' => now()->subMinutes(20),
            ]);
        }

        // 2 device online (< 2 menit)
        for ($i = 0; $i < 2; $i++) {
            $this->createCheckinDevice([
                'election_id'  => $election->id,
                'last_ping_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)
            ->post(route('admin.device-logs.purge'));

        $response->assertRedirect();

        // Hanya 2 device yang tersisa (online)
        $this->assertEquals(2, CheckinDevice::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'device.purged']);
    }

    public function test_purge_removes_devices_with_null_last_ping()
    {
        $admin = $this->createAdmin();

        // Device dengan last_ping_at null dianggap stale
        $this->createCheckinDevice(['last_ping_at' => null]);

        $this->actingAs($admin)->post(route('admin.device-logs.purge'));

        $this->assertEquals(0, CheckinDevice::count());
    }

    public function test_purge_does_not_log_if_nothing_deleted()
    {
        $admin = $this->createAdmin();
        // Tidak ada device

        $this->actingAs($admin)->post(route('admin.device-logs.purge'));

        $this->assertDatabaseMissing('activity_logs', ['action' => 'device.purged']);
    }

    public function test_purge_cleans_both_checkin_and_voting_devices()
    {
        $admin = $this->createAdmin();
        $election = Election::factory()->create();

        $this->createCheckinDevice([
            'election_id'  => $election->id,
            'last_ping_at' => now()->subMinutes(20),
        ]);

        $this->createVotingDevice([
            'election_id'  => $election->id,
            'last_ping_at' => now()->subMinutes(20),
        ]);

        $this->actingAs($admin)->post(route('admin.device-logs.purge'));

        $this->assertEquals(0, CheckinDevice::count());
        $this->assertEquals(0, VotingDevice::count());
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    public function test_operator_cannot_access_device_logs()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)
            ->get(route('admin.device-logs.index'));

        $response->assertForbidden();
    }
}
