<?php

namespace Tests\Feature\Device;

use App\Models\CheckinDevice;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_voter_can_checkin_via_valid_qr()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->active()->create();
        $session = ElectionSession::factory()->active()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $device = CheckinDevice::create([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-TEST',
            'device_token'     => 'TESTTOKEN123',
            'token_expired_at' => now()->addMinutes(5),
        ]);

        // Buat voter record
        Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voter->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
        ]);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => 'TESTTOKEN123']));

        $response->assertSee('Check-in Berhasil');

        $voterRecord = Voter::where('user_id', $voter->id)->first();
        $this->assertTrue($voterRecord->checked_in);
        $this->assertNotNull($voterRecord->checked_in_at);
    }

    public function test_checkin_fails_with_invalid_token()
    {
        $voter = $this->createVoter();

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => 'INVALID']));

        $response->assertSee('QR Tidak Valid');
    }

    public function test_checkin_fails_with_expired_token()
    {
        $voter = $this->createVoter();

        CheckinDevice::create([
            'election_id'      => Election::factory()->active()->create()->id,
            'device_label'     => 'Kiosk-EXPIRED',
            'device_token'     => 'EXPIRED123',
            'token_expired_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => 'EXPIRED123']));

        $response->assertSee('QR Kadaluarsa');
    }

    public function test_checkin_logs_activity()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->active()->create();
        $session = ElectionSession::factory()->active()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        CheckinDevice::create([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-TEST',
            'device_token'     => 'TESTTOKEN',
            'token_expired_at' => now()->addMinutes(5),
        ]);

        $voterRecord = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voter->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
        ]);

        $this->actingAs($voter)->get(route('scan.checkin', ['token' => 'TESTTOKEN']));

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'checkin.success',
            'subject_id' => $voterRecord->id,
        ]);
    }
}
