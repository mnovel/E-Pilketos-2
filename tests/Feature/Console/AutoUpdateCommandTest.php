<?php

namespace Tests\Feature\Console;

use App\Enums\ElectionStatus;
use App\Enums\SessionStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoUpdateCommandTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // COMMAND EXECUTION
    // ==========================================

    public function test_command_runs_successfully()
    {
        $this->artisan('pilketos:auto-update')
            ->assertSuccessful();
    }

    public function test_dry_run_does_not_modify_data()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);
        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $this->artisan('pilketos:auto-update', ['--dry-run' => true])
            ->assertSuccessful();

        // Status tidak berubah karena dry-run
        $this->assertEquals(ElectionStatus::DRAFT, $election->fresh()->status);
    }

    // ==========================================
    // AUTO-ACTIVATE ELECTION
    // ==========================================

    public function test_activates_draft_election_when_time_comes()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);
        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::ACTIVE, $election->fresh()->status);
    }

    public function test_does_not_activate_draft_with_less_than_2_candidates()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);
        Candidate::factory()->create(['election_id' => $election->id]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::DRAFT, $election->fresh()->status);
    }

    public function test_does_not_activate_draft_before_start_time()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(2),
        ]);
        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::DRAFT, $election->fresh()->status);
    }

    public function test_does_not_activate_draft_after_end_time()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->subHour(),
        ]);
        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        // Setelah auto-close, status jadi CLOSED (bukan ACTIVE)
        $this->assertNotEquals(ElectionStatus::ACTIVE, $election->fresh()->status);
    }

    // ==========================================
    // AUTO-CLOSE ELECTION
    // ==========================================

    public function test_closes_active_election_when_end_time_passes()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->subMinute(),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::CLOSED, $election->fresh()->status);
    }

    public function test_does_not_close_active_election_before_end()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::ACTIVE, $election->fresh()->status);
    }

    public function test_closes_expired_draft_election()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->subHour(),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::CLOSED, $election->fresh()->status);
    }

    public function test_does_not_close_published_election()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::PUBLISHED,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->subHour(),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::PUBLISHED, $election->fresh()->status);
    }

    // ==========================================
    // AUTO-ACTIVATE SESSION
    // ==========================================

    public function test_activates_scheduled_session_in_time_window()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->addHours(2),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::SCHEDULED,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(5)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(SessionStatus::ACTIVE, $session->fresh()->status);
        $this->assertNotNull($session->fresh()->activated_at);
    }

    public function test_does_not_activate_scheduled_session_before_time()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->addHours(2),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::SCHEDULED,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->addHour()->format('H:i'),
            'waktu_selesai' => now()->addHours(2)->format('H:i'),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(SessionStatus::SCHEDULED, $session->fresh()->status);
    }

    public function test_does_not_activate_session_when_election_is_draft()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->addHours(2),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::SCHEDULED,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(5)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        // Session tidak aktif karena election bukan active
        $this->assertNotEquals(SessionStatus::ACTIVE, $session->fresh()->status);
    }

    // ==========================================
    // AUTO-CLOSE SESSION
    // ==========================================

    public function test_closes_active_session_when_time_passes()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->addHours(2),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subHour()->format('H:i'),
            'waktu_selesai' => now()->subMinute()->format('H:i'),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(SessionStatus::CLOSED, $session->fresh()->status);
        $this->assertNotNull($session->fresh()->closed_at);
    }

    public function test_closes_scheduled_session_that_was_missed()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(3),
            'end_at'   => now()->addHours(2),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::SCHEDULED,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subHours(2)->format('H:i'),
            'waktu_selesai' => now()->subHour()->format('H:i'),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(SessionStatus::CLOSED, $session->fresh()->status);
    }

    public function test_does_not_close_session_before_end_time()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHours(2),
        ]);

        $class = ClassRoom::factory()->create();

        $session = ElectionSession::factory()->create([
            'election_id'   => $election->id,
            'class_id'      => $class->id,
            'status'        => SessionStatus::ACTIVE,
            'tanggal'       => now()->toDateString(),
            'waktu_mulai'   => now()->subMinutes(5)->format('H:i'),
            'waktu_selesai' => now()->addMinutes(30)->format('H:i'),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(SessionStatus::ACTIVE, $session->fresh()->status);
    }

    // ==========================================
    // INTEGRATION TEST
    // ==========================================

    public function test_full_lifecycle_in_one_run()
    {
        // Election 1: draft dengan 2 kandidat & waktunya sudah masuk → jadi active
        $elect1 = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);
        Candidate::factory()->count(2)->create(['election_id' => $elect1->id]);

        // Election 2: active yang sudah lewat → jadi closed
        $elect2 = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'start_at' => now()->subHours(2),
            'end_at'   => now()->subMinute(),
        ]);

        $this->artisan('pilketos:auto-update')->assertSuccessful();

        $this->assertEquals(ElectionStatus::ACTIVE, $elect1->fresh()->status);
        $this->assertEquals(ElectionStatus::CLOSED, $elect2->fresh()->status);
    }
}
