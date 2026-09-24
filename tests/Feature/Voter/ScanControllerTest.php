<?php

namespace Tests\Feature\Voter;

use App\Enums\DeviceStatus;
use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Enums\VoterStatus;
use App\Models\ActivityLog;
use App\Models\CheckinDevice;
use App\Models\CheckinLog;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Voter;
use App\Models\VotingDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Setup election + class + voter user + voter record.
     */
    protected function setupVoter(array $voterOverrides = []): array
    {
        $class = ClassRoom::factory()->create();

        $voterUser = $this->createVoter($class);
        $voterUser->update(['status' => VoterStatus::VERIFIED]);

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

        $voterRecord = Voter::create(array_merge([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
            'session_id'  => $session->id,
        ], $voterOverrides));

        return compact('class', 'voterUser', 'voterRecord', 'election', 'session');
    }

    protected function makeCheckinDevice(Election $election, array $overrides = []): CheckinDevice
    {
        return CheckinDevice::create(array_merge([
            'election_id'      => $election->id,
            'device_label'     => 'Kiosk-TEST',
            'device_token'     => 'CHK-' . strtoupper(bin2hex(random_bytes(4))),
            'token_expired_at' => now()->addMinutes(5),
            'last_ping_at'     => now(),
        ], $overrides));
    }

    protected function makeVotingDevice(Election $election, ElectionSession $session, array $overrides = []): VotingDevice
    {
        return VotingDevice::create(array_merge([
            'election_id'      => $election->id,
            'session_id'       => $session->id,
            'device_label'     => 'Bilik-TEST',
            'device_token'     => 'VOT-' . strtoupper(bin2hex(random_bytes(4))),
            'token_expired_at' => now()->addMinutes(5),
            'status'           => DeviceStatus::IDLE,
        ], $overrides));
    }

    // =========================================================
    // CHECKIN — ACCESS
    // =========================================================

    public function test_guest_cannot_access_checkin()
    {
        $this->get(route('scan.checkin', ['token' => 'ANY']))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_checkin()
    {
        $this->actingAs($this->createAdmin())
            ->get(route('scan.checkin', ['token' => 'ANY']))
            ->assertForbidden();
    }

    public function test_operator_cannot_access_checkin()
    {
        $this->actingAs($this->createOperator())
            ->get(route('scan.checkin', ['token' => 'ANY']))
            ->assertForbidden();
    }

    // =========================================================
    // CHECKIN — DEVICE VALIDATION
    // =========================================================

    public function test_checkin_fails_with_invalid_token()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => 'INVALID-TOKEN']));

        $response->assertSee('QR Tidak Valid');
        $response->assertSee('tidak dikenal');
    }

    public function test_checkin_fails_with_expired_token()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);
        $election = Election::factory()->create(['status' => ElectionStatus::ACTIVE]);

        $device = $this->makeCheckinDevice($election, [
            'device_token'     => 'EXPIRED-TOKEN',
            'token_expired_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => 'EXPIRED-TOKEN']));

        $response->assertSee('QR Kadaluarsa');
    }

    // =========================================================
    // CHECKIN — ELECTION VALIDATION
    // =========================================================

    public function test_checkin_fails_when_election_not_active()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertSee('Pemilihan Tidak Aktif');
    }

    public function test_checkin_fails_when_election_not_started()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(3),
        ]);

        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertSee('Belum Waktunya');
    }

    public function test_checkin_fails_when_election_already_ended()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(3),
            'end_at'   => now()->subHour(),
        ]);

        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        // autoCloseIfEnded dipanggil → election di-close → "Waktu Pemilihan Habis"
        $response->assertSee('Waktu Pemilihan Habis');
        $this->assertEquals(ElectionStatus::CLOSED, $election->fresh()->status);
    }

    // =========================================================
    // CHECKIN — VOTER VALIDATION
    // =========================================================

    public function test_checkin_fails_when_voter_not_registered()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);

        // Tidak ada Voter record untuk user ini
        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voter)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertSee('Anda Belum Terdaftar');
    }

    public function test_checkin_shows_info_when_already_checked_in()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
        ] = $this->setupVoter();

        $voterRecord->update(['checked_in' => true, 'checked_in_at' => now()]);

        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertSee('Sudah Check-in');
    }

    // =========================================================
    // CHECKIN — SESSION VALIDATION
    // =========================================================

    public function test_checkin_fails_when_no_active_session_for_class()
    {
        $class = ClassRoom::factory()->create();
        $voterUser = $this->createVoter($class);
        $voterUser->update(['status' => VoterStatus::VERIFIED]);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);

        // Session untuk kelas LAIN, bukan kelas voter
        $otherClass = ClassRoom::factory()->create();
        ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $otherClass->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(10)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ]);

        $voterRecord = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $voterUser->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertSee('Sesi Kelas Tidak Aktif');
    }

    // =========================================================
    // CHECKIN — HAPPY PATH
    // =========================================================

    public function test_voter_can_checkin_successfully()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter();

        $device = $this->makeCheckinDevice($election);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertOk();
        $response->assertSee('Check-in Berhasil');
        $response->assertSee('bilik suara', false);

        $voterRecord->refresh();
        $this->assertTrue($voterRecord->checked_in);
        $this->assertNotNull($voterRecord->checked_in_at);
        $this->assertSame($session->id, $voterRecord->session_id);
    }

    public function test_checkin_creates_checkin_log()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
        ] = $this->setupVoter();

        $device = $this->makeCheckinDevice($election);

        $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $this->assertDatabaseHas('checkin_logs', [
            'election_id' => $election->id,
            'voter_id'    => $voterRecord->id,
        ]);

        $log = CheckinLog::where('voter_id', $voterRecord->id)->first();
        $this->assertNotNull($log);
        $this->assertNotNull($log->scanned_at);
    }

    public function test_checkin_logs_activity()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'class'       => $class,
        ] = $this->setupVoter();

        $device = $this->makeCheckinDevice($election);

        $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'checkin.success',
            'subject_id' => $voterRecord->id,
        ]);

        $log = ActivityLog::where('action', 'checkin.success')->first();
        $this->assertNotNull($log);
        $this->assertSame($election->id, $log->meta['election_id']);
        $this->assertSame($class->name, $log->meta['kelas']);
    }

    // =========================================================
    // VOTING — ACCESS
    // =========================================================

    public function test_guest_cannot_access_voting_scan()
    {
        $this->get(route('scan.voting', ['token' => 'ANY']))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_voting_scan()
    {
        $this->actingAs($this->createAdmin())
            ->get(route('scan.voting', ['token' => 'ANY']))
            ->assertForbidden();
    }

    // =========================================================
    // VOTING — DEVICE VALIDATION
    // =========================================================

    public function test_voting_scan_fails_with_invalid_token()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $response = $this->actingAs($voter)
            ->get(route('scan.voting', ['token' => 'INVALID']));

        $response->assertSee('QR Tidak Valid');
    }

    public function test_voting_scan_fails_with_expired_token()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);
        $election = Election::factory()->create(['status' => ElectionStatus::ACTIVE]);
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->makeVotingDevice($election, $session, [
            'device_token'     => 'EXPIRED-VOT',
            'token_expired_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($voter)
            ->get(route('scan.voting', ['token' => 'EXPIRED-VOT']));

        $response->assertSee('QR Kadaluarsa');
    }

    public function test_voting_scan_fails_when_device_not_idle()
    {
        [
            'class'        => $class,
            'voterUser'    => $voterUser,
            'voterRecord'  => $voterRecord,
            'election'     => $election,
            'session'      => $session,
        ] = $this->setupVoter();

        // Device sudah ASSIGNED ke voter lain
        $otherClass = ClassRoom::factory()->create();
        $otherUser = $this->createVoter($otherClass);
        $otherVoter = Voter::create([
            'election_id' => $election->id,
            'user_id'     => $otherUser->id,
            'class_id'    => $otherClass->id,
        ]);

        $device = $this->makeVotingDevice($election, $session, [
            'status'            => DeviceStatus::ASSIGNED,
            'assigned_voter_id' => $otherVoter->id,
            'assigned_at'       => now(),
        ]);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Device Sedang Dipakai');
    }

    // =========================================================
    // VOTING — ELECTION VALIDATION
    // =========================================================

    public function test_voting_scan_fails_when_election_ended()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(3),
            'end_at'   => now()->subHour(),
        ]);

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voter)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Waktu Pemilihan Habis');
    }

    public function test_voting_scan_fails_when_election_not_active()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voter)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Pemilihan Tidak Aktif');
    }

    // =========================================================
    // VOTING — VOTER VALIDATION
    // =========================================================

    public function test_voting_scan_fails_when_voter_not_registered()
    {
        $class = ClassRoom::factory()->create();
        $voter = $this->createVoter($class);

        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voter)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Belum Terdaftar');
    }

    public function test_voting_scan_fails_when_voter_not_checked_in()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter(['checked_in' => false]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Belum Check-in');
    }

    public function test_voting_scan_shows_info_when_already_voted()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter([
            'checked_in' => true,
            'has_voted'  => true,
            'voted_at'   => now(),
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Sudah Memilih');
    }

    // =========================================================
    // VOTING — SESSION VALIDATION
    // =========================================================

    public function test_voting_scan_fails_when_session_ended()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter([
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        // Session di-close
        $session->update(['status' => SessionStatus::CLOSED, 'closed_at' => now()]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Sesi Sudah Berakhir');
    }

    public function test_voting_scan_fails_when_session_out_of_time_window()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter([
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        // Session masih ACTIVE tapi waktunya sudah lewat
        $session->update([
            'waktu_mulai'   => now()->subHours(2)->format('H:i'),
            'waktu_selesai' => now()->subHour()->format('H:i'),
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertSee('Sesi Sudah Berakhir');
    }

    // =========================================================
    // VOTING — HAPPY PATH
    // =========================================================

    public function test_voter_can_scan_voting_successfully()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter([
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $response = $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $response->assertOk();
        $response->assertSee('Berhasil');
        $response->assertSee('layar device', false);
    }

    public function test_voting_scan_assigns_device_to_voter()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
        ] = $this->setupVoter([
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $device->refresh();

        $this->assertSame(DeviceStatus::ASSIGNED, $device->status);
        $this->assertSame($voterRecord->id, $device->assigned_voter_id);
        $this->assertNotNull($device->assigned_at);
    }

    public function test_voting_scan_logs_activity()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
            'session'     => $session,
            'class'       => $class,
        ] = $this->setupVoter([
            'checked_in'    => true,
            'checked_in_at' => now(),
        ]);

        $device = $this->makeVotingDevice($election, $session);

        $this->actingAs($voterUser)
            ->get(route('scan.voting', ['token' => $device->device_token]));

        $log = ActivityLog::where('action', 'voter.voting_scanned')->first();

        $this->assertNotNull($log);
        $this->assertSame($voterRecord->id, $log->subject_id);
        $this->assertSame($election->id, $log->meta['election_id']);
        $this->assertSame($class->name, $log->meta['kelas']);
    }

    // =========================================================
    // IDEMPOTENCY
    // =========================================================

    public function test_voter_cannot_checkin_twice()
    {
        [
            'voterUser'   => $voterUser,
            'voterRecord' => $voterRecord,
            'election'    => $election,
        ] = $this->setupVoter();

        $device = $this->makeCheckinDevice($election);

        // Pertama: sukses
        $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]))
            ->assertSee('Check-in Berhasil');

        // Kedua: info
        $response = $this->actingAs($voterUser)
            ->get(route('scan.checkin', ['token' => $device->device_token]));

        $response->assertSee('Sudah Check-in');
    }
}
