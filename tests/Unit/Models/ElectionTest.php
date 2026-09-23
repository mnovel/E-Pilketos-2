<?php

namespace Tests\Unit\Models;

use App\Enums\ElectionStatus;
use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_draft_returns_true_for_draft_status()
    {
        $election = Election::factory()->make(['status' => ElectionStatus::DRAFT]);
        $this->assertTrue($election->isDraft());
    }

    public function test_is_active_returns_true_for_active_status()
    {
        $election = Election::factory()->make(['status' => ElectionStatus::ACTIVE]);
        $this->assertTrue($election->isActive());
    }

    public function test_has_started_returns_true_when_start_at_in_past()
    {
        $election = Election::factory()->make(['start_at' => now()->subHour()]);
        $this->assertTrue($election->hasStarted());
    }

    public function test_has_ended_returns_true_when_end_at_in_past()
    {
        $election = Election::factory()->make(['end_at' => now()->subHour()]);
        $this->assertTrue($election->hasEnded());
    }

    public function test_is_within_time_window_returns_true_between_dates()
    {
        $election = Election::factory()->make([
            'start_at' => now()->subHour(),
            'end_at'   => now()->addHour(),
        ]);

        $this->assertTrue($election->isWithinTimeWindow());
    }

    public function test_auto_activate_if_ready_transitions_draft_to_active()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);

        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $result = $election->autoActivateIfReady();

        $this->assertTrue($result);
        $this->assertEquals(ElectionStatus::ACTIVE, $election->fresh()->status);
    }

    public function test_auto_activate_returns_false_with_one_candidate()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->subMinute(),
            'end_at'   => now()->addHour(),
        ]);

        Candidate::factory()->create(['election_id' => $election->id]);

        $this->assertFalse($election->autoActivateIfReady());
    }

    public function test_auto_activate_returns_false_before_start_time()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::DRAFT,
            'start_at' => now()->addHour(),
            'end_at'   => now()->addHours(2),
        ]);

        Candidate::factory()->count(2)->create(['election_id' => $election->id]);

        $this->assertFalse($election->autoActivateIfReady());
    }

    public function test_auto_close_if_ended_transitions_active_to_closed()
    {
        $election = Election::factory()->create([
            'status'   => ElectionStatus::ACTIVE,
            'end_at'   => now()->subMinute(),
        ]);

        $result = $election->autoCloseIfEnded();

        $this->assertTrue($result);
        $this->assertEquals(ElectionStatus::CLOSED, $election->fresh()->status);
    }

    public function test_get_runtime_status_returns_correct_labels()
    {
        $draft = Election::factory()->make(['status' => ElectionStatus::DRAFT, 'start_at' => now()->addHour()]);
        $this->assertEquals('draft', $draft->getRuntimeStatus());

        $ready = Election::factory()->make(['status' => ElectionStatus::DRAFT, 'start_at' => now()->subMinute()]);
        $this->assertEquals('ready', $ready->getRuntimeStatus());

        $active = Election::factory()->make(['status' => ElectionStatus::ACTIVE, 'end_at' => now()->addHour()]);
        $this->assertEquals('active', $active->getRuntimeStatus());

        $published = Election::factory()->make(['status' => ElectionStatus::PUBLISHED]);
        $this->assertEquals('published', $published->getRuntimeStatus());
    }
}
