<?php

namespace Tests\Feature\Public;

use App\Enums\ElectionStatus;
use App\Models\Candidate;
use App\Models\ClassRoom;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_elections()
    {
        Election::factory()->create(['status' => ElectionStatus::DRAFT]);
        Election::factory()->create(['status' => ElectionStatus::ACTIVE]);
        Election::factory()->create(['status' => ElectionStatus::CLOSED]);

        $published = Election::factory()->create([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now(),
        ]);

        $response = $this->get(route('hasil.index'));

        $response->assertStatus(200);
        $response->assertViewIs('public.result.index');
        $response->assertViewHas('elections', function ($elections) use ($published) {
            return $elections->count() === 1
                && $elections->first()->id === $published->id;
        });
    }

    public function test_show_displays_published_election_result()
    {
        $election = Election::factory()->create([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now(),
        ]);

        $response = $this->get(route('hasil.show', $election));

        $response->assertStatus(200);
        $response->assertViewIs('public.result.show');
        $response->assertViewHas('election');
        $response->assertViewHas('stats');
        $response->assertViewHas('candidates');
        $response->assertViewHas('sessions');
    }

    public function test_show_returns_404_for_non_published_election()
    {
        $draft = Election::factory()->create(['status' => ElectionStatus::DRAFT]);
        $active = Election::factory()->create(['status' => ElectionStatus::ACTIVE]);
        $closed = Election::factory()->create(['status' => ElectionStatus::CLOSED]);

        $this->get(route('hasil.show', $draft))->assertStatus(404);
        $this->get(route('hasil.show', $active))->assertStatus(404);
        $this->get(route('hasil.show', $closed))->assertStatus(404);
    }

    public function test_show_calculates_correct_stats()
    {
        $election = Election::factory()->create([
            'status'             => ElectionStatus::PUBLISHED,
            'hasil_published_at' => now(),
        ]);

        $class = ClassRoom::factory()->create();
        $candidate = Candidate::factory()->create(['election_id' => $election->id]);
        $session = ElectionSession::factory()->create([
            'election_id' => $election->id,
            'class_id'    => $class->id,
        ]);

        // 3 votes
        for ($i = 0; $i < 3; $i++) {
            Vote::create([
                'election_id'  => $election->id,
                'session_id'   => $session->id,
                'candidate_id' => $candidate->id,
                'hash'         => hash('sha256', Str::uuid()),
            ]);
        }

        $response = $this->get(route('hasil.show', $election));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_voted'] === 3
                && $stats['total_candidates'] === 1;
        });
    }
}
